<?php
	/**
	*
	* скрипт для установки приложения в Битрикс24
	*
	*/
	
	require_once 'b24db.php';

	// проверка откуда пришел запрос
	if (isset($_REQUEST['DOMAIN'], $_REQUEST['AUTH_ID'], $_REQUEST['REFRESH_ID'], $_REQUEST['AUTH_EXPIRES'])) {
		$domain_pos = strpos($_SERVER['HTTP_REFERER'], $_REQUEST['DOMAIN']);
		if ($domain_pos !== false) {
			$db = DB::setConnection();
			$create_query = 'CREATE TABLE IF NOT EXISTS b24_portal_auth (
			domain VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci UNIQUE KEY,
			auth_id VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci,
			auth_expires BIGINT(8) UNSIGNED,
			refresh_id VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci
			) ENGINE = MyISAM;';
			
			$db->exec($create_query);
		}
	}
?>