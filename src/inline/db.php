<?php

// experimental...

#inline db_initx
function db_init($dsn,$user,$pass){
#db_initx_beg
	$_pdo = new PDO($dsn,$user,$pass,[
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::MYSQL_ATTR_LOCAL_INFILE => 1,
	]);
#db_initx_end
}

#inline db_close ()
function db_close(){
#db_close_beg
	if($_pdo && $_pdo->inTransaction()){
		$_pdo->rollback();
	}
	$_pdo = null;
	$_sth = null;
#db_close_end
}

db_init('{{db_dsn}}','{{db_user}}','{{db_pass}}');
