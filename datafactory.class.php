<?php
/**
 * This class holds the connections to the database
 *
 * @package DataRecord
 * @author Robert Cabri <robert@dicabrio.com>
 * @copyright Robert Cabri
 */
class DataFactory {

	/**
	 * @var constant
	 */
	const C_DEFAULT_DB = 'default';

	/**
	 * @var DataFactory
	 */
	private static $oInstance = null;

	/**
	 * @var PDO
	 */
	private $databaseConnections = null;

	/**
	 * construction is not possible. This is prevented because this object is a singleton
	 *
	 * @return void
	 */
	private function __construct() {
		// disable instantiation
	}

	/**
	 * get the instance of the datafactory
	 *
	 * @return DataFactory
	 */
	public static function getInstance() {
		if (self::$oInstance === null) {
			self::$oInstance = new DataFactory();
		}
		return self::$oInstance;
	}

	/**
	 * Set the database connection for the Facade.
	 *
	 * @param PDO $connection
	 */
	public function getConnection($sDatabasename=null) {

		if ($this->databaseConnections == null) {
			throw new DataFactoryException('Database connection is not set');
		}

		if ($sDatabasename === null) {
			$sDatabasename = self::C_DEFAULT_DB;
		}

		if (!isset($this->databaseConnections[$sDatabasename])) {
			throw new DataFactoryException('Database connection is not set for '.$sDatabasename);
		}
			
		return $this->databaseConnections[$sDatabasename];
	}

	/**
	 * Add a PDO connection to the factory.
	 * It will always set the erromode of the PDO object to throw Exceptions
	 *
	 * @param $conn
	 * @param $sDatabaseName
	 * @return unknown_type
	 */
	public function addConnection(PDO $conn, $sDatabaseName = null) {
		if ($sDatabaseName === null) {
			$sDatabaseName = self::C_DEFAULT_DB;
		}

		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->databaseConnections[$sDatabaseName] = $conn;
	}

	public function beginTransaction($sDatabasename=null){
		$this->getConnection($sDatabasename)->beginTransaction();
	}

	public function commit($sDatabasename=null) {
		$this->getConnection($sDatabasename)->commit();
	}

	public function rollBack($sDatabasename=null) {
		$this->getConnection($sDatabasename)->rollBack();
	}

}

class DataFactoryException extends Exception {}