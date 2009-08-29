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
 *   PRIMARY KEY  (`id`)
 * ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
 *
 *
 * class User extends DataRecord {
 *
 *	 	public function __construct($iID = null) {
 *			parent::__construct(__CLASS__, $iID);
 *		}
 *
 *		// define the method to define the columns to map to the db
 *		protected function defineColums() {
 *			parent::addColumn('id', DataType::INT, false, false);
 *			parent::addColumn('name', DataType::VARCHAR, 255, false);
 *		}
 *
 *		// its optional to create getters/setters
 *		// it will come in handy when using an IDE like eclipse of netbeans
 *		public function getName() {
 *			return $this->name;
 *		}
 *
 *		public function setName($sName) {
 *			$this->name = $sName;
 *		}
 *
 * }
 *
 * $oDatabase = new PDO('mysql:dbname=db_name;host=localhost', 'db_user', 'db_pass');
 * DataFacade::setConnection($oDatabase);
 *
 * $oUser = new User();
 * $oUser->setName('Robert Cabri');
 * $oUser->save();
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
	public function __construct($table, $id=null, $sConnection=null) {

		if ($sConnection !== null) {
			self::$sDBConnectionName = $sConnection;
		}

		$this->table = strtolower($table);

		$this->oColumns = new ColumnAggr();
		$this->defineColumns();

		$this->id = intval($id);

		if ($this->id > 0) {
			$this->load();
		}
	}

	/**
	 * define the attributes for the given class that will map to
	 * the database
	 *
	 * @return void
	 */
	abstract protected function defineColumns();

	/**
	 * define the relations and map them to properties
	 *
	 * @return void
	 */
	protected function defineRelations() {
		// it could be that no relations will ever be defined
	}

	protected function setRetrieveRawData($bRaw) {
		self::$bRaw = $bRaw;
	}

	public function getID() {
		return $this->id;
	}

	public function setID($iID) {
		$this->id = $iID;
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

		if ($this->id > 0) {
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

		$this->id = $oDatabaseHandler->lastInsertId();
	}

	private function update() {
		if (!$this->id) {
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
	private function load() {
		if ($this->oColumns->count()) {
			$sql = "SELECT ".$this->attributes->getAttributesString()." FROM `".$this->table."` WHERE id = :id";

			$oDatabaseHandler = self::getConnection();
			$statement = $oDatabaseHandler->prepare($query);
			$statement->bindParam(':id', intval($this->id));
			$statement->execute();

			$row = $statement->fetch(PDO::FETCH_ASSOC);
			// load the attribute values
			if ($row === false) {
				throw new RecordException('Record of '.get_class($this).' was not found with this id:'.$this->id);
			}

			if (count($row) > 0) {
				foreach ($row as $attribute => $value) {
					if( $attribute == 'id' ) {
						continue;
					}

					$this->$attribute = $value;
				}
			}

			$statement = null;
		}
	}

	/**
	 * @return void
	 */
	public function delete() {
		if ($this->id == 0) {
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
	 *
	 * @param string $columnName
	 * @param const $dataType data type
	 * @param int $size length of the string/number
	 * @param bool $null false = not null / true = null
	 */
	protected function addColumn($columnName, $dataType, $size=null, $null=false) {
		$attr = new Attribute($columnName);
		$attr->setFormattedName(str_replace('_', ' ', $columnName));
		$attr->setType($dataType);
		$attr->setSize($size);
		$attr->setNullable($null);

		// set the is_fk and fk_table
		if (preg_match('/^(.*)_id$/', $columnName, $matches)) {
			$attr->setForeignKey(true);
			$attr->setForeignKeyTable($matches[1]);
			$attr->setJoinType($joinType);
		}

		$this->oColumns->add($attr);
	}

	/**
	 * @param string $oColumnName
	 * @return mixed
	 */
	protected function __get($oColumnName) {
		return $this->oColumns->$oColumnName;
	}


	/**
	 * @param string $oColumnName
	 * @param mixed $value
	 */
	protected function __set($oColumnName, $value) {
		$this->oColumns->$oColumnName = $value;
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
				$oTmpObject->$sKey = $sValue;
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

		$sql = "SELECT ".$selection." FROM `".strtolower($sTableName)."` ";

		if ($conditions !== null) {
			$sql .= " WHERE ".$conditions->getQuery();
			$aBindings = array_merge($aBindings, $conditions->toArray());
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