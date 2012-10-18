<?php

//Start session
if(!isset($_SESSION['SESS_MEMBER_ID'])) session_start();
require_once($_SERVER["DOCUMENT_ROOT"]."/time/includes/config.php");
require_once(__SITE_BASE__."func/func_fetch.php");
require_once(__SITE_BASE__."func/func_misc.php");

$regiserspan = new timespan($user->registerdate, time());
$monthStats = $regiserspan->getMonthStats();

$allMonths = $regiserspan->monthsInSpan();
$firstMonth = $allMonths[0];
$lastMonth = end($allMonths);
$all_months_totals = 0;

echo "<div class='list_header'>Month stats: ".$firstMonth['year']." ".date("M", mktime(0, 0, 0, $firstMonth['month'], 10))." - ".$lastMonth['year']." ".date("M", mktime(0, 0, 0, $lastMonth['month'], 10))."</div>";

echo "<div class='subpage_container'>";
echo "<div class='subpage_month'>";

if($user->offset != 0)
{
	echo "<div class=''><div class='monthlist_offset'>Offset: ".timestampToTime($user->offset)."</div></div>";
	$all_months_totals += $user->offset; 
}
foreach($monthStats as $month) {
	if(date("m-Y") !=  str_pad($month['month'], 2, "0", STR_PAD_LEFT)."-".$month['year']) $status = "<img src='images/check.png' height='10'>\n";
	else {
		$status = "";
		$active_month = true;
	}
	
	if($month['vacation'] != 0) $vacation = " + <a href='summary.php?summary=month_view&m={$month['month']}&y=${$month['year']}'>". timestampToTime($month['vacation'])."</a>";
	else $vacation = "";
	
	$diffstamp = ($month['vacation']+$month['worked'] - $month['towork'] + $month['timeoff']);
	if ($active_month) {
		$diff = "...ongoing";
		$diffstamp = 0;
	}
	elseif ($diffstamp < 0) $diff = "-".timestampToTime(abs($diffstamp));
	else $diff = "+	".timestampToTime($diffstamp);
	
	$all_months_totals += $diffstamp;
	
	$monthName = date("M", mktime(0, 0, 0, $month['month'], 10));
	printf("<div class='monthlist_item'><div class='monthlist_date'>%s, %s: </div><div class='monthlist_timestamp'><a href='summary.php?summary=month_view&m=%s&y=%s'>%s</a></div><div class='monthlist_holiday'>%s</div><div class='monthlist_diff'>%s</div><div class='monthlist_status'>%s</div>%s</div>",$monthName, $month['year'],str_pad($month['month'], 2, "0", STR_PAD_LEFT), $month['year'],timestampToTime($month['worked']), $holiday, $diff , $status, timestampToTime($all_months_totals) );
}
echo "<div id='monthlist_alltotals'>";
echo timestampToTime($all_months_totals);
echo "</div>";
echo "</div>";
echo "<div class='subpage_menu'>";
echo "<ul class='subpage_menu_list subpage_menu_style'><li class='bheader'>Actions</li><ul class='subpage_menu_list'><li><a href='#' id='subpage_menu_add_vac'>Add Vacation</a></li><li><a href='#' id='subpage_menu_add_free'>Add Time off</a></li><li><a href='#' id='subpage_menu_add_work'>Add work</a></li></ul></ul>";
echo "</div>";
echo "</div>";
echo "<div class='clear'></div>";
include("includes/add_forms.php");
?>