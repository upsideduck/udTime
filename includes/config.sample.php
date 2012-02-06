<?php
	define('DB_HOST', '------------');	
	define('DB_USER', '------------');
  	define('DB_PASSWORD', '------------');
  	define('DB_DATABASE', '------------');
  	
    
  	define(__SITE_BASE__, '------------');
  	
	
  
	$xml_output = "";

	class formValues {
		
		public $description;
		
		public $script;
		
		public $name;
		
		public $choices = array();
	
	}
  
	
		
	
	//Connect to mysql server
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	
	if(!$link) {
		
		die('Failed to connect to server: ' . mysql_error());
	
	}
	  
	
	
	//Select database
	
	$db = mysql_select_db(DB_DATABASE);
	
	if(!$db) {
		
		die("Unable to select database");
	
	}

	
	$resultuser = mysql_query("SELECT * FROM userdb WHERE username = '". $_SESSION['SESS_USERNAME']."'");
	
	$user = mysql_fetch_object($resultuser);
	
	
	date_default_timezone_set("$user->timezone");
	
	
	//Array to store validation errors
	
	$errmsg_arr = array();
	
	
	//Validation error flag
	
	$errflag = false;
	
?>