<?php
/**
 * Class that holds the criteria for the where clause of a query
 * It is used in the findAll method in DataRecord
 *
 * @package DataRecord
 * @author Robert Cabri <robert@dicabrio.com>
 * @copyright Robert Cabri
 */
class Criteria extends ArrayObject {

	private $query = "";

	public function __construct($sQueryPart, $bind=array()) {
		$this->query = $sQueryPart;

		if (count($bind) > 0) {
			parent::__construct($bind);
		}
	}

	public function addBind($key, $value) {
		parent::offsetSet($key, $value);
	}

	public function addBinds($bindings) {
		parent::append($bindings);
	}

	public function getQuery() {
		return $this->query;
	}

}
