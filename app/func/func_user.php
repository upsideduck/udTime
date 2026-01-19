<?php
/********************************************************************
 *
 *	loginUser - process login request
 *			 
 *	Incomming: 	$username 
 *				$password
 *
 *	Outgoing : $result_arr
 *		key  : 0 - bool login success or not
 *			   1 - Message
 *
 ********************************************************************/
function loginUser($username, $password) {
	include("../includes/connection.php");
	$result_arr[0] = true;
	//Input Validations
	if($username == '') {
		$result_arr[0] = false;
		$result_arr[] = 'Username ID missing';
	}
	if($password == '') {
		$result_arr[0] = false;
		$result_arr[] = 'Password missing';
	}
	
	if($result_arr[0]) {
		//Create query
		$qry="SELECT * FROM userdb WHERE username='$username' AND password='".md5($password)."'";
		$result=mysqli_query($link, $qry);

		
		if(mysqli_num_rows($result) != 1) {
			$result_arr[0] = false;
			$result_arr[] = 'Username could not be found or unsername and password does not match';
		}
		else
		{
			$result_arr[] = 'Successful login';
			
		}
	}
	mysqli_close($link);
	return $result_arr;
}

/********************************************************************
 *
 *	loginUser - process login request
 *			 
 *	Incomming: 	$api_key
 *
 *	Outgoing : $result_arr
 *		key  : 0 - bool login success or not
 *			   1 - Message
 *
 ********************************************************************/
function verifyApikey($api_key) {
	include("../includes/connection.php");
	$result_arr[0] = true;
	//Input Validations
	if($api_key == '') {
		$result_arr[0] = false;
		$result_arr[] = 'API key missing';
	}

	if($result_arr[0]) {
		$api_key_hash = md5($api_key);
		//Create query
		$qry="SELECT * FROM apikeys WHERE key_hash='$api_key_hash'";
		$result=mysqli_query($link, $qry);

		
		if(mysqli_num_rows($result) != 1) {
			$result_arr[0] = false;
			$result_arr[] = 'API key is not valid';
		}
		else
		{
			$result_arr[] = 'Successful login';
			
		}
	}
	mysqli_close($link);
	return $result_arr;
}

/********************************************************************
 *
 *	registerUser - process register request
 *			 
 *	Incomming: $newUser
 *		key  : username 
 *			   dworkweek - hours work per week
 *			   password 
 *			   cpassword - copy of password
 *			   date - register date
 *
 *	Outgoing : $arror_arr
 *		key  : 0 - bool login success or not
 *			   1 - message
 *
 ********************************************************************/
function registerUser($newUser) {
	include(__SITE_BASE__."/includes/connection.php");
	$result_arr = array();
	$result_arr[0] = true;
	//Input Validations
	if($newUser['username'] == '') {
		$result_arr[] = 'Username missing';
		$result_arr[0] = false;
	}
	if($newUser['ww'] == '') {
		$result_arr[] = 'Workweek missing or not valid';
		$result_arr[0] = false;
	}
	if($newUser['password'] == '') {
		$result_arr[] = 'Password missing';
		$result_arr[0] = false;
	}
	if($newUser['cpassword'] == '') {
		$result_arr[] = 'Confirm password missing';
		$result_arr[0] = false;
	}
	if( strcmp($newUser['password'], $newUser['cpassword']) != 0 ) {
		$result_arr[] = 'Passwords do not match';
		$result_arr[0] = false;
	}
	
	//Check for duplicate login ID
	if($login != '') {
		$qry = "SELECT * FROM userdb WHERE username='".$newUser['username']."'";
		$result = mysqli_query($link, $qry);
		if($result) {
			if(mysqli_num_rows($result) > 0) {
				$result_arr[] = 'Username already in use';
				$result_arr[0] = false;
			}
			@mysql_free_result($result);
		}
		
	}
	
	if($result_arr[0]){	
		//Create INSERT query
		$qry = "INSERT INTO userdb(username, dworkweek, registerdate, password) VALUES('".$newUser['username']."',".$newUser['ww'].",".$newUser['date'].",'".md5($newUser['password'])."')";
		$result = @mysqli_query($link, $qry);
		if(!$result) {
			$result_arr[] = 'Something went wrong when user was added, please contact admin';
			$result_arr[0] = false;
		}
	}
	
	mysqli_close($link);
	return $result_arr;
}
/********************************************************************
 *
 *	uploadAvatar - upload avatar to mysql
 *			 
 *	Incomming: 
 *
 *	Outgoing : $arror_arr
 *		key  : 0 - bool login success or not
 *			   1 - message
 *
 ********************************************************************/
function uploadAvatar(){
	include(__SITE_BASE__."/includes/connection.php");
	if(is_uploaded_file($_FILES['userfile']['tmp_name'][0])) {
		
		
	    // check the file is less than the maximum file size
	    if($_FILES['userfile']['size'][0] < 20000) {
		    // prepare the image for insertion
		    $imgData =addslashes (file_get_contents($_FILES['userfile']['tmp_name'][0]));
	  
	  	    $sql = "UPDATE userdb SET
	    	        avatar = '$imgData' WHERE member_id=".$_SESSION['SESS_MEMBER_ID'];

		    if(!mysqli_query($link, $sql)) echo 'Unable to upload file';   
		} else {
	     	// if the file is not less than the maximum allowed, print an error
		     echo
		    '<div>File exceeds the Maximum File limit</div>
	    	<div>Maximum File limit is '.$maxsize.'</div>
	      	<div>File '.$_FILES['userfile']['name'].' is '.$_FILES['userfile']['size'].' bytes</div>
	      	<hr />';
		}
		

	}
	mysqli_close($link);
}
/********************************************************************
 *
 *	getProfile - get all profile info
 *			 
 *	Incomming: 
 *
 *	Outgoing : profile object
 *
 ********************************************************************/
function getProfile(){
	include(__SITE_BASE__."/includes/connection.php");
	$resultuser = mysqli_query($link, "SELECT * FROM userdb WHERE username = '". $_SESSION['SESS_USERNAME']."'");
	$profile = mysqli_fetch_object($resultuser);

	mysqli_close($link);
	return $profile;
}


?>