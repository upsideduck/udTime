<?php

      // check if something has been change outside this session
      if(isset($_SESSION["SESS_ACTIVE_PERIOD"]) && $user->activeperiod != $_SESSION["SESS_ACTIVE_PERIOD"]) updateSession($user);
	
	?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
<?php
switch (__SCRIPT_NAME__) {
	case "index" :
		include("includes/headers/head_login.php");
		include("includes/headers/head_index.php");
		break;
	case "summary" :
		include("includes/headers/head_summary.php");
		break;
	case "edit" :
		include("includes/headers/head_edit.php");
		break;
}
?>
<link href="css/main.css" rel="stylesheet" type="text/css" />
<link href="css/objects.css" rel="stylesheet" type="text/css" />
<link href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" type="text/css" />
<title>UDTime</title>
</head>
<body>
<div class="page">
<div class="header"><div id="logo">&nbsp;</div></div>
<div class="pagebody">
