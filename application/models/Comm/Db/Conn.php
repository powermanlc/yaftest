<?php
class Comm_Db_Conn 
{
	private static $pools = array(); //存储mysql连接，实现mysql_connect的单例
	
	public static function getDb($alias = NULL) {
		if (is_null($alias)) {
			$name = 'test';
			
		}
		$db = Comm_Db_Mysql();
	
	}
}
