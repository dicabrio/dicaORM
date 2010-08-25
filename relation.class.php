<?php
/**
 *
 * @author robertcabri
 *
 */
class Relation extends DataRecord {

	public function __construct($sThis, $sOther, DataRecord $oThis, DataRecord $oOther=null) {
		parent::__construct($sThis.'_'.$sOther);
		$sThisIDString = $sThis.'_id';
		$sOtherIDString = $sOther.'_id';

		parent::addColumn('id', DataTypes::INT, false, true);
		parent::addColumn($sThisIDString, DataTypes::INT, false, true);
		parent::addColumn($sOtherIDString, DataTypes::INT, false, true);

		$this->setAttr($sThisIDString, $oThis->getID());
		if ($oOther instanceof DataRecord) {
			$this->setAttr($sOtherIDString, $oOther->getID());
		}
	}

	public function defineColumns() {
		return;
	}

	public static function add($sThis, $sOther, DataRecord $oThis, DataRecord $oOther) {
		$oRel = new Relation($sThis, $sOther, $oThis, $oOther);
		$oRel->save(true);
	}

	public static function remove($sThis, $sOther, DataRecord $oThis, DataRecord $oOther=null) {
		$aRel = Relation::get($sThis, $sOther, $oThis, $oOther);
		foreach ($aRel as $oRel) {
			$oRel->delete();
		}
	}

	public static function get($sThis, $sOther, DataRecord $oThis, DataRecord $oOther=null) {

		parent::setRetrieveRawData(true);

		$aBindings['thisid'] = $oThis->getID();
		$sRelationTable = $sThis.'_'.$sOther;
		$sQuery = "	SELECT	*
					FROM	`".$sRelationTable."`
					WHERE	".$sThis."_id = :thisid" ;

		if ($oOther instanceof DataRecord) {
			$sQuery .= " AND ".$sOther."_id = :otherid";
			$aBindings['otherid'] = $oOther->getID();
		}

		$aResult = parent::findBySql($sRelationTable, $sQuery, $aBindings);

		$aRelations = array();
		foreach ($aResult as $aRelationRecord) {
			$oRelation = new Relation($sThis, $sOther, $oThis, $oOther);
			$oRelation->setID($aRelationRecord['id']);
			$aRelations[] = $oRelation;
		}

		parent::setRetrieveRawData(false);
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

//		$query = "	SELECT t2.*
//					FROM `".$sRelationTable."` AS t1
//					LEFT JOIN `".$sSearchRelations."` AS t2
//					ON t1.".$sSearchRelations."_id = t2.id
//					WHERE t1.".$sReferenceRelation."_id = :referencid";
		$query = "	SELECT t2.*
					FROM `".$sRelationTable."` AS t1, `".$sSearchRelations."` AS t2
					WHERE t1.".$sSearchRelations."_id = t2.id AND t1.".$sReferenceRelation."_id = :referencid";

//		test($query);
//		test($oReferenceObj->getID());
		$aBind = array('referencid' => $oReferenceObj->getID());
		return parent::findBySql($sSearchRelations, $query, $aBind);
	}

	public static function getSingle($sThis, $sOther, DataRecord $oThis=null, DataRecord $oOther=null) {
		$aOther = self::getOther($sThis, $sOther, $oThis, $oOther);
		if (count($aOther) > 0) {
			return current($aOther);
		}

		return null;
	}
}