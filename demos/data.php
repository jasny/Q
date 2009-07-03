<?php
use Q\DB, Q\Config, Q\SecurityException, Q\HTTP, Q\Lock;

set_include_path(__DIR__ . '/../src/');
$_ENV['Q_CONFIG'] = __DIR__ . '/config/settings.yaml';

require_once 'Q/Config.php';
require_once 'Q/Authenticate.php';
require_once 'Q/DB.php';
require_once 'Q/HTTP.php';
require_once 'Q/Lock.php';

/**
 * Main controller
 */
class Ctl_Data
{
    /**
	 * Table gateway
	 * @var DB_Table
	 */
    protected $td;
        
    /**
	 * Class constructor
	 */
    public function __construct()
    {
        if (!empty($_GET['table'])) {
            $table = $_GET['table'];
            list($table) = HTTP::scriptArgs();
        }

        if (!empty($table)) {
            if (strpos($this->table, '`')) throw new SecurityException("Table should not contains a '`' character. Go away hacker, go away you bad bad man!");
            $this->td = DB::i()->table($table);
        }
    }

    
    /**
	 * Execute command
	 */
    public function execute()
    {
        $command = !empty($_GET['cmd']) ? $_GET['cmd'] : $_SERVER['REQUEST_METHOD'];
        $this->$command();
    }

    /**
     * Action for get method
     */
    public function get()
    {
        if (!$this->td) return;
        
        $command = isset($_GET['id']) ? 'load' : 'overview';
        $this->$command();
    }
    
    /**
     * Alias of save
     */
    public function post()
    {
        $this->save();
    }
    

    /**
	 * Output current user
	 */
    public function curuser()
    {
    	$user = Authenticate::i()->user;
    	self::outputXML('<user id="' . $user->id . '" username="' . htmlspecialchars($user->username, ENT_COMPAT, 'UTF8') . '"' . ($user->author_id ? ' author_id="' . $user->author_id . '"' : '') . '><fullname>' . htmlspecialchars($user->fullname, ENT_COMPAT, 'UTF8') . '</fullname></user>');
    }
    
    
    /**
     * Describe a table.
     * 
     * @param string  $table   Use a different table
     * @param boolean $return  Do not output, but return XML
     * @return string
     */
    public function describe($table=null, $return=false)
    {
        if (empty($table) && !$this->td) throw new Exception("No table argument specified");
        $td = empty($table) ? $this->td : ($table instanceof DB_Table ? $table : DB::i()->table($table));
        
        $xml = '<props table="' . htmlspecialchars($td->getName(), ENT_COMPAT, 'UTF-8') . '" description="' . htmlspecialchars($td['description'], ENT_COMPAT, 'UTF-8') . '"' . (isset($td['icon']) ? ' icon=' . htmlspecialchars($td['icon'], ENT_COMPAT, 'UTF-8') . '"' : '') . ' descfield="' . htmlspecialchars($this->td->getFieldProperty('#role:description', 'name'), ENT_COMPAT, 'UTF-8') . '">';
        foreach ($td->getProperties() as $index=>$field) {
            if ($index[0] == '#' || !empty($field['hidden']) || (empty($field['type']) && empty($field['datatype']))) continue;

            $datatype = !empty($field['datatype']) ? $field['datatype'] : $field['type'];
            
            unset($values);
            $xml .= '<prop select="' . htmlspecialchars(isset($field['orm']) ? $field['orm'] : $field['name'], ENT_COMPAT, 'UTF-8') . '" datatype="' . htmlspecialchars($datatype, ENT_COMPAT, 'UTF-8') . '" caption="' . htmlspecialchars($field['caption'], ENT_COMPAT, 'UTF-8') . '"' . (isset($field['info']) ? ' info="' . htmlspecialchars($field['info'], ENT_COMPAT, 'UTF-8') . '"' : '') .
              (isset($field['default']) ? ' default="' . htmlspecialchars($field['default'], ENT_COMPAT, 'UTF-8') . '"' : '') . (!empty($field['frozen']) ? ' frozen="1"' : '') . ($field['required'] ? ' required="1"' : '') . 
              (isset($field['maxlength']) ? ' maxlength="' . (int)$field['maxlength'] . '"' : '') . (isset($field['decimals']) ? ' decimals="' . (int)$field['decimals'] . '"' : '') . (isset($field['mask']) ? ' mask="' . htmlspecialchars($field['mask'], ENT_COMPAT, 'UTF-8') . '"' : '')  . (isset($field['validate']) ? ' validate="' . htmlspecialchars($field['validate'], ENT_COMPAT, 'UTF-8') . '"' : '');
              
            switch ($datatype) {
                case 'children':
                    $xml .= ' type="children" multiple="' . $field['multiple'] . '">' . $this->describe($field['foreign_table'], true) . "</prop>";
                    break;
                
                case 'juntion':
                    $xml .= ' multiple="' . $field['multiple'] . '"';
                case 'lookupkey':
                case 'parentkey':
                    $xml .= ' type="lookup" foreign_table="' . htmlspecialchars($field['foreign_table'], ENT_COMPAT, 'UTF-8') . '" descfield="' . htmlspecialchars(DB::i()->table($field['foreign_table'])->getFieldProperty('#role:description', 'name'), ENT_COMPAT, 'UTF-8') . '" />';
                    break;
                    
                case 'boolean':
                    if (!isset($field['values'])) $values = array(0=>'Nee', 1=>'Ja');
                case 'enum':
                    if (!isset($values)) {
                        $values = is_array($field['values']) ? $field['values'] : Q\split_set($field['values'], ',');
                        $options = isset($field['options']) ? (is_array($field['options']) ? $field['options'] : Q\split_set($field['options'], ',')) : $values;  
                        $values = array_combine($options, $values);
                    }
                case 'set':
                    if (!isset($values)) {
                        $values = Q\binset(is_array($field['values']) ? $field['values'] : Q\split_set($field['values']));
                        $xml .= ' multiple="' . $field['multiple'] . '"';
                    }
                    $xml .= " type=\"dropdown\">";
                    foreach ($values as $i=>$value) $xml .= "<item value=\"$i\">" . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . "</item>";
                    $xml .= "</prop>";
                    break;
                    
                case 'html':
                    $xml .= ' type="custom" form="winEditor" mask="[Bewerken]"/>';
                    break;
                    
                default:
                    $xml .= '/>';
                    break;
            }
        }
        
        $xml .= '</props>';
        
        if ($return) return $xml;
        
        self::outputXML($xml);
        return null;
    }
    
    /**
	 * Output list of records.
	 */
    public function overview()
    {
        $show = !empty($_GET['show']) ? $_GET['show'] : 'current';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $_ENV['cfg']['cms']['limit_overview'];
        $filters = !empty($_GET['filter']) ? $_GET['filter'] : array();
        $order = isset($_GET['order']) ? $_GET['order'] : null;
        
        $statement = $this->td->getStatement('overview.xml');
        $statement->addToPart(DB::COMMAND_PART, 'SQL_CALC_FOUND_ROWS');
        
        foreach ($filters as $key=>$value) {
            if (empty($value)) continue;
            list($field, $operator) = explode(' ', $key . ' ');
            $statement->addCriteria(strpos('.', $field) === false ? DB::i()->makeIdentifier($this->table, $field) : DB::i()->quoteIdentifier($field), $value, $operator);
        }
        
        if (isset($limit)) $statement->setLimit($limit, DB::arg('page', $page));
        if (isset($order)) {
            list($field, $sort) = explode(" ", $order . ' ASC');
            $statement->addOrderBy((strpos('.', $field) === false ? DB::i()->makeIdentifier($this->table, $field) : DB::i()->quoteIdentifier($field)) . ' ' .$sort);
        }
        
        $xml = join("\n", (array)$statement->execute(DB::arg('state', $state))->getColumn());
        $rowcount = DB::i()->query("SELECT FOUND_ROWS()")->fetchValue();
        $xml = '<overview table="' . htmlspecialchars($this->table, ENT_COMPAT, 'UTF-8') . '" page="' . $page . '" count="' . $rowcount . '"' . (isset($limit) ? ' limit="' . $limit . '"' : '') . ' descfield="' . htmlspecialchars($this->td->getFieldProperty('#role:description', 'name'), ENT_COMPAT, 'UTF-8') . '">' . $xml . '</overview>'; 

        self::outputXML($xml);
    }
    
    /**
	 * Output a record.
	 */
    public function load()
    {
        if (!isset($_GET['id'])) throw new Exception("No id argument specified");
        $id = $_GET['id'];

        $xml = $td->load($id, 'xml', DB::FETCH_VALUE);
        if (empty($xml)) throw new Exception("Could not load {$td['name']} record '$id'.");
        
        self::outputXML($xml);
    }

    /**
	 * Add / update a record
	 */
    public function save()
    {
        $id = $td->newRecord($_POST)->validate()->save()->getId();
        echo $id;
    }

    /**
	 * Delete a record.
	 * 
	 * @param string  $table   Use a different table
	 * @param mixed   $id
	 */
    public function delete($table=null, $id=null)
    {
        if (empty($table) && !$this->td) throw new Exception("No table argument specified");
        $td = empty($table) ? $this->td : ($table instanceof DB_Table ? $table : DB::i()->table($table));
        
        if (!isset($id) && isset($_GET['id'])) $id = $_GET['id'];
        if (!isset($id)) throw new Exception("No id argument specified");

        $td->delete($id, DB::SINGLE_ROW);
        echo 1;
    }

    
    /**
     * Request or refresh a lock
     */
    public function lock()
    {
        $key = null;
        if (isset($_GET['lock'])) list($name, $key) = explode(':', $_GET['lock'], 2) + array(1=>null);
        if (isset($_GET['name'])) $name = $_GET['name'];
        if (isset($_GET['key'])) $key = $_GET['key'];
        
        if (empty($name)) {
            if (empty($this->td)) throw new Exception("No key or table argument specified.");
            if (!isset($_GET['id'])) throw new Exception("Table argument specified, but no id specified.");
            
            $name = md5($this->td['name'] . '~' . $_GET['id']);
        }
        
        $lock = new Lock(Config::i()->application . '.' . $name);
        
        if (!$lock->acquire($key)) {
            HTTP::response(423);
            self::outputXML($lock->asXML());
            exit;
        }
        
        echo $lock;
    }
    
    /**
     * Release a lock
     */
    public function unlock()
    {
        $key = null;
        if (isset($_GET['lock'])) list($name, $key) = explode(':', $_GET['lock'], 2) + array(1=>null);
        if (isset($_GET['name'])) $name = $_GET['name'];
        if (isset($_GET['key'])) $key = $_GET['key'];
                
        $lock = new Lock(Config::i()->application . '.' . $name);

        if (!$lock->release($check)) {
            HTTP::response(423);
            echo "Invalid token: You do not own that lock.";
            exit;
        }
        
        echo 1;
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
    	        HTTP::header('Content-type: text/plain; charset=UTF-8');
    	    } else {
            	HTTP::header('Content-type: text/xml; charset=UTF-8');
            	echo '<?xml version="1.0" encoding="UTF-8" ?>', "\n";
    	    }
    	}

    	echo $xml;
    }
}

// Execute
$ctl = new NDB_Data();
$ctl->execute();

?>