<script type="text/javascript" src="js/jquery.maskedinput-1.3.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
<?php 
	if(isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) != '')) {
?>
<script type="text/javascript" src="js/clock.js"></script>
<script type="text/javascript" src="js/notifications.js"></script>
<script type="text/javascript" src="js/main-form.js"></script>
<script type="text/javascript" src="js/projects.js"></script>
<?php
}
?>
<link href="css/timePicker.css" rel="stylesheet" type="text/css" />
