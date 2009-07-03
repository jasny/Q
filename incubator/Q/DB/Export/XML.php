<?php

	/**
	 * Default config for to XML conversion.
	 * 
	 * view     'standard': Each column value is a attribute in a row node with attribute name
	 *          'attributes': Each column value is a attribute in a row attribute
	 *          'forest': Node name fore each column is the field name
	 * root     Name of the root node
	 * row      Name of the row nodes
	 * column   Name of the column nodes (when $cfg['view'] is standard)
	 * value    Name of the value nodes when using a tree result
	 * names    'fieldnames': Use fieldnames as tags/attributes
	 *          'roles: Use roles as tags/attributes
	 * 
	 * @var array
	 */
	static public $xmlcfg = array(
		'view'=>'forest',
		'root'=>'resultset',
		'row'=>'row',
		'column'=>'field',
		'value'=>'value',
		'names'=>'fieldnames'
	);

	/**
	 * Returns an XML representation for the result.
	 * Native XML databases will return result XML if $cfg is not specified.
	 * 
	 * @param  array        $cfg   Properties on how to convert the record to XML (see DB_Result_$xmlcfg)
	 * @return string
	 */
	abstract public function getXML($cfg=null);

	
// DB_MySQL_Result_Tree
	
	/**
	 * Returns an XML representation for the result.
	 * CAUTION: resets the result pointer.
	 * 
	 * @param  array   $cfg   Properties on how to convert the record to XML (see DB_$xmlcfg) 
	 * @return string
	 */
	function getXML($cfg=null)
	{
		$cfg = isset($cfg) ? array_merge(DB::$xmlcfg, $cfg) : DB::$xmlcfg;

		switch ($cfg['names']) {
			case 'fieldnames':
				$names = $this->getFieldnames(DB::FIELDNAME_COL);
				foreach ($names as $i=>$name) $names[$i] = array($i, $name);
				break;
			case 'roles':
				$names = array();
				foreach ($this->getRoles() as $name=>$i) $names[] = array($name, $i);
				break;
		}
				
		switch ($cfg['view']) {
			case 'attributes':
				$code = 'return "<' . $cfg['row']; 
				foreach ($names as $n) $code .= ' ' . $n[1] . '=\\"" . htmlspecialchars($row[' . $n[0] . ']) . "\\"';
				$code .= ' />";';
				break;
			
			case 'forest':
				$code = 'return "<' . $cfg['row'] . '>';
				foreach ($names as $n) $code .= '<' . $n[1] . '>" . htmlspecialchars($row[' . $n[0] . ']) . "</' . $n[1] . '>';
				$code .= '</' . $cfg['row'] . '>";';
				break;
			
			case 'standard':
				$code = 'return "<' . $cfg['row'] . '>';
				foreach ($names as $n) $code .= '<' . $cfg['column'] . ' name=\\"' . $n[1] . '\\">" . htmlspecialchars($row[' . $n[0] . ']) . "</' . $cfg['column'] . '>';
				$code .= '</' . $cfg['row'] . '>";';
				break;

			default:
				throw new DB_Exception("Unable to create XML: Unknown view '" . $cfg['view'] . "'");				
		}

		$fn = create_function('$row', $code);
		
		$this->native->data_seek(0);
		$xml = "<" . $cfg['root'] . ">";
		while (($row = $this->native->fetch_row())) $xml .= $fn($row);
		$xml .= "</" . $cfg['root'] . ">";
		
		$this->native->data_seek(0);
		return $xml;
	}	
	
// DB_MySQL_Result_Tree
	
	/**
	 * Returns an XML representation for the result.
	 * CAUTION: resets the result pointer.
	 * 
	 * @param  array    $cfg   Properties on how to convert the record to XML (see DB_$xmlcfg) 
	 * @return string
	 */
	function getXML($cfg=null)
	{
		$cfg = isset($cfg) ? array_merge(DB::$xmlcfg, $cfg) : DB::$xmlcfg;
		
		$this->native->data_seek(0);
		$xml = "<" . $cfg['root'] . ">";
		
		if ($cfg['names'] !== 'roles') {
			switch ($cfg['view']) {
				case 'attributes':	while(($row = $this->fetchAssoc(DB::FETCH_NON_RECURSIVE))) $xml .= $this->getXML_Attributes($row, $cfg); break;
				case 'forest':		while(($row = $this->fetchAssoc(DB::FETCH_NON_RECURSIVE))) $xml .= $this->getXML_Forest($row, $cfg); break;
				case 'standard':	while(($row = $this->fetchAssoc(DB::FETCH_NON_RECURSIVE))) $xml .= $this->getXML_Standard($row, $cfg); break;
				default:			throw new DB_Exception("Unable to create XML: Unknown view '" . $cfg['view'] . "'");
			}
		} else {
			switch ($cfg['view']) {
				case 'attributes':	while(($row = $this->fetchRoles(DB::FETCH_NON_RECURSIVE))) $xml .= $this->getXML_Attributes($row, $cfg); break;
				case 'forest':		while(($row = $this->fetchRoles(DB::FETCH_NON_RECURSIVE))) $xml .= $this->getXML_Forest($row, $cfg); break;
				case 'standard':	while(($row = $this->fetchRoles(DB::FETCH_NON_RECURSIVE))) $xml .= $this->getXML_Standard($row, $cfg); break;
				default:			throw new DB_Exception("Unable to create XML: Unknown view '" . $cfg['view'] . "'");
			}
		}
		
		$xml .= "</" . $cfg['root'] . ">";
		
		$this->native->data_seek(0);
		return $xml;
	}
	
	/**
	 * Create XML for a row using 'attributes' format
	 * 
	 * @param  array   $row
	 * @param  array   $cfg   Properties on how to convert the record to XML (see DB_$xmlcfg) 
	 * @param  boolean $close Close XML node 
	 * @return string
	 */
	protected function getXML_Attributes($row, $cfg, $close=true)
	{
		$i = 0;
		$xml_child = null;
		$xml = "<" . $cfg['row'];
		
		foreach ($row as $key=>$value) {
			if ($cfg['names'] === 'roles') $i = $this->getFieldIndex("#role:{$key}");
			
			if ($key === 'tree:join') {
				// skip
			} elseif (!isset($this->children[$i])) {
				$xml .= " $key=\"" . htmlspecialchars($value) . '"';
			} elseif ($this->children[$i][3] == DB::FETCH_VALUE) {
				$xml_child .= "<$key>";
				if (isset($value)) foreach ($value as $subval) $xml_child .= "<" . $cfg['value'] . " value=\"" . htmlspecialchars($subval) . "\" />";
				$xml_child .= "</$key>";
			} elseif (!($this->children[$i][0] instanceof self)) {
				$xml_child .= "<$key>";
				if (isset($value)) foreach ($value as $subrow) {
					$xml_child .= "<" . $cfg['row'];
					foreach ($subrow as $subkey=>$subvalue) if ($subkey !== 'tree:join') $xml_child .= " $subkey=\"" . htmlspecialchars($subvalue) . '"';
					$xml_child .= "/>";
				}
				$xml_child .= "</$key>";
			} else {
				$xml_child .= "<$key>";
				if (isset($value)) foreach ($value as $subrow) $xml_child .= $this->children[$i]->getXML_Attributes($subrow, $cfg);
				$xml_child .= "</$key>";
			}
			$i++;
		}
		
		if ($close) $xml .= isset($xml_child) ? ">" . $xml_child . "</" . $cfg['row'] . ">" : " />";
		 else $xml .= ">" . $xml_child;
		
		return $xml;
	}
	
	/**
	 * Create XML for a row using 'forest' format
	 * 
	 * @param  array   $row
	 * @param  array   $cfg   Properties on how to convert the record to XML (see DB_$xmlcfg) 
	 * @param  boolean $close Close XML node 
	 * @return string
	 */
	protected function getXML_Forest($row, $cfg, $close=true)
	{
		$i = 0;
		$xml = "<" . $cfg['row'] . ">";
		
		foreach ($row as $key=>$value) {
			if ($cfg['names'] === 'roles') $i = $this->getFieldIndex("#role:{$key}");
			
			if ($key === 'tree:join') {
				// skip
			} elseif (!isset($this->children[$i])) {
				$xml .= "<$key>" . htmlspecialchars($value) . "</$key>";
			} elseif ($this->children[$i][3] == DB::FETCH_VALUE) {
				$xml .= "<$key>";
				if (isset($value)) foreach ($value as $subval) $xml .= "<" . $cfg['value'] . ">" . htmlspecialchars($subval) . "</" . $cfg['value'] . ">";
				$xml .= "</$key>";
			} elseif (!($this->children[$i][0] instanceof self)) {
				$xml .= "<$key>";
				if (isset($value)) foreach ($value as $subrow) {
					$xml .= "<" . $cfg['row'] . ">";
					foreach ($subrow as $subkey=>$subvalue) if ($subkey !== 'tree:join') $xml .= "<$subkey>" . htmlspecialchars($subvalue) . "</$subkey>";
					$xml .= "</" . $cfg['row'] . ">";
				}
				$xml .= "</$key>";
			} else {
				$xml .= "<$key>";
				if (isset($value)) foreach ($value as $subrow) $xml .= $this->children[$i][0]->getXML_Forest($subrow, $cfg);
				$xml .= "</$key>";
			}
			$i++;
		}
		
		if ($close) $xml .= "</" . $cfg['row'] . ">";
		return $xml;
	}
	
	/**
	 * Create XML for a row using 'standard' format
	 * 
	 * @param  array   $row
	 * @param  array   $cfg   Properties on how to convert the record to XML (see DB_$xmlcfg) 
	 * @param  boolean $close Close XML node 
	 * @return string
	 */
	protected function getXML_Standard($row, $cfg, $close=true)
	{
		$i = 0;
		$col = $cfg['column'];
		$xml = "<" . $cfg['row'] . ">";
		
		foreach ($row as $key=>$value) {
			if ($cfg['names'] === 'roles') $i = $this->getFieldIndex("#role:{$key}");
			
			if ($key === 'tree:join') {
				// skip
			} elseif (!isset($this->children[$i])) {
				$xml .= "<$col name=\"$key=\">" . htmlspecialchars($value) . "</$col>";
			} elseif ($this->children[$i][3] == DB::FETCH_VALUE) {
				$xml .= "<$col name=\"$key=\">";
				if (isset($value)) foreach ($value as $subval) $xml .= "<" . $cfg['value'] . ">" . htmlspecialchars($subval) . "</" . $cfg['value'] . ">";
				$xml .= "</$col>";
			} elseif (!($this->children[$i][0] instanceof self)) {
				$xml .= "<$col name=\"$key=\">";
				if (isset($value)) foreach ($value as $subrow) {
					$xml .= "<" . $cfg['row'] . ">";
					foreach ($subrow as $subkey=>$subvalue) if ($subkey !== 'tree:join') $xml .= "<$col name=\"$subkey=\">" . htmlspecialchars($subvalue) . "</$col>";
					$xml .= "</" . $cfg['row'] . ">";
				}
				$xml .= "</$col>";
			} else {
				$xml .= "<$col name=\"$key=\">";
				if (isset($value)) foreach ($value as $subrow) $xml .= $this->children[$i]->getXML_Standard($subrow, $cfg);
				$xml .= "</$col>";
			}
			$i++;
		}
		
		if ($close) $xml .= "</" . $cfg['row'] . ">";
		return $xml;
	}
	

// DB_MySQL_Result_NestedSet
	
	/**
	 * Create XML for a row using 'attributes' format
	 * 
	 * @param array   $row
	 * @param array   $cfg    Properties on how to convert the record to XML (see DB_$xmlcfg)
	 * @param boolean $close  Close XML node 
	 * @return string
	 */
	protected function getXML_Attributes($row, $cfg, $close=true)
	{
		$xml = parent::getXML_Attributes($row, $cfg, false);
		
		if ($this->prepareFetchChild()) {
			do {
				$row = $cfg['names'] !== 'roles' ? $this->native->fetch_assoc() : $this->fetchRoles(DB::FETCH_NON_RECURSIVE);
				$xml .= $this->getXML_Attributes($row, $cfg);
			} while ($this->prepareFetchSibling());
		}
		
		if ($close) $xml .= "</" . $cfg['row'] . ">";
		return $xml;
	}
	
	/**
	 * Create XML for a row using 'forest' format
	 * 
	 * @param array   $row
	 * @param array   $cfg    Properties on how to convert the record to XML (see DB_$xmlcfg) 
	 * @param boolean $close  Close XML node 
	 * @return string
	 */
	protected function getXML_Forest($row, $cfg, $close=true)
	{
		$xml = parent::getXML_Forest($row, $cfg, false);
		
		if ($this->prepareFetchChild()) {
			do {
				$row = $cfg['names'] !== 'roles' ? $this->native->fetch_assoc() : $this->fetchRoles(DB::FETCH_NON_RECURSIVE);
				$xml .= $this->getXML_Forest($row, $cfg);
			} while ($this->prepareFetchSibling());
		}
		
		if ($close) $xml .= "</" . $cfg['row'] . ">";
		return $xml;
	}
	
	/**
	 * Create XML for a row using 'standard' format
	 * 
	 * @param array   $row
	 * @param array   $cfg    Properties on how to convert the record to XML (see DB_$xmlcfg) 
	 * @param boolean $close  Close XML node 
	 * @return string
	 */
	protected function getXML_Standard($row, $cfg, $close=true)
	{
		$xml = parent::getXML_Standard($row, $cfg, false);
		
		if ($this->prepareFetchChild()) {
			do {
				$row = $cfg['names'] !== 'roles' ? $this->native->fetch_assoc() : $this->fetchRoles(DB::FETCH_NON_RECURSIVE);
				$xml .= $this->getXML_Standard($row, $cfg);
			} while ($this->prepareFetchSibling());
		}
		
		if ($close) $xml .= "</" . $cfg['row'] . ">";
		return $xml;
	}
	
	

	
	/**
	 * Returns an XML representation for the result.
	 * CAUTION: resets the result pointer.
	 * 
	 * @param  array   $cfg   Properties on how to convert the record to XML (see DB_$xmlcfg) 
	 * @return string
	 */
	function getXML($cfg=null)
	{
		$cfg = isset($cfg) ? array_merge(DB::$xmlcfg, $cfg) : DB::$xmlcfg;

		switch ($cfg['names']) {
			case 'fieldnames':
				$names = $this->getFieldnames(DB::FIELDNAME_COL);
				foreach ($names as $i=>$name) $names[$i] = array($i, $name);
				break;
			case 'roles':
				$names = array();
				foreach ($this->getRoles() as $name=>$i) $names[] = array($name, $i);
				break;
		}
				
		switch ($cfg['view']) {
			case 'attributes':
				$code = 'return "<' . $cfg['row']; 
				foreach ($names as $n) $code .= ' ' . $n[1] . '=\\"" . htmlspecialchars($row[' . $n[0] . ']) . "\\"';
				$code .= ' />";';
				break;
			
			case 'forest':
				$code = 'return "<' . $cfg['row'] . '>';
				foreach ($names as $n) $code .= '<' . $n[1] . '>" . htmlspecialchars($row[' . $n[0] . ']) . "</' . $n[1] . '>';
				$code .= '</' . $cfg['row'] . '>";';
				break;
			
			case 'standard':
				$code = 'return "<' . $cfg['row'] . '>';
				foreach ($names as $n) $code .= '<' . $cfg['column'] . ' name=\\"' . $n[1] . '\\">" . htmlspecialchars($row[' . $n[0] . ']) . "</' . $cfg['column'] . '>';
				$code .= '</' . $cfg['row'] . '>";';
				break;

			default:
				throw new DB_Exception("Unable to create XML: Unknown view '" . $cfg['view'] . "'");				
		}

		$fn = create_function('$row', $code);
		
		$this->native->data_seek(0);
		$xml = "<" . $cfg['root'] . ">";
		while (($row = $this->native->fetch_row())) $xml .= $fn($row);
		$xml .= "</" . $cfg['root'] . ">";
		
		$this->native->data_seek(0);
		return $xml;
	}	
?>