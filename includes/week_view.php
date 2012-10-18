<?php

$daysLabels = array(1=>"Mon",2=>"Tue",3=>"Wed",4=>"Thu",5=>"Fri",6=>"Sat",7=>"Sun");
$year = $_REQUEST['y'];
$week = $_REQUEST['w'];
$numdays = 7;
$timeArray = fetchStartEndTime("week",$year,null,null,$week);
$return = fetchPeriodsArray($timeArray);
$periodsArray = $return[0];
$daysArray = array_fill(1,$numdays,null);

$weekstats = timespan::updateWeekStats($year,$week);
if(!$weekstats){
	echo "Stats not able to be calulated! Contact webmaster.";
	exit;
}
if($periodsArray){
	foreach($periodsArray as $period){
		$dayToAddTo = date("N",$period[0]["starttime"]);
		$daysArray[$dayToAddTo][] = $period;
	}
}

$prevweek = date("W", strtotime("{$year}W{$week} - 1 week"));
$prevyear = date("Y", strtotime("{$year}W{$week} - 1 week"));
$nextweek = date("W", strtotime("{$year}W{$week} + 1 week"));
$nextyear = date("Y", strtotime("{$year}W{$week} + 1 week"));

echo "<div class='list_header'><a href='{$_SERVER['PHP_SELF']}?summary=week_view&w={$prevweek}&y={$prevyear}'><<</a> Week: {$week} - Year: {$year} <a href='{$_SERVER['PHP_SELF']}?summary=week_view&w={$nextweek}&y={$nextyear}'>>></a> </div>";
echo "<div class='subpage_container'>";
echo "<div class='subpage_week'>";
echo "<div class='week_container_list'> \n";
echo "<div class='day_container_list'> \n";
echo "<div class='day_header_list bheader'>Day</div> \n";
echo "<ul class='work_list'>\n";
echo "<li class='work_item_list bheader'>Work time</li>\n";
echo "</ul>\n";
echo "<div class='week_totals_list bheader'>Time off</div> \n";	
echo "<div class='week_totals_list bheader'>Vacation</div> \n";	
echo "<div class='week_totals_list bheader'>Day total</div> \n";	
echo "<div class='week_totals_list bheader'></div> \n";	
echo "</div>";

for ($day = 1; $day <= 7; $day++) {
	if($day >= 6) $rowcolor = "weekendrowcolor";
	elseif($day%2) $rowcolor = "oddrowcolor";
	else $rowcolor = "evenrowcolor";
	$fulldate = date("Y-m-d", strtotime($year."W".($week < 10 ? "0".$week : $week)." +".($day-1)."days"));
	echo "<div class='day_container_list {$rowcolor}'> \n";
	echo "<div class='day_header_list'>{$daysLabels[$day]}</div> \n";
	$worktimeForDay = 0;
	$breaktimeForDay = 0;
	if ($daysArray[$day] != null)
	{
		echo "<ul class='work_list'>\n";
		foreach($daysArray[$day] as $dayArray) {
			$nrPeriodsAndBreakes = count($dayArray);
			$worktimeForDay += $dayArray[0]['endtime'] - $dayArray[0]['starttime'];
			printf("<li class='work_item_list'><a href='edit.php?type=work&p=%s&a=%s'>%s - %s</a>", $dayArray[0]['id'], "edit", date("H:i:s",$dayArray[0]['starttime']), date("H:i:s",$dayArray[0]['endtime']));
			if ($nrPeriodsAndBreakes > 1)  {
				echo "\n<ul class='break_list'>\n";
				for ($i = 1; $i < $nrPeriodsAndBreakes; $i++) {
					$breaktimeForDay +=  $dayArray[$i]['endtime'] - $dayArray[$i]['starttime'];
					printf("<li class='break_item_list'><a href='edit.php?type=break&p=%s&a=%s'>-->%s - %s</a></li>\n",  $dayArray[$i]['id'], "edit", date("H:i:s",$dayArray[$i]['starttime']), date("H:i:s",$dayArray[$i]['endtime']));
					
				}
				echo "</ul>\n";
			}
			echo "</li>\n	";
		}
		echo "</ul>\n";
	}
	else
	{
		echo "<ul class='work_list'>\n";
		echo "<li class='work_item_list'>00:00:00</li>\n";
		echo "</ul>\n";
	}	
	$daytotal = $worktimeForDay - $breaktimeForDay;
	
	if($weekstats['timeoff']['days'][$fulldate]) $timeoffday = timestampToTime($weekstats['timeoff']['days'][$fulldate]['time']);
	else $timeoffday = "";
	if($weekstats['vacation']['days'][$fulldate]) $vacationday = timestampToTime($weekstats['vacation']['days'][$fulldate]['time']);
	else $vacationday = "";
	echo "<div class='week_totals_list'>{$timeoffday}</div> \n";	
	echo "<div class='week_totals_list'>{$vacationday}</div> \n";	
	echo "<div class='week_totals_list'>".timestampToTime($daytotal)."</div> \n";	
	echo "<div class='week_totals_list'>". timestampToDecTime($daytotal) ."</div> \n";	
	echo "</div>";
}


echo "</div>";
echo "<div class='weekview_worked'>Worked time: ".timestampToDecTime($weekstats['worked']+$weekstats['vacation']['sum'])." (incl vacation)</div>";
echo "<div class='weekview_worked'>Time to work: ".timestampToDecTime($weekstats['towork']-$weekstats['timeoff']['sum'])." (incl time off)</div>";
echo "<div id='weeklist_alltotals'>".(timestampToDecTime($weekstats['worked']+$weekstats['vacation']['sum']) - timestampToDecTime($weekstats['towork']-$weekstats['timeoff']['sum']))."</div>";
echo "</div>";
echo "<div class='subpage_menu'>";
echo "<ul class='subpage_menu_list subpage_menu_style'><li class='bheader'>View</li><ul class='subpage_menu_list'>";
	echo "<li><a href='summary.php?summary=week_view&w={$_GET['w']}&y={$_GET['y']}'>List</a></li>";
echo "<li><a href='summary.php?summary=week_cal&w={$_GET['w']}&y={$_GET['y']}'>Calendar</a></li></ul><li class='bheader'>Actions</li><ul class='subpage_menu_list'><li><a href='#' id='subpage_menu_add_vac'>Add Vacation</a></li><li><a href='#' id='subpage_menu_add_free'>Add Time off</a></li><li><a href='#' id='subpage_menu_add_work'>Add work</a></li></ul></ul>";
echo "</div>";
echo "</div>";
echo "<div class='clear'></div>";
include("includes/add_forms.php");
?>