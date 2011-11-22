<?php
$timeArray = fetchWeekStartEndTime($_REQUEST['w'], $_REQUEST['y']);

$return = fetchPeriodsArray($timeArray);

$allPeriods = $return[0];
$dayArray = $return[1];


$sql3 = "SELECT * FROM weeksdb WHERE member_id = " . $_SESSION['SESS_MEMBER_ID'] . " AND year = ".$_REQUEST['y']." AND week = ".$_REQUEST['w'];
$weekTotalsResult = mysql_query($sql3);
$weekTotals = mysql_fetch_row($weekTotalsResult);

echo "<div><a href='".$_SERVER['PHP_SELF']."?summary=week_list&w=".$_REQUEST['w']."&y=".$_REQUEST['y']."'>List view</a></div>";
	
echo "<div class='totals'>Week total:<br />".timestampToTime($weekTotals[11])."</div>";

echo "<div class='container'>";

echo "<div class='hf_tab'>Monday</div>
<div class='hf_tab'>Tuesday</div>
<div class='hf_tab'>Wednesday</div>
<div class='hf_tab'>Thursday</div>
<div class='hf_tab'>Friday</div>
<div class='hf_tab'>Saturday</div>
<div class='hf_tab'>Sunday</div>";
echo "<div class='week_container'>";
for ($day = 1; $day <= 7; $day++) {
	echo "<div class='day_container'>";
	if ($dayArray[$day] != null)
	{
		foreach($dayArray[$day] as $separatePeriod) {
			$nrPeriodsAndBreakes = count($allPeriods[$separatePeriod]);
			$lengthWork = $allPeriods[$separatePeriod][0]['endtime'] - $allPeriods[$separatePeriod][0]['starttime'];
			$timeInWork = ($allPeriods[$separatePeriod][0]['starttime'] % (60*60*24));
			echo "<div class='work_tab' style='height: ".($lengthWork*100/(60*60*24))."%; top: ".($timeInWork*100/(60*60*24))."%;'>";
			
			for ($i = 1; $i < $nrPeriodsAndBreakes; $i++) {
				$length = $allPeriods[$separatePeriod][$i]['endtime'] - $allPeriods[$separatePeriod][$i]['starttime'];
				$timeIn = ($allPeriods[$separatePeriod][$i]['starttime'] % (60*60*24)) - $timeInWork;
				echo "<div class='break_tab' style='height: ".($length/$lengthWork*100)."%; top: ".($timeIn*100/(60*60*24)).";'> <a href='#'></a></div>";
				
			}
			printf(" <a href='%/edit.php?type=work&p=%s&a=%s'></a></div>", $_SERVER['PHP_SELF'], $allPeriods[$separatePeriod][0]['id'], "edit");
			
		}
	}
	
	echo "</div>";
}
echo "</div>"; 
for ($i = 4; $i <= 10; $i++) {
	echo "<div class='hf_tab' style='margin: 4px;'>".timestampToTime($weekTotals[$i])."</div>";
}
echo "</div>";
?>