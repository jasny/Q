<?php
namespace Q;

require_once 'Q/DB.php';
require_once 'Q/Controller.php';
require_once 'Q/HTTP.php';

/**
 * Controller to create, read, update and delete data from a database.
 * 
 * @package Controller
 */
class Controller_CRUD implements Controller
{
    /**
	 * Table gateway
	 * @var DB_Table
	 */
    public $table;

    /**
     * Overview property
     * @var string
     */
    public $overview = 'overview';
    
    /**
     * Default limit for overview
     * @var int
     */
    public $defaultLimit;
    
    
    
    /**
	 * Class constructor
	 */
    public function __construct($options=array())
    {
        if (isset($_GET['table'])) $table = $_GET['table'];
          elseif (count(HTTP::scriptArgs()) > 0) list($table) = HTTP::scriptArgs();
        
        if (!empty($table)) {
            if (strpbrk($table, '`\'"') !== false) throw new SecurityException("Table should not contains quotation characters.");
            $this->table = DB::i()->table($table);
                  
            if ($this->td['role'] === 'revision-table' || $this->td['role'] === 'revision-history') throw new Exception("Don't access revision tables directly");
        }
    }
	
    
    /**
     * Action for get method.
     */
    public function get()
    {
         $command = isset($_GET['id']) ? 'load' : 'overview';
         return $this->$command();
    }
    
    /**
     * Alias of Controller_CRUD::save().
     */
    public function post()
    {
        $this->save();
    }
    
    /**
     * Add criteria to statement for each filter from array $filters
     * 
     * @param $statement
     * @param array $filters
     */
    protected function applyFilters($statement, $filters)
    {
    	$db = $this->table->getConnection();
    	
        foreach ($filters as $key=>$value) {
            if (empty($value)) continue;
            
            list($field, $operator) = explode(' ', $key) + array(1=>null);
            $statement->addCriteria(strpos('.', $field) === false ? $db->makeIdentifier($this->table, $field) : DB::i()->quoteField($field), $value, $operator);
        }
    }
    
    /**
	 * Output list of records.
	 */
    public function overview()
    {
        if (empty($this->table)) throw new Exception("No table argument specified");

        $statement = $this->table[$this->overview];
        if (empty($statement)) throw new Exception("Unable to show overview of {$this->table}; Table does not have property '$this->overview'.");
        
        $show = !empty($_GET['show']) ? $_GET['show'] : 'current';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        $filters = !empty($_GET['filter']) ? $_GET['filter'] : array();
        $order = isset($_GET['order']) ? $_GET['order'] : null;
        
        $matches = null;
        if (preg_match('/^\#callback:(\w+)$/i', (string)$statement, $matches)) {
            $fn = $matches[1];
            $xml = $fn($show, $page, $limit, $filters, $order);
        } else {
            
            $statement->addToPart(DB::COMMAND_PART, 'SQL_CALC_FOUND_ROWS');
            
            $statement->addCriteria('_revision_current', true);
            if ($show == 'active' || $show == 'current') $statement->addWhere('`_revision_action` != "DELETE"');
            if ($show == 'active') $statement->addWhere('`_revision_time_activate` IS NULL');
            if ($show == 'delete') $statement->addWhere('`_revision_action` = "DELETE"');
                        
            $this->applyFilters($statement, $filters);
            
            if (isset($limit)) $statement->setLimit($limit, DB::arg('page', $page));
            foreach ((array)$order as $o) {
                list($field, $sort) = explode(" ", "$o ASC");
                $statement->addOrderBy((strpos('.', $field) === false ? DB::i()->makeIdentifier($this->table, $field) : DB::i()->quoteField($field)) . ' ' .$sort);
            }
            
            $xml = join("\n", (array)$statement->execute()->getColumn());
            $rowcount = DB::i()->query("SELECT FOUND_ROWS()")->fetchValue();
            $xml = '<overview table="' . htmlspecialchars($this->table, ENT_COMPAT, 'UTF-8') . '" page="' . $page . '" count="' . $rowcount . '"' . (isset($limit) ? ' limit="' . $limit . '"' : '') . ' descfield="' . htmlspecialchars($this->td->getFieldProperty('#role:description', 'name'), ENT_COMPAT, 'UTF-8') . '">' . $xml . '</overview>'; 
        }

        self::outputXML($xml);
    }
    
    /**
	 * Output a record, history and revisions
	 */
    public function loadAll($id=null)
    {
        ob_start();
        self::outputXML('<data descfield="' . htmlspecialchars($this->td->getFieldProperty('#role:description', 'name'), ENT_COMPAT, 'UTF-8') . '">');
        $this->load($id);
        $this->revisions($id);
        $this->history($id);
        echo '</data>';
    }
    
    /**
	 * Output a record.
	 */
    public function load($id=null, $revision=null)
    {
        if (empty($this->table)) throw new Exception("No table argument specified");
        if (!isset($id)) list($id, $revision) = $this->getGetVars();
        if (!isset($id)) throw new NotFoundException("No id argument specified");

        if ($revision === 'latest') $revision = DB::i()->prepare("SELECT IF(`r`.`_revision_action`='DELETE', `r`.`_revision_previous`, `r`.`_revision_`) AS `revision` FROM `_revision_-{$this->table}` AS `r` WHERE `r`.`_revision_current` = TRUE")->addCriteria($this->td->getFieldProperty('#role:id', 'name'), $id)->execute()->fetchValue();
                
        $matches = null;
        if (preg_match('/^\#callback:(\w+)$/i', $this->td['load.xml'], $matches)) {
            $fn = $matches[1];
            $xml = $fn($id, $revision);
        } else {
            $stmt = $this->td->getStatement('load.xml')->addCriteria('#role:id', $id);
            if ($revision) $stmt->addCriteria("{$this->table}.`_revision_`", $revision);
              else $stmt->addCriteria("{$this->table}.`_revision_current`", true);
              
            $xml = $stmt->execute()->fetchValue();
        }

        if (empty($xml)) throw new Exception("Could not load {$this->td['name']} record $id.");
        self::outputXML($xml);
    }
    
    /**
	 * Ouput revisions of a record. 
	 */
    public function revisions($id)
    {
        if (empty($this->table)) throw new Exception("No table argument specified");
        if (!isset($id)) list($id) = $this->getGetVars();
        if (!isset($id)) throw new Exception("No id argument specified");
        
        $id_col = $this->td->getFieldProperty('#role:id', 'name_db');
        DB::i()->query("SET @cnt=0;");
        $revisions = DB::i()->query("SELECT xql_element('revision' AS `xql:root`, xql_forest(`r`.`_revision_` AS `this`, `r`.`_revision_previous` AS `previous`, `r`.`_revision_current` AS `current`, `r`.`_revision_time_activate` AS `time_activate`, `r`.`_revision_comment` AS `xql:cdata:comment`, `r`.`_revision_action` AS `action`, xql_element('user', revuser.fullname, revuser.id, revuser.user AS `username`), `r`.`_revision_timestamp` AS `timestamp`, `r`.`_revision_log` AS `xql:cdata:log`), @cnt:=@cnt+1 AS `version`) FROM `_revision_-{$this->table}` AS `r` LEFT JOIN `user` AS `revuser` ON `r`.`_revision_user_id`=`revuser`.`id` WHERE `r`.`$id_col`=? ORDER BY `r`.`_revision_timestamp`", $id)->getColumn();
        if (empty($revisions)) throw new Exception("Could not load {$this->td['name']} record $id. Does it exist?");
        
        $xml = "<revisions table=\"{$this->table}\" id=\"$id\">" . join('', $revisions) . "</revisions>";
        self::outputXML($xml);
    }
    
    /**
	 * Ouput revision history of a record. 
	 */
    public function history($id)
    {
        if (empty($this->table)) throw new Exception("No table argument specified");
        if (!isset($id)) list($id) = $this->getGetVars();
        if (!isset($id)) throw new Exception("No id argument specified");
        
        $id_col = $this->td->getFieldProperty('#role:id', 'name_db');
        $rev_future  = DB::i()->query("SELECT xql_forest('revision' AS `xql:root`, `r`.`_revision_` AS `this`, `r`.`_revision_previous` AS `previous`, `r`.`_revision_current` AS `current`, `r`.`_revision_time_activate` AS `time_activate`, `r`.`_revision_comment` AS `xql:cdata:comment`, `r`.`_revision_action` AS `action`, xql_element('user', revuser.fullname, revuser.id, revuser.user AS `username`), `r`.`_revision_timestamp` AS `timestamp`, `r`.`_revision_log` AS `xql:cdata:log`) FROM `_revision_-{$this->table}` AS `r` LEFT JOIN `user` AS `revuser` ON `r`.`_revision_user_id`=`revuser`.`id` WHERE `r`.`$id_col`=? AND `r`.`_revision_time_activate` IS NOT NULL AND `r`.`_revision_time_activate` != '9999-12-30' ORDER BY `r`.`_revision_time_activate` DESC", $id)->getColumn();
        $rev_history = DB::i()->query("SELECT xql_forest('revision' AS `xql:root`, `r`.`_revision_` AS `this`, `r`.`_revision_previous` AS `previous`, `r`.`_revision_current` AS `current`, `h`.`_revision_time_activate` AS `time_activate`, `r`.`_revision_comment` AS `xql:cdata:comment`, `r`.`_revision_action` AS `action`, xql_element('user', revuser.fullname, revuser.id, revuser.user AS `username`), `r`.`_revision_timestamp` AS `timestamp`, `r`.`_revision_log` AS `xql:cdata:log`) FROM `_revision_-{$this->table}-_history_` AS `h` INNER JOIN `_revision_-{$this->table}` AS `r` ON `h`.`_revision_`=`r`.`_revision_` LEFT JOIN `user` AS `revuser` ON `r`.`_revision_user_id`=`revuser`.`id` WHERE `r`.`$id_col`=? ORDER BY `h`.`_revision_time_activate` DESC", $id)->getColumn();
        
        $xml = "<history table=\"{$this->table}\" id=\"$id\">" . join("\n", (array)$rev_future) . join("\n", (array)$rev_history) . "</history>";
        self::outputXML($xml);
    }

    /**
	 * Add / update a record
	 */
    public function save()
    {
        if (empty($this->table)) throw new Exception("No table argument specified");
        list(, , $at, $comment) = $this->getGetVars();

        if (!isset($at)) $at = isset($_POST['_revision_time_activate']) ? $_POST['_revision_time_activate'] : '9999-12-30';
        if (isset($comment)) $_POST['_revision_comment'] = $comment;

        $matches = null;
        if (isset($this->td['save']) && preg_match('/^#callback:(.*)$/', $this->td['save'], $matches)) {
            list($id, $revision) = call_user_func($matches[1], $_POST, $at);
        } else {
            $td = $at <= strftime('%Y-%m-%d %H:%M:%S') ? $this->td : DB::i()->table("_revision_-{$this->table}");
            $record = $td->getRecord(array('_revision_'=>isset($_POST['_revision_previous']) ? $_POST['_revision_previous'] : null, '_revision_time_activate'=>$at) + $_POST);
            $record->_revision_ = null;
            $record->validate()->save();
            
            if ($td === $this->td) {
                $revision = null;
                $id = $record->getId();
            } else {
                $revision = $record->getId();
                $id = DB::i()->lookupValue("_revision_-{$this->table}", $this->td->getPrimaryKey(), array('_revision_'=>$revision));
            }
        }
        
        $this->load($id, $revision);
    }

    /**
	 * Delete a record.
	 */
    public function delete()
    {
        if (empty($this->table)) throw new Exception("No table argument specified");
        list($id, , $at) = $this->getGetVars();
                
        if (!isset($at) || $at <= strftime('%Y-%m-%d %H:%M:%S')) {
            DB::i()->delete($this->table, $id);
            $this->onactivate($id);
        } else {
            $record = DB::i()->table("_revision_-{$this->table}")->getStatement()->addWhere("`_revision_current`=TRUE")->load($id);
            $record->setValues(array('_revision_'=>null, '_revision_time_activate'=>$at, '_revision_action'=>'DELETE', '_revision_current'=>0))->save();
        }

        echo 1;
    }

    /**
     * Undelete a record. 
     */
    public function undelete()
    {
        if (empty($this->table)) throw new Exception("No table argument specified");
        if (!isset($id)) list($id, $revision) = $this->getGetVars();
                        
        $id_col = $this->td->getFieldProperty('#role:id', 'name_db');
        if (isset($revision)) {
            if (DB::lookupValue("`_revision_-{$this->table}`", $id_col, $revision) != $id) throw new Exception("Revision is not of the specified record.");
        } else {
            $revision = DB::i()->query("SELECT `rv`.`_revision_previous` FROM `_revision_-{$this->table}` AS rv LEFT JOIN `_revision_-{$this->table}-_history_` AS h ON rv.id = h.id AND rv._revision_timestamp < h._revision_time_activate WHERE rv.`$id_col`=? AND rv.`_revision_action`='DELETE'", $id)->fetchValue();
            if (!isset($revision)) throw new Exception("The record doesn't appear to be deleted.");
        }
        
        DB::i()->store($this->table, array('_revision_'=>$revision));
        $this->onactivate($id);
        echo 1;
    }
    
    /**
     * Switch to a revision / activate record. 
     */
    public function activate()
    {
        if (empty($this->table)) throw new Exception("No table argument specified");
        list($id, $revision, $at, $comment) = $this->getGetVars();
        if (!isset($id)) throw new Exception("No id argument specified");
        if ($revision === 'latest') $revision = DB::i()->lookupValue("`_revision_-{$this->table}`", 'MAX(`_revision_`)', array($this->td->getFieldProperty('#role:id', 'name')=>$id));
                
        $id_col = $this->td->getFieldProperty('#role:id', 'name_db');
        list($cur_revision, $active) = DB::i()->query("SELECT _revision_, _revision_time_activate IS NULL AS `active` FROM `_revision_-{$this->table}` WHERE `$id_col`=$id AND `_revision_current`=TRUE")->fetchRow();

        if (!isset($cur_revision)) throw new Exception("The record doesn't exist or is deleted.");
        if ($active && (!isset($revision) || $cur_revision == $revision)) {
            echo 0;
            return; // Already active
        }
        
        if (isset($revision) && DB::i()->lookupValue("`_revision_-{$this->table}`", $id_col, $revision) != $id) throw new Exception("Revision is not of the specified record.");
        if (!isset($revision)) $revision = $cur_revision;

        if (!isset($at) || $at < strftime('%Y-%m-%d %H:%M:%S')) {
            DB::i()->store($this->table, array($id_col=>$id, '_revision_'=>$revision) + (isset($comment) ? array('_revision_comment'=>$comment) : array()));
            $this->onactivate($id);
            echo 1;
        } else {
            DB::i()->update("`_revision_-{$this->table}`", array('_revision_'=>$revision), array('_revision_time_activate'=>$at) + (isset($comment) ? array('_revision_comment'=>$comment) : array()));
            echo 2;
        }
    }

    /**
     * Undo an activation of a a record.
     */
    public function deactivate()
    {
        if (empty($this->table)) throw new Exception("No table argument specified");
        list($id, , $at) = $this->getGetVars();
        if (!isset($at)) $at = '9999-12-31';

        DB::i()->query("UPDATE `_revision_-{$this->table}` SET `_revision_time_activate` = " . DB::i()->quote($at) . " WHERE `" . $this->td->getFieldProperty('#role:id', 'name') . "`=$id AND `_revision_current`=TRUE");
        $this->onactivate($id);
        echo 1;
    }
    
    /**
     * Call onactivate
     */
    public function onactivate($id)
    {
        if (!$this->td['onactivate']) return;
        
        if (preg_match('/^\#callback:(\w+)$/i', $this->td['onactivate'], $matches)) {
            $fn = $matches[1];
            $fn($id);
        } else {
            $this->td->getStatement('onactivate')->execute(DB::arg('id', $id));
        }
    }

    
    /**
     * Request or refresh a lock
     */
    public function lock()
    {
        $key = filter_input(INPUT_GET, 'key');
        if (empty($key)) {
            if (empty($this->table)) throw new Exception("No key or table argument specified");
            list($id) = $this->getGetVars();
            
            $key = md5($this->table . '~' . $id);
            $check = md5(microtime());
        } else {
            list($key, $check) = explode(':', $key, 2) + array(1=>md5(microtime()));
        }
        
        $lock = 'nbd-cms.lock.' . $key;
        
        if (!apc_add($lock, array('timestamp'=>strftime('%Y-%m-%d %T'), 'check'=>$check))) {
            $info = apc_fetch($lock);
            if ($info['check'] != $check) {
                HTTP::response(423); //locked
                self::outputXML("<locked key=\"$key\" " . (!empty($id) ? "table=\"{$this->table}\" id=\"{$id}\"" : '') . " timeout=\"{$_ENV['cfg']['lock-timeout']}\">" .
                  (isset($info['user_fullname']) ? '<user>' . htmlspecialchars($info['user_fullname'], ENT_COMPAT, 'UTF-8') . '</user>' : null) .
                  "<timestamp>{$info['timestamp']}</timestamp>" .
                  '</locked>');
                
                exit;
            }
            apc_store($lock, $info, $_ENV['cfg']['lock-timeout']);
        }
        
        echo $key . ':' . $check;
    }
    
    /**
     * Release a lock
     */
    public function unlock()
    {
        $key = filter_input(INPUT_GET, 'key');
        if (empty($key)) throw new Exception("No key argument specified");
        list($key, $check) = explode(':', $key, 2) + array(1=>null);
        
        $lock = 'nbd-cms.lock.' . $key;
        $info = apc_fetch($lock);

        if (empty($info)) {
            HTTP::response(204);
            echo "Lock does not exist";
            exit;
        }
        
        if ($info['check'] != $check) {
            HTTP::response(423); //locked
            echo "Invalid token: You do not own that lock.";
            exit;
        }
        
        apc_delete($lock);
        echo 1;
    }

    
    /**
     * Get the latest modified records
     */
    public function latest()
    {
        $limit = isset($_GET['limit']) ? $_GET['limit'] : $_ENV['cfg']['cms']['limit_latest'];
        
        $stmt = DB::i()->prepare("SELECT xql_element(`latest`.`table`, xql_forest(`latest`.`description`, `latest`.`action`, DATE_FORMAT(`latest`.`timestamp`, '%d-%m-%Y %T') AS `timestamp`, xql_element('user', revuser.fullname, revuser.id, revuser.user AS `username`)), `latest`.`id`, `latest`.`revision` AS `rev`) FROM `_revision_latest` AS `latest` LEFT JOIN `_revision_latest` AS `l` ON `latest`.`table`=`l`.`table` AND `latest`.`id`=`l`.`id` AND `latest`.`revision` < `l`.`revision` LEFT JOIN `user` AS `revuser` ON `latest`.`user_id`=`revuser`.`id` WHERE `l`.`id` IS NULL ORDER BY `latest`.`timestamp` DESC LIMIT $limit");
        if (!empty($this->table)) $stmt->addCriteria('`latest`.`table`', $this->table);
        if (!empty($_GET['user_id'])) $stmt->addCriteria('`latest`.`user_id`', $_GET['user_id']=='current' ? Q\Authenticate::i()->user_id : $_GET['user_id']);
        
        $latest = $stmt->execute()->getColumn();
        
        $xml = "<latest" . (!empty($this->table) ? ' table="' . htmlspecialchars($this->table, ENT_COMPAT, 'UTF-8') . '"' : '') . (!empty($_GET['table']) ? ' user_id="' . htmlspecialchars($_GET['user_id'], ENT_COMPAT, 'UTF-8') . '"' : '') . " limit=\"$limit\">" . join("\n", (array)$rev_future) . join("\n", (array)$latest) . "</latest>";
        self::outputXML($xml);
    }
    
    /**
     * Get statistics
     */
    public function statistics()
    {
        $tables = DB::i()->prepare("SELECT xql_element('table', null, `tbl`.`TABLE_NAME` AS `name`, `tbl`.`TABLE_ROWS` AS `count`) AS `xml` FROM `INFORMATION_SCHEMA`.`TABLES` AS `tbl` WHERE `tbl`.`TABLE_SCHEMA`=DATABASE()")->addCriteria("`tbl`.`TABLE_NAME`", $_ENV['cfg']['statistics_tables'])->execute()->getColumn();
        $supplier = DB::i()->query("SELECT xql_element('table', null, 'supplier' AS `name`, IF(`subscription_type`=0, 'prospect', 'participant') AS `subscription`, COUNT(*) AS `records`) AS `xml` FROM `supplier` GROUP BY (`subscription_type`=0)")->getColumn();
        
        $xml = "<statistics>" . join("\n", (array)$tables) . join("\n", (array)$supplier) . "</statistics>";
        self::outputXML($xml);
    }
    
    /**
	 * Helper function.
	 * 
	 * @param string $xml
	 */
    static protected function outputXML($xml)
    {
    	if (!headers_sent()) {
    	    if (isset($_SERVER['CONTENT_TYPE']) && preg_match('/^multipart\/form-data;/i', $_SERVER['CONTENT_TYPE'])) {
    	        header('Content-type: text/plain; charset=UTF-8');
    	    } else {
            	header('Content-type: text/xml; charset=UTF-8');
            	if (ob_get_length() == 0) echo '<?xml version="1.0" encoding="UTF-8" ?>', "\n";
    	    }
    	}

    	echo $xml;
    }
}

// Execute
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $ctl = new NBD_Data();
    $ctl->execute();
}

?>
