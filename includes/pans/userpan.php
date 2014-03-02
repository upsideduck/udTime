<?php
require_once(__SITE_BASE__ . 'func/func_misc.php');		
require_once(__SITE_BASE__ . 'func/func_user.php');
require_once(__SITE_BASE__ . 'func/func_fetch.php');				

	$profile = getProfile();
	$today = fetchWorkAndBreakTime(fetchStartEndTime("today"));
	$thisweek = fetchWorkAndBreakTime(fetchStartEndTime("thisweek"));
	$thismonth = fetchWorkAndBreakTime(fetchStartEndTime("thismonth"));


	echo "<div class='row-fluid'><div class='span12'>";
	echo "<b>User</b>";
	echo "<table class='table  table-striped'>";
	echo "<tr><th class='profiletag'><span class='profilehead'>Username: </span></th><td>$profile->username</td></tr>";
	echo "<tr><th class='profiletag'><span class='profilehead'>Name: </span></th><td>$profile->firstname $profile->lastname</td></tr>";
	echo "<tr><th class='profiletag'><span class='profilehead'>Register: </span></th><td>".Date("Y-m-d",$profile->registerdate)."</td></tr>";
	echo "<tr><th class='profiletag'><span class='profilehead'>Work week: </span></th><td>".timestampToTime($profile->dworkweek)." </td></tr>";
	echo "<tr><th class='profiletag'><span class='profilehead'>Offset: </span></th><td>".timestampToTime($profile->offset)." </td></tr>";
	echo "</table>";
	/*echo "<div class='span5'>";
	echo "<img class='avatar' src='includes/avatar.php?MID=".$_SESSION['SESS_MEMBER_ID']."'>";
	echo "</div></div>";
	echo "<div class='row-fluid'>"*/
	echo "<b>Statisitcs</b>";
	echo "<table class='table  table-striped'>";
	echo "<tr><th class='profiletag'><span class='profilehead'>Today: </span></th><td><span id='uptoday'>".timestampToTime($today['worktime'])."</span></td></tr>";
	echo "<tr><th class='profiletag'><span class='profilehead'>This week: </span></th><td><span id='upthisweek'>".timestampToTime($thisweek['worktime'])."</span></td></tr>";
	echo "<tr><th class='profiletag'><span class='profilehead'>This month: </span></th><td><span id='upthismonth'>".timestampToTime($thismonth['worktime'])."</span></td></tr>";
	echo "</table>";
	echo "</div></div>"
?>
</p>
