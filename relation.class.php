<?php
/**
 * 
 * @author robertcabri
 *
 */
class Relation extends DataRecord {

	public function __construct($sThis, $sOther, DataRecord $oThis, DataRecord $oOther) {
		parent::__construct($sThis.'_'.$sOther);
		$sThisIDString = $sThis.'_id';
		$sOtherIDString = $sOther.'_id';

		parent::addColumn('id', DataTypes::INT, false, true);
		parent::addColumn($sThisIDString, DataTypes::INT, false, true);
		parent::addColumn($sOtherIDString, DataTypes::INT, false, true);

		$this->$sThisIDString = $oThis->getID();
		$this->$sOtherIDString = $oOther->getID();
	}

	public function defineColumns() {
		return;
	}

	public static function add($sThis, $sOther, DataRecord $oThis, DataRecord $oOther) {
		$oRel = new Relation($sThis, $sOther, $oThis, $oOther);
		$oRel->save(true);
	}

	public static function remove($sThis, $sOther, DataRecord $oThis, DataRecord $oOther) {
		$aRel = Relation::get($sThis, $sOther, $oThis, $oOther);
		foreach ($aRel as $oRel) {
			$oRel->delete();
		}
	}

	public static function get($sThis, $sOther, DataRecord $oThis, DataRecord $oOther) {
		self::setRawOutput(true);
		$sRelationTable = $sThis.'_'.$sOther;
		$sQuery = "	SELECT	*
FROM	".$sRelationTable."
WHERE	".$sThis."_id = :thisid
AND ".$sOther."_id = :otherid " ;

		$aBindings = array('thisid' => $oThis->getID(), 'otherid' => $oOther->getID());
		$aResult = parent::findBySql($sRelationTable, $sQuery, $aBindings);

		$aRelations = array();
		foreach ($aResult as $aRelationRecord) {
			$oRelation = new Relation($sThis, $sOther, $oThis, $oOther);
			$oRelation->setID($aRelationRecord['id']);
			$aRelations[] = $oRelation;
		}
		return $aRelations;
	}

	/**
	 *
	 * @param string $sThis
	 * @param string $sOther
	 * @param DataRecord $oThis
	 * @param DataRecord $oOther
	 * @return array
	 */
	public static function getOther($sThis, $sOther, DataRecord $oThis=null, DataRecord $oOther=null) {
		if ($oThis === null && $oOther === null) {
			throw new RecordException('Cannot retrieve relations if no relatable object is given');
		}

		$sRelationTable = $sThis.'_'.$sOther;

		// search for relation for a certain object
		// if no this object is given get the other as search reference
		$sSearchRelations = $sOther;
		$sReferenceRelation = $sThis;
		$oReferenceObj = $oThis;
		if ($oThis === null) {
			$sSearchRelations = $sThis;
			$sReferenceRelation = $sOther;
			$oReferenceObj = $oOther;
		}

		$query = "	SELECT `".$sSearchRelations."`.*
FROM `".$sRelationTable."` AS t1
LEFT JOIN `".$sSearchRelations."` AS t2
ON t1.".$sSearchRelations."_id = t2.id
WHERE t1.".$sReferenceRelation."_id = :referencid";

		$aBind = array('referencid' => $oReferenceObj->getID());
		return parent::findBySql($sSearchRelations, $query, $aBind);
	}
}