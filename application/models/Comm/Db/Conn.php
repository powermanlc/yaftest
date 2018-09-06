<?php
class Comm_Db_ConnModel 
{
	private static $pools = array(); //存储mysql连接，实现mysql_connect的单例
	
	public static function getDb($alias = NULL) {
		if (is_null($alias)) {
			$name = 'test';
			
		}
		//$db = Comm_Db_Mysql();
        //$config = Ekw_DBC::getConf($name);
        $config = Yaf_Registry::get('db')->toArray()['test']['dw']; 
        $item = 'w';
        $item = $config['dbname'] . ':d' . $item;
        if(array_key_exists($item, self::$pools)) {
            file_put_contents("/home/www/lc.log", '1111', FILE_APPEND);
            return self::$pools[$item];
        }
        $db = new Comm_Db_MysqlModel($config['host'], $config['port'], $config['uname'], $config['password'], $config['dbname'], $config['charset']);
        // self::$pools[$item] = $db;
        self::$pools[$item] = $db;
        return $db;
                                        

	
	}
}
