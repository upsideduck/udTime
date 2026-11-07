<?php
/********************************************************************
 *
 *	start_session - initiate the logged in session
 *			 
 *	Incomming: $data 
 *		desc : result of member query
 *
 *	Outgoing : none
 *
 ********************************************************************/ 
function start_session($data) {
	include(__SITE_BASE__."/includes/connection.php");
	session_regenerate_id();
	$member = mysqli_fetch_assoc($data);
	$_SESSION['SESS_MEMBER_ID'] = $member['member_id'];
	$_SESSION['SESS_USERNAME'] = $member['username'];
	$_SESSION['SESS_ACTIVE_PERIOD'] = $member['activeperiod'];	
	$_SESSION['SESS_ACTIVE_TYPE'] = $member['activetype'];	
	mysqli_close($link);
		
}

/********************************************************************
 *
 *	close_session - end the logged in session
 *			 
 *	Incomming: none 
 *
 *	Outgoing : none
 *
 ********************************************************************/ 
function close_session() {
	// Initialize the session.
	// If you are using session_name("something"), don't forget it now!
	
	// Unset all of the session variables.
	$_SESSION = array();
	
	// If it's desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (ini_get("session.use_cookies")) {
	    $params = session_get_cookie_params();
	    setcookie(session_name(), '', time() - 42000,
	        $params["path"], $params["domain"],
	        $params["secure"], $params["httponly"]
	    );
	}
	
	// Finally, destroy the session.
	session_destroy();
}
?>