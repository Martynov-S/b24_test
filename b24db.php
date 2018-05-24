<?php
require_once 'b24config.php';

class DB {
	public static $b24cfg = array();
	
	public static function loadConfig($b24cfg) {
		self::$b24cfg = $b24cfg;
	}
	
	public static function setConnection() {
		$options = array(
			PDO::MYSQL_ATTR_INIT_COMMAND =>	'SET NAMES ' . self::$b24cfg['charset']
			);
			
		$dsn = self::$b24cfg['driver'] . ':host=' . self::$b24cfg['host'] . ';port=' . self::$b24cfg['port'] . ';dbname=' . self::$b24cfg['dbname'];
		
		$pdo = new PDO($dsn,self::$b24cfg['username'],self::$b24cfg['password'],$ptions);
		return $pdo;
	}
	
	public static function getValue($key) {
		if (array_key_exists($key, self::$b24cfg)) {
			return self::$b24cfg[$key];
		}
	}
}

DB::loadConfig($b24cfg);
?>