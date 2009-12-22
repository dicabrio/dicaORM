<?php

/**
 * class that represents an aggregate
 *
 * @package hosting_package
 * @author Jason Perkins <jperkins@sneer.org>
 * @version $Revision: 1.2 $
 * @copyright Jason Perkins
 *
 */

class ColumnAggr extends ArrayObject {

	private $aModified = array();

	/**
	 * constructur
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}


	/*
	 * Adds the passed $item(s) to the $this->collection
	 *
	 * @returns void
	 */
	public function add(Column $item) {
		parent::offsetSet($item->getName(), $item);
	}


	/**
	 * __get
	 *
	 * @param datatype $paramname description
	 * @return datatype description
	 */
	public function __get($property) {
		if (parent::offsetExists($property)) {
			return parent::offsetGet($property)->getValue();
		}
	}


	/**
	 * __set
	 *
	 * @access public
	 * @param datatype $paramname description
	 * @return datatype description
	 */
	public function __set($property, $sNewValue) {
		if (parent::offsetExists($property)) {
			$item = parent::offsetGet($property);
			$sOldValue = $item->getValue();
			if ($sNewValue != $sOldValue) {
				$item->setValue($sNewValue);
				$this->aModified[$property] = true;
			}
		}
	}

	public function isModified() {
		return count($this->aModified) > 0;
	}

	public function clearModifiedStatus() {
		$this->aModified = array();
	}

	/**
	 * @return string
	 */
	public function __toString() {
		$string = "Attributes <br />";

		$oIterator = parent::getIterator();
		while ($oIterator->valid()) {
			$oAttribute = $oIterator->current();
			$string .= $oAttribute->getName().": ".$oAttribute->getValue()."<br />";
			$oIterator->next();
		}

		return $string;
	}
}

