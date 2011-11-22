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
		$result=mysql_query($qry);

		
		if(mysql_num_rows($result) != 1) {
			$result_arr[0] = false;
			$result_arr[] = 'Username could not be found or unsername and password does not match';
		}
		else
		{
			$result_arr[] = 'Successful login';
			
		}
	}
	return $result_arr;
}

/********************************************************************
 *
 *	registerUser - process register request
 *			 
 *	Incomming: $newUser
 *		key  : username 
 *			   workweek - hours work per week
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
		$result = mysql_query($qry);
		if($result) {
			if(mysql_num_rows($result) > 0) {
				$result_arr[] = 'Username already in use';
				$result_arr[0] = false;
			}
			@mysql_free_result($result);
		}

	}
	
	if($result_arr[0]){	
		//Create INSERT query
		$qry = "INSERT INTO userdb(username, workweek, registerdate, password) VALUES('".$newUser['username']."',".$newUser['ww'].",".$newUser['date'].",'".md5($newUser['password'])."')";
		$result = @mysql_query($qry);
		if(!$result) {
			$result_arr[] = 'Something went wrong when user was added, please contact admin';
			$result_arr[0] = false;
		}
	}
	
	return $result_arr;
}
?>