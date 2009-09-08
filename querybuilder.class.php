<?php
/**
 *
 * @package DataRecord
 * @author Robert Cabri <robert@dicabrio.com>
 * @copyright Robert Cabri
 */
class QueryBuilder {

	private $sQueryType;

	private $bPrepared = false;

	CONST QUERY_DELETE_FORMAT = 'DELETE FROM %s WHERE %s';

	CONST QUERY_INSERT_FORMAT = 'INSERT INTO %s SET %s';

	CONST QUERY_UPDATE_FORMAT = 'UPDATE %s SET %s WHERE %s';

	CONST QUERY_SELECT_FORMAT = 'SELECT %s FROM %s WHERE %s';

	/**
	 * @var DataRecord
	 */
	private $oDataRecord;

	public function __construct($sQueryType, DataRecord $oDataRecord) {
		$this->sQueryType = $sQueryType;
		$this->oDataRecord = $oDataRecord;
	}

	public function getQuery($bPrepared = false) {
		$this->bPrepared = $bPrepared;
		$sTable = $this->oDataRecord->getTable();
		switch ($this->sQueryType) {
			case 'DELETE' :
				return $this->buildDeleteQuery($sTable);
				break;
			case 'SELECT' :
				return $this->buildSelectQuery($sTable);
				break;
			case 'INSERT' :
				return $this->buildInsertQuery($sTable);
				break;
			case 'UPDATE' :
				return $this->buildUpdateQuery($sTable);
				break;
		}

		throw new QueryBuilderException('No querytype specified');
	}

	private function buildDeleteQuery($sTable) {

		if ($this->bPrepared === true) {
			$sWhere = "id = :id";
		} else {
			$sWhere = sprintf("id = %d", $this->oDataRecord->getID());
		}

		$sQuery = sprintf(self::QUERY_DELETE_FORMAT, $sTable, $sWhere);
		return $sQuery;
	}

	private function buildSelectQuery($sTable) {

		$oColumns = $this->oDataRecord->getColumns();
		foreach ($oColumns as $oColumn) {
			$aColumns[] = sprintf("`%s`", $oColumn->getName());
		}

		$sColumns = implode(',', $aColumns);

		if ($this->bPrepared === true) {
			$sWhere = "id = :id";
		} else {
			$sWhere = sprintf("id = %d", $this->oDataRecord->getID());
		}

		return sprintf(self::QUERY_SELECT_FORMAT, $sColumns, $sTable, $sWhere);

	}

	private function buildInsertQuery($sTable) {

		$oColumns = $this->oDataRecord->getColumns();

		$sColumns = "";
		foreach ($oColumns as $oColumn) {
			if ($oColumn->getName() == 'id' || !$oColumn->isModified()) {
				continue;
			}

			if ($this->bPrepared) {
				$sColumns .= sprintf("`%s` = :%s,", $oColumn->getName(), $oColumn->getName());
			} else {
				$sColumns .= sprintf("`%s` = '%s',", $oColumn->getName(), mysql_real_escape_string($oColumn->getName()));
			}
		}

		$sColumns = substr($sColumns, 0, -1);

		$sQuery = sprintf(self::QUERY_INSERT_FORMAT, $sTable, $sColumns);
		return $sQuery;
	}

	private function buildUpdateQuery($sTable) {

		$oColumns = $this->oDataRecord->getColumns();

		$sColumns = "";
		foreach ($oColumns as $oColumn) {
			if ($oColumn->getName() == 'id' || !$oColumn->isModified()) {
				continue;
			}

			if ($this->bPrepared) {
				$sColumns .= sprintf("`%s` = :%s,", $oColumn->getName(), $oColumn->getName());
			} else {
				$sColumns .= sprintf("`%s` = '%s',", $oColumn->getName(), mysql_real_escape_string($oColumn->getName()));
			}
		}

		$sColumns = substr($sColumns, 0, -1);

		if ($this->bPrepared === true) {
			$sWhere = "id = :id";
		} else {
			$sWhere = sprintf("id = %d", $this->oDataRecord->getID());
		}

		return sprintf(self::QUERY_UPDATE_FORMAT, $sTable, $sColumns, $sWhere);
	}

	public function getPreparedValues() {
		switch ($this->sQueryType) {
			case 'INSERT' :
				return $this->buildInsertParams();
				break;
			case 'UPDATE' :
				return $this->buildUpdateParams();
				break;
			case 'DELETE' :
				return $this->buildDeleteParams();
				break;
			case 'SELECT' :
				return $this->buildSelectParams();
				break;
		}
	}

	private function buildInsertParams() {
		$aBindValues = array();
		foreach ($this->oDataRecord->getColumns() as $oColumn) {
			if ($oColumn->getName() != 'id' && $oColumn->isModified()) {
				$aBindValues[$oColumn->getName()] = $this->sanitizeVal($oColumn->getType(), $oColumn->getValue());
			}
		}
		return $aBindValues;
	}

	private function buildUpdateParams() {
		$aBindValues = array();
		foreach ($this->oDataRecord->getColumns() as $oColumn) {
			if ($oColumn->getName() == 'id' || $oColumn->isModified()) {
				$aBindValues[$oColumn->getName()] = $this->sanitizeVal($oColumn->getType(), $oColumn->getValue());
			}
		}
		return $aBindValues;
	}

	private function buildSelectParams() {
		return array('id' => $this->oDataRecord->getID());
	}

	private function buildDeleteParams() {
		return array('id' => $this->oDataRecord->getID());
	}

	private function sanitizeVal($iType, $mValue) {
		switch ($iType) {
			case DataTypes::INT:
				$mReturnValue = intval($mValue);
				break;
			case DataTypes::DOUBLE:
				$mReturnValue = doubleval($mValue);
				break;
			default:
				$mReturnValue = $mValue;
				break;
		}
		return $mReturnValue;
	}
}

class QueryBuilderException extends Exception {}
