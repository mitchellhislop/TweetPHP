<?
require_once(DOC_CLASS.'DBDriven.class.php');


class NonArchivedTable extends DBDriven
{
	private $id;
	
	public function __construct($tableName, $id)
	{
		parent::__construct($tableName);
		$this->setId($id);
	}
	public function getCreated()
	{
		return $this->queryForValue('created');
	}
	public function getId()
	{
		return $this->id;
	}
	
	public function isActive()
	{
		if($this->queryForValue('active') == '1')
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function queryForValue($value)
	{
		$tablename = mysql_real_escape_string($this->getTableName());
		$value = mysql_real_escape_string($value);
		$query = "SELECT `$value` AS `val` FROM `$tablename` WHERE `$tablename`.`id_$tablename` = '".mysql_real_escape_string($this->getId())."'";
		
		$res = self::query($query);
		if(mysql_num_rows($res) == '1')
		{
			$res = mysql_fetch_assoc($res);
		}
		else
		{
			return false;
		}
		return stripslashes(stripslashes($res['val']));
	}
	
	protected function queryForValueBoolean($value)
	{
		if($this->queryForValue($value) == '1')
			return true;
		return false;
	}
	
	private function setId($id)
	{
		$this->id = $id;
	}
	
	public function toggleActive()
	{
		return $this->toggleBooleanValue('active');
	}
		
	protected function toggleBooleanValue($field)
	{
		$value = '1';
		if($this->queryForValueBoolean($field) == '1')
		{
			$value = '0';
		}
		return $this->updateValue($field, $value);
	}
	
	protected function updateValue($field, $value)
	{
		$tablename = mysql_real_escape_string($this->getTableName());
		$value = mysql_real_escape_string($value);
		
		$query = "UPDATE `$tablename` SET `$tablename`.`".mysql_real_escape_string($field)."` = '".mysql_real_escape_string($value)."' WHERE `$tablename`.`id_$tablename` = '".mysql_real_escape_string($this->getId())."'\n\n";
		if(self::query($query))
		{
			return true;
		}
		return false;
	}
}
?>
