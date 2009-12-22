<?php
/**
 * This class is the base of all models. This is to be extended when creating a model
 * You don't have to worry about queries
 *
 * usage:
 *
 * for this example you need a table called "user"
 *
 * CREATE TABLE IF NOT EXISTS `user` (
 *   `id` int(11) NOT NULL auto_increment,
 *   `name` varchar(255) NOT NULL default '',
 *   `other` varchar(255) NOT NULL default '',
 *   PRIMARY KEY  (`id`)
 * ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
 *
 * include('datarecord.class.php');
 * include('attribute.class.php');
 * include('columnaggr.class.php');
 * include('datafactory.class.php');
 * include('datatypes.class.php');
 * include('querybuilder.class.php');
 *
 *
 * class Test extends DataRecord {
 *
 * 	public function __construct($iID = null) {
 * 		parent::__construct(__CLASS__, $iID);
 * 	}
 *
 * 	// define the method to define the columns to map to the db
 * 	protected function defineColumns() {
 * 		parent::addColumn('id', DataTypes::INT, false, false);
 * 		parent::addColumn('name', DataTypes::VARCHAR, 255, false);
 * 		parent::addColumn('other', DataTypes::VARCHAR, 255, false);
 * 	}
 *
 * 	// its optional to create getters/setters
 * 	// it will come in handy when using an IDE like eclipse of netbeans
 * 	public function getName() {
 * 		return $this->name;
 * 	}
 *
 * 	public function setName($sName) {
 * 		$this->name = $sName;
 * 	}
 *
 * 	public function getOther() {
 * 		$this->other;
 * 	}
 *
 * 	public function setOther($sOther) {
 * 		$this->other = $sOther;
 * 	}
 *
 * 	public static function findAll() {
 * 		return parent::findAll(__CLASS__, self::ALL);
 * 	}
 * }
 *
 * try {
 * 	$oDatabase = new PDO('mysql:dbname=##db_name##;host=localhost', '##db_user##', '##db_pass##');
 * 	$oData = DataFactory::getInstance();
 * 	$oData->addConnection($oDatabase, 'default');
 * 	$oData->beginTransaction();
 *
 * 	$aAll = Test::findAll();
 * 	foreach ($aAll as $oTesting) {
 * 		$oTesting->delete();
 * 	}
 *
 * 	echo $iRandomValue = mt_rand(1,10);
 *
 * 	$oTest = new Test();
 * 	$oTest->setName('Robert Cabri'.$iRandomValue);
 * 	$oTest->save();
 *
 * 	$oTest->setName($iRandomValue.'Robert Cabri');
 * 	$oTest->save();
 *
 * 	$oData->commit();
 * } catch (Exception $e) {
 * 	echo $e->getMessage();
 * 	$oData->rollBack();
 * }
 *
 *
 *
 *
 * @package DataRecord
 * @author Robert Cabri <robert@dicabrio.com>
 * @copyright Robert Cabri
 */
abstract class DataRecord {

	const ALL = "*";

	/**
	 * @var ColumnAggr
	 */
	private $oColumns = null;

	/**
	 * @var string
	 */
	private $table = null;

	/**
	 * @var boolean
	 */
	private $isModified = false;

	/**
	 * @var boolean
	 */
	private static $bRaw = false;

	/**
	 * @var boolean
	 */
	public static $debug = true;

	/**
	 * @var string
	 */
	private static $sDBConnectionName = 'default';

	/**
	 * Constructor of the datarecord. Datarecord itself maynot be initiated. "Abstract" prevents that.
	 * we should extend this and fill given properties and let DataRecord handle most of your
	 * deleting, saving and updating
	 *
	 * @param string $table
	 * @param int $id
	 */
	protected function __construct($table, $id=null, $sConnection=null) {

		if ($sConnection !== null) {
			self::$sDBConnectionName = $sConnection;
		}

		$this->table = strtolower($table);

		$this->oColumns = new ColumnAggr();
		$this->addColumn('id', DataTypes::INT, false, true);
		$this->defineColumns();

		$this->setAttr('id', intval($id));
		
		$this->loadRecord();
	}

	/**
	 * define the attributes for the given class that will map to
	 * the database
	 *
	 * @return void
	 */
	abstract protected function defineColumns();

	/**
	 * define validations for the object.
	 * It is not defined abstract because it is optional. If you want to use it override it.
	 * 
	 * @returnn void
	 */
	protected function defineValidations() {

	}

	/**
	 * define the relations and map them to properties
	 *
	 * @return void
	 */
	protected function defineRelations() {
		// it could be that no relations will ever be defined
	}

	/**
	 * retrieve rawdata (arrays)
	 * 
	 * @param bool $bRaw
	 */
	protected function setRetrieveRawData($bRaw) {
		self::$bRaw = $bRaw;
	}

	public function getID() {
		return (int)$this->getAttr('id');
	}

	public function setID($iID) {
		$this->setAttr('id', $iID);
	}

	public function getTable() {
		return $this->table;
	}

	/**
	 * @return ColumnAggr
	 */
	public function getColumns() {
		return $this->oColumns;
	}

	/**
	 * save the object. if saved return true else return false
	 * @return boolean
	 */
	public function save() {
		if ($this->oColumns->isModified() === false) {
			return false;
		}

		if ($this->getAttr('id') > 0) {
			$this->update();
		} else {
			$this->insert();
		}

		return true;
	}

	private function insert() {

		$oQueryBuilder = new QueryBuilder('INSERT', $this);

		$oDatabaseHandler = self::getConnection();
		$statement = $oDatabaseHandler->prepare($oQueryBuilder->getQuery(true));
		$statement->execute($oQueryBuilder->getPreparedValues());

		if (!$statement) {
			$errorInfo = $statement->errorInfo();
			throw new RecordException($errorInfo[2]);
		}

		$this->setAttr('id', $oDatabaseHandler->lastInsertId());
	}

	private function update() {
		if (!$this->getAttr('id')) {
			throw new RecordException($this->table." object needs an ID to update()");
		}

		$oQueryBuilder = new QueryBuilder('UPDATE', $this);

		$oDatabaseHandler = self::getConnection();
		$statement = $oDatabaseHandler->prepare($oQueryBuilder->getQuery(true));
		$statement->execute($oQueryBuilder->getPreparedValues());

		if (!$statement) {
			$errorInfo = $statement->errorInfo();
			throw new RecordException($errorInfo[2]);
		}

	}


	/**
	 * Load the object with data from the database
	 *
	 */
	private function loadRecord() {

		if ($this->getAttr('id') == 0) {
			return;
		}


		if ($this->oColumns->count()) {
			$oQueryBuilder = new QueryBuilder('SELECT', $this);

			$oDatabaseHandler = self::getConnection();
			$statement = $oDatabaseHandler->prepare($oQueryBuilder->getQuery(true));
			$statement->execute($oQueryBuilder->getPreparedValues());

			$row = $statement->fetch(PDO::FETCH_ASSOC);
			// load the attribute values
			if ($row === false) {
				throw new RecordException('Record of '.get_class($this).' was not found with this id:'.$this->getAttr('id'));
			}

			if (count($row) > 0) {
				foreach ($row as $attribute => $value) {
					$this->setAttr($attribute, $value);
				}
			}

			$this->oColumns->clearModifiedStatus();

			$statement = null;
		}
	}

	/**
	 * @return void
	 */
	public function delete() {
		if ($this->getAttr('id') == 0) {
			throw new RecordException($this->table." object has no ID, can't delete it");
		}

		$oQueryBuilder = new QueryBuilder('DELETE', $this);


		$oDatabaseHandler = self::getConnection();

		$statement = $oDatabaseHandler->prepare($oQueryBuilder->getQuery(true));
		$statement->execute($oQueryBuilder->getPreparedValues());

		if (!$statement) {
			$errorInfo = $statement->errorInfo();
			throw new RecordException($errorInfo[2]);
		}

		return;
	}

	/**
	 * Define a column for the object. Specify the right datatype, size and if it is required (null/ not null)
	 * TODO add validators
	 *
	 * @param string $columnName
	 * @param const $dataType data type
	 * @param int $size length of the string/number
	 * @param bool $null false = not null / true = null
	 */
	protected function addColumn($columnName, $dataType, $size=null, $null=false) {
		$attr = new Column($columnName);
		$attr->setFormattedName(str_replace('_', ' ', $columnName));
		$attr->setType($dataType);
		$attr->setSize($size);
		$attr->setNullable($null);

		// set the is_fk and fk_table
		if (preg_match('/^(.*)_id$/', $columnName, $matches)) {
			$attr->setForeignKey(true);
			$attr->setForeignKeyTable($matches[1]);
		}

		$this->oColumns->add($attr);
	}

	/**
	 *
	 * @param <type> $oValidator
	 */
	protected function addValidator(ColumnValidator $oValidator) {
		
	}

	/**
	 * @param string $columnname
	 * @return string
	 */
	protected function getAttr($columnname) {
		return $this->oColumns->$columnname;
	}


	/**
	 * @param string $oColumnName
	 * @param mixed $value
	 * @return DataRecord
	 */
	public function setAttr($oColumnName, $value) {
		$this->oColumns->$oColumnName = $value;

		return $this;
	}

	private static function getConnection($sConnection=null) {

		if ($sConnection === null) {
			$sConnection = self::$sDBConnectionName;
		}

		return DataFactory::getInstance()->getConnection($sConnection);
	}

	/**
	 * Works like findAll, but requires a complete SQL string.
	 *
	 * @param string $sClassName
	 * @param string $query
	 * @param array $bind
	 * @param string $sConnectionName
	 *
	 * @return array
	 */
	protected static function findBySql($sClassName, $sQuery, $aBind=array(), $sConnectionName = null) {
		$aResulObjects = array();

		$oDatabaseHandler = self::getConnection($sConnectionName);

		$oStatement = $oDatabaseHandler->prepare($sQuery);
		$oStatement->execute($aBind);

		if (!$oStatement) {
			$aErrorInfo = $oStatement->errorInfo();
			throw new RecordException($aErrorInfo[2]);
		}

		if (self::$bRaw === true) {
			return $oStatement->fetchAll();
		}

		while ($aRow = $oStatement->fetch()) {
			$oTmpObject = new $sClassName();
			foreach ($aRow as $sKey => $sValue) {
				$oTmpObject->setAttr($sKey, $sValue);
			}

			$aResulObjects[] = $oTmpObject;
		}

		return $aResulObjects;

	}

	/**
	 * Find all
	 *
	 * Returns an array of all the objects that could be instantiated from
	 * the associated table in the database.
	 *
	 * @access public
	 * @param string $tableName name of table to execute query against
	 * @param array $columns the columns you like to retreive
	 * @param Criteria $conditions conditions to apply in query
	 * @param string $orderings order to return results with
	 * @param string $limit any limit to apply to query
	 * @return object first object that matches "conditions" and "orderings"
	 */
	protected static function findAll($sTableName, $columns, Criteria $conditions=null, $orderings=null, $limit=null, $sDbConnectionName = null) {
		// construct the sql
		$selection = '*';
		$aBindings = array();

		if (is_array($columns) && count($columns) > 0) {
			$selection = implode(',', $columns);
		} else if (is_array($columns) && count($columns) == 1) {
			$selection = $columns[0];
		}

		// TODO: make the querybuilder responsible for create the query
		$sql = "SELECT ".$selection." FROM `".strtolower($sTableName)."` ";

		if ($conditions !== null) {
			$sql .= " WHERE ".$conditions->getQuery();
			$aBindings = array_merge($aBindings, (array) $conditions);
		}

		if ($orderings !== null) {
			$sql .= " ORDER BY ".$orderings;
		}

		if ($limit !== null) {
			$sql .= " LIMIT ".$limit;
		}

		return self::findBySql($sTableName, $sql, $aBindings, $sDbConnectionName);
	}

}


class RecordException extends Exception {}