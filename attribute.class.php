<?php

/**
 * represents an attribute of an DataRecord
 *
 * @package DataRecord
 * @author Robert Cabri <robert@dicabrio.com>
 * @copyright Robert Cabri
 */
class Attribute {

	/**
	 *	@var string
	 */
	private $name = null;

	/**
	 *	@var string
	 */
	private $value = null;

	/**
	 *	@var string
	 */
	private $formattedName = '';

	/**
	 *	@var int
	 */
	private $size = 0;

	/**
	 *	@var string
	 */
	private $type = null;

	/**
	 *	@var boolean
	 */
	private $isNullable = false;

	/**
	 *	@var boolean
	 */
	private $isFk = false;
	
	/**
	 * @var boolean
	 */
	private $bIsModified = false;

	/**
	 *	@var string
	 */
	private $fkTable = null;
	
	private $joinType = 'inner';


	/**
	 * constructor
	 *
	 * @param string $name
	 * @return void
	 */
	public function __construct($name) {
		$this->name = $name;
	}


	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}


	/**
	 * @param string $value
	 * @return void
	 */
	public function setValue($value) {
		$this->validateValue($value);
		
		if ($this->value != $value) {
			$this->bIsModified = true;
		}
		
		$this->value = $value;
	}
	
	/**
	 * @return boolean
	 */
	public function isModified() {
		return $this->bIsModified;
	}


	/**
	 * @return string
	 */
	public function getFormattedName() {
		return $this->formattedName;
	}


	/**
	 * @param string $name
	 * @return void
	 */
	public function setFormattedName($name) {
		$this->formattedName = $name;
	}


	/**
	 * @return int
	 */
	public function getSize() {
		return $this->size;
	}


	/**
	 * @param int $size
	 * @return void
	 */
	public function setSize($size) {
		$this->size = $size;
	}


	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}


	/**
	 * @param string $type
	 * @return void
	 */
	public function setType($type) {
		$this->type = $type;
	}


	/**
	 * @return boolean
	 */
	public function IsNullable() {
		return $this->isNullable;
	}


	/**
	 * @param boolean $bIsNullable
	 * @return void
	 */
	public function setNullable($bIsNullable) {
		$this->isNullable = $bIsNullable;
	}


	/**
	 * @return boolean
	 */
	public function isForeignKey() {
		return $this->isFk;
	}

	/**
	 * @param boolean $isFk
	 * @return void
	 */
	public function setForeignKey($isFk) {
		$this->isFk = $isFk;
	}

	/**
	 * @return string
	 */
	public function getForeignKeyTable() {
		return $this->fkTable;
	}

	/**
	 * @param string $fkTable
	 * @return void
	 */
	public function setForeignKeyTable($fkTable) {
		$this->fkTable = $fkTable;
	}
	
	/**
	 * @param string $joinType
	 * @return void
	 */
	public function setJoinType($joinType) {
		$this->joinType = $joinType;
	}


	/**
	 * @return int
	 */
	public function getLength() {
		return strlen($this->value);
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * @TODO also fire custom validators
	 * 
	 * @param string $value
	 * @return void
	 */
	private function validateValue($value) {
		// validate
		switch ($this->type) {
			case DataTypes::VARCHAR:
				if (strlen($value) > $this->size) {
					throw new RecordException('The value of `'.$this->name.'` is to long');
				}
				
				if (!$this->isNullable && empty($value)) {
					throw new RecordException('The value of `'.$this->name.'` may not be null');
				}
			break;
			case DataTypes::INT:
				if (!is_int($value) && !ctype_digit($value)) {
					throw new RecordException('The value of is `'.$this->name.'` no number');
				}
			break;
		} 
	}
}

