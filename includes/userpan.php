<?php
require_once('config.php');
require_once(__SITE_BASE__ . 'func/func_misc.php');		
require_once(__SITE_BASE__ . 'func/func_user.php');		

	$profile = getProfile();
       
    echo "<div class='ui-widget ui-widget-content ui-corner-all userpan clearfix'>";
    echo "<div class='ui-widget-header ui-corner-all'>User</div>";

	echo "<img class='avatar' src='includes/avatar.php?MID=".$_SESSION['SESS_MEMBER_ID']."'>";
	echo "<div class='profiletag'><span class='profilehead'>Username: </span>$profile->username</div>";
	echo "<div class='profiletag'><span class='profilehead'>Name: </span>$profile->firstname $profile->lastname</div>";
	echo "<div class='profiletag'><span class='profilehead'>Register: </span>".Date("Y-m-d",$profile->registerdate)."</div>";
	echo "<div class='profiletag'><span class='profilehead'>Work week: </span>".timestampToTime($profile->dworkweek)." </div>";
	echo "<div class='profiletag'><span class='profilehead'>Offset: </span>".timestampToTime($profile->offset)." </div>";
	echo "<div class='clear'></div>";
 	echo "</div>";

?>
</p>