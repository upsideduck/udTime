<?php
require_once(__SITE_BASE__ . 'func/func_misc.php');		
require_once(__SITE_BASE__ . 'func/func_user.php');
require_once(__SITE_BASE__ . 'func/func_fetch.php');				

	$profile = getProfile();
	$today = fetchWorkAndBreakTime(fetchStartEndTime("today"));
	$thisweek = fetchWorkAndBreakTime(fetchStartEndTime("thisweek"));
	$thismonth = fetchWorkAndBreakTime(fetchStartEndTime("thismonth"));

    echo "<div class='ui-widget ui-widget-content ui-corner-all userpan clearfix'>";
    echo "<div class='ui-widget-header ui-corner-all'>User</div>";
	echo "<div class='content-container'>";
	echo "<img class='avatar' src='includes/avatar.php?MID=".$_SESSION['SESS_MEMBER_ID']."'>";
	echo "<div class='profiletag'><span class='profilehead'>Username: </span>$profile->username</div>";
	echo "<div class='profiletag'><span class='profilehead'>Name: </span>$profile->firstname $profile->lastname</div>";
	echo "<div class='profiletag'><span class='profilehead'>Register: </span>".Date("Y-m-d",$profile->registerdate)."</div>";
	echo "<div class='profiletag'><span class='profilehead'>Work week: </span>".timestampToTime($profile->dworkweek)." </div>";
	echo "<div class='profiletag'><span class='profilehead'>Offset: </span>".timestampToTime($profile->offset)." </div>";
	echo "<div class='clear border-top'></div>";
	echo "<div class='profiletag'><span class='profilehead'>Today: </span>".timestampToTime($today['worktime'])."</div>";
	echo "<div class='profiletag'><span class='profilehead'>This week: </span>".timestampToTime($thisweek['worktime'])."</div>";
	echo "<div class='profiletag'><span class='profilehead'>This month: </span>".timestampToTime($thismonth['worktime'])."</div>";
 	echo "</div>";
	echo "</div>";
?>
</p>
