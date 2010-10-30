<?
class DBDriven
{
	private $tableName;
	
	public function __construct($tableName)
	{
		$this->setTableName($tableName);
	}
	
	public function __toString()
	{
		return serialize($this);
	}
	
	protected static function getConn()
	{
		global $conn;
		return $conn;
	}
	
	protected static function query($query)
	{
		global $executed_queries;
		$executed_queries[] = $query;
		return mysql_query($query, self::getConn());
	}
	
	protected function getTableName()
	{
		return $this->tableName;
	}
	
	private function setTableName($tableName)
	{
		$this->tableName = $tableName;
	}
}
?>