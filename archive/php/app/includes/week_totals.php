<?php

//Start session
/*if(!isset($_SESSION['SESS_MEMBER_ID'])) session_start();
require_once("includes/config.php");
require_once(__SITE_BASE__."func/func_fetch.php");
require_once(__SITE_BASE__."func/func_misc.php");

$regiserspan = new timespan($user->registerdate, time());
$weekStats = $regiserspan->getWeekStats();
$allWeeks = $regiserspan->weeksInSpan();
$firstWeek = $allWeeks[0];
$lastWeek = end($allWeeks);
$all_months_totals = 0;

echo "<div class='list_header'>Week stats: ".$firstWeek['year']." W".$firstWeek['week']." - ".$lastWeek['year']." W".$lastWeek['week']."</div>";

$all_weeks_totals = 0;

echo "<div class='subpage_container'>";
echo "<div class='subpage_week'>";

if($user->offset != 0)
{
	echo "<div class='weeklist_item'><div class='weeklist_offset'>Offset: ".timestampToTime($user->offset)."</div></div>";
	$all_weeks_totals += $user->offset; 
}
foreach($weekStats as $week) {
	if(date("W-Y") !=  str_pad($week['week'], 2, "0", STR_PAD_LEFT)."-".$week['year']) $status = "<img src='images/check.png' height='10'>\n";
	else {
		$status = "";
		$active_week = true;
	}
	
	if($week['asworktime'] != 0) $asworktime = " + <a href='summary.php?summary=week_view&w={$week['week']}&y=${$week['year']}'>". timestampToTime($week['asworktime'])."</a>";
	else $asworktime = "";
	
	$diffstamp = ($week['asworktime']+$week['worked'] - $week['towork'] - $week['againstworktime']);
	if ($active_week) {
		$diff = "ongoing...";
		$diffstamp = 0;
	}
	elseif ($diffstamp < 0) $diff = "-".timestampToTime(abs($diffstamp));
	else $diff = "+	".timestampToTime($diffstamp);
	
	$all_weeks_totals += $diffstamp;
	printf("<div class='weeklist_item'><div class='weeklist_date'>w%s - y%s: </div><div class='weeklist_timestamp'><a href='summary.php?summary=week_view&w=%s&y=%s'>%s</a></div><div class='weeklist_holiday'>%s</div><div class='weeklist_diff'>%s</div><div class='weeklist_status'>%s</div>%s</div>",$week['week'], substr($week['year'],-2),$week['week'], $week['year'],timestampToTime($week['worked']), $holiday, $diff , $status, timestampToTime($all_weeks_totals) );
}
echo "<div id='weeklist_alltotals'>";
echo timestampToTime($all_weeks_totals);
echo "</div>";
echo "</div>";
echo "<div class='subpage_menu'>";
echo "<ul class='subpage_menu_list subpage_menu_style'><li class='bheader'>Actions</li><ul class='subpage_menu_list'><li><a href='#' id='subpage_menu_add_vac'>Add asworktime</a></li><li><a href='#' id='subpage_menu_add_free'>Add Time off</a></li><li><a href='#' id='subpage_menu_add_work'>Add work</a></li><li><a href='#' id='subpage_menu_add_break'>Add break</a></li></ul></ul>";
echo "</div>";
echo "</div>";
echo "<div class='clear'></div>";
*/
?>
<div class='row'>
<div class='span10 offset2 marketing'>
<h1>Summary</h1>
<p id='sumsubheader' class='marketing-byline'>Weeks stats</p>
</div></div>
<div class="row">
<div class="span6 offset3">
<?php include_once("includes/pans/resultpan.php"); ?>
</div>
</div>
<div class="row">
<table id="weekTotals" class="table table-striped span6 offset3">
<tr>
	<th>
		Year
	</th>	
	<th>
		Week
	</th>
	<th>
		Worked
	</th>
	<th>
		Difference
	</th>
	<th>
		Total difference
	</th>
	<th>
	</th>
</table>
</div>
<div class='row'>
<div class='span10 offset1'>
<div class="pagination pagination-centered">
<ul id="paginationwt"></ul>
</div>
</div>
</div>