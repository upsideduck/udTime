<?php
//Start session
if(!isset($_SESSION['SESS_MEMBER_ID'])) session_start();
echo $_SERVER["PATH_TRANSLATED"];
require_once($_SERVER["DOCUMENT_ROOT"]."/time/includes/config.php");
require_once(__SITE_BASE__."func/func_weeksdb.php");
require_once(__SITE_BASE__."func/func_fetch.php");
require_once(__SITE_BASE__."func/func_misc.php");

$timeArray = $timeArray = fetchStartEndTime("week",$_REQUEST['y'],0,0,$_REQUEST['w']);
$return = fetchPeriodsArray($timeArray);

$allPeriods = $return[0];
$dayArray = $return[1];


$sql3 = "SELECT * FROM weeksdb WHERE member_id = " . $_SESSION['SESS_MEMBER_ID'] . " AND year = ".$_REQUEST['y']." AND week = ".$_REQUEST['w'];
$weekTotalsResult = mysql_query($sql3);
$weekTotals = mysql_fetch_row($weekTotalsResult);
	
echo "<div><a href='".$_SERVER['PHP_SELF']."?summary=week_view&w=".$_REQUEST['w']."&y=".$_REQUEST['y']."'>Graphic view</a></div>";
	
echo "<div class='total_list'>Time to work this week: <br>".timestampToTime($weekTotals[12])."</div>";
echo "<div class='total_list'>Time worked: <br>".timestampToTime($weekTotals[13])."</div>";
echo "<div class='holiday_list'>Vacation this week: <a href='edit.php?type=holiday&w=".$_REQUEST['w']."&y=".$_REQUEST['y']."&a=new'><img width='15px'src='images/edit.png'></a><br>".timestampToTime($weekTotals[11])."</div>";


for ($day = 1; $day <= 7; $day++) {
	echo "<div class='day_container_list'> \n";
	echo "<div class='day_header_list'>Day: $day </div> \n";
	if ($dayArray[$day] != null)
	{
		echo "<ul class='work_list'>\n";
		foreach($dayArray[$day] as $separatePeriod) {
			$nrPeriodsAndBreakes = count($allPeriods[$separatePeriod]);
			printf("<li class='work_item_list'><a href='edit.php?type=work&p=%s&a=%s'>%s - %s</a>", $allPeriods[$separatePeriod][0]['id'], "edit", date("H:i:s",$allPeriods[$separatePeriod][0]['starttime']), date("H:i:s",$allPeriods[$separatePeriod][0]['endtime']));
			if ($nrPeriodsAndBreakes > 1)  {
				echo "\n<ul class='break_list'>\n";
				for ($i = 1; $i < $nrPeriodsAndBreakes; $i++) {
					printf("<li class='break_item_list'><a href='edit.php?type=break&p=%s&a=%s'>-->%s - %s</a></li>\n",  $allPeriods[$separatePeriod][$i]['id'], "edit", date("H:i:s",$allPeriods[$separatePeriod][$i]['starttime']), date("H:i:s",$allPeriods[$separatePeriod][$i]['endtime']));
					
				}
				echo "</ul>\n";
			}
			echo "</li>\n	";
		}
		echo "</ul>\n";
	}
	echo "<div class='week_totals_list'>".timestampToTime($weekTotals[$day+3])."</div> \n";	
	echo "</div>";
}


?>