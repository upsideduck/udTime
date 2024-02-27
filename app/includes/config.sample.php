 <?php
	  define('DB_HOST', '------------');	
	  define('DB_USER', '------------');
		define('DB_PASSWORD', '------------');
		define('DB_DATABASE', '------------');
   
		define("__SITE_BASE__", '------------');
		define("__SITE_URI__", '------------');
		
  
		// Autoload classes as they are needed
		spl_autoload_register(function ($class) {
		  include __SITE_BASE__.'classes/' . $class . '.class.php';
	  });
  
	  class formValues {
		  public $description;
		  public $script;
		  public $name;
		  public $choices = array();
	  }
	  error_reporting(E_ALL ^ E_NOTICE); 
	  include('connection.php');
  
	  //Select database
	  // $db = mysql_select_db(DB_DATABASE);
	  // if(!$db) {
	  // 	die("Unable to select database");
	  // }
	  if( defined('__SCRIPT_NAME__') && constant('__SCRIPT_NAME__') == "logout"){
		  
		  
	  }elseif(isset($_SESSION['SESS_USERNAME'])) {
		  $resultuser = mysqli_query($link, "SELECT member_id,username,timezone,dworkweek,activeperiod,activetype,offset,registerdate,statsstartdate,admin FROM userdb WHERE username = '". $_SESSION['SESS_USERNAME']."'");
		  $user = mysqli_fetch_object($resultuser);
  
		  date_default_timezone_set("$user->timezone");
	  }elseif(isset($_SERVER['HTTP_X_AUTHENTIK_USERNAME'])) {
		  $basename = constant('__SITE_BASE__')."func/func_session.php";
		  require_once($basename);
		  $username = $_SERVER['HTTP_X_AUTHENTIK_USERNAME'];
		  $qry="SELECT * FROM userdb WHERE username='$username'";
		  $result=mysqli_query($link, $qry);
		  if(mysqli_num_rows($result) == 1) {
			  start_session($result);
  
			  $resultuser = mysqli_query($link, "SELECT member_id,username,timezone,dworkweek,activeperiod,activetype,offset,registerdate,statsstartdate,admin FROM userdb WHERE username = '". $_SERVER['HTTP_X_AUTHENTIK_USERNAME']."'");
			  $user = mysqli_fetch_object($resultuser);
  
			  date_default_timezone_set("$user->timezone");
		  }
	  }
	  //Array to store validation errors
	  $errmsg_arr = array();
	  
	  //Validation error flag	
	  $errflag = false;
	  
  ?>