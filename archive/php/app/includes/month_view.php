<?php


$year = $_REQUEST['y'];
$month = $_REQUEST['m'];

$monthstats = timespan::updateMonthStats($year,$month);
if(!$monthstats){
	echo "Stats not able to be calulated! Contact webmaster.";
	exit;
}
/*
if($periodsArray){
	foreach($periodsArray as $period){
		$dayToAddTo = date("j",$period[0]["starttime"]);
		$daysArray[$dayToAddTo][] = $period;
	}
}

$prevmonth = date("m", strtotime("{$year}-{$month} - 1 month"));
$prevyear = date("Y", strtotime("{$year}-{$month} - 1 month"));
$nextmonth = date("m", strtotime("{$year}-{$month} + 1 month"));
$nextyear = date("Y", strtotime("{$year}-{$month} + 1 month"));


echo "<div class='list_header'><a href='{$_SERVER['PHP_SELF']}?summary=month_view&m={$prevmonth}&y={$prevyear}'><<</a> {$year} ".date("M", mktime(0, 0, 0, $month, 10))." <a href='{$_SERVER['PHP_SELF']}?summary=month_view&m={$nextmonth}&y={$nextyear}'>>></a> </div>";
echo "<div class='subpage_container'>";
echo "<div class='subpage_month'>";
echo "<div class='month_container_list'><div class='monthlist_item'><div class='monthlist_date bheader'>Date</div><div class='monthlist_day bheader'>Day</div><div class='monthlist_starttime bheader'>Start</div><div class='monthlist_breaks bheader'>Breaks</div><div class='monthlist_endtime bheader'>End</div><div class='monthlist_againstworktime bheader'>Free time</div><div class='monthlist_asworktime bheader'>asworktime</div></div>";

$tocsvarray = array(); 

for($i = 1; $i <= $numdays; $i++){
	$day = date("D",strtotime("{$year}-{$month}-{$i}"));
	$dayofweek = date("N",strtotime("{$year}-{$month}-{$i}"));
	if($dayofweek >= 6) $rowcolor = "weekendrowcolor";
	elseif($i%2) $rowcolor = "oddrowcolor";
	else $rowcolor = "evenrowcolor";
	
	if($asworktimetimestamp = $monthstats['asworktime']['days'][$year."-".$month."-".($i < 10 ? '0'.$i : $i)]['time']) $asworktimetime = timestampToTime($asworktimetimestamp);
	else $asworktimetime = "";
	if($againstworktimetimestamp = $monthstats['againstworktime']['days'][$year."-".$month."-".($i < 10 ? '0'.$i : $i)]['time']) $againstworktimetime = timestampToTime($againstworktimetimestamp);
	else $againstworktimetime = "";
	
	$periodsForDay = $daysArray[$i];
	$numPeriodsForDay = count($periodsForDay);
	if($periodsForDay){
		$writedate = true;
		foreach($periodsForDay as $periodForDay){
			$workTimePeriod = 0;
			$starttime = date("H:i",$periodForDay[0]['starttime']);
			$endtime = date("H:i",$periodForDay[0]['endtime']);
			
			$workTimePeriod = $workTimePeriod + ($periodForDay[0]['endtime'] - $periodForDay[0]['starttime']);
			
			$breaks = "";
			for($i2 = 1; $i2 < count($periodForDay); $i2++){
				$breaks .= date("H:i",$periodForDay[$i2]['starttime']) . "--" .  date("H:i",$periodForDay[$i2]['endtime']);
				$workTimePeriod = $workTimePeriod - ($periodForDay[$i2]['endtime'] - $periodForDay[$i2]['starttime']);
				if($i2 < count($periodForDay)) $breaks .= "<br>";
				
				if($i2 == 1)
					$tocsvarray[] = array($i,$day,$starttime,date("H:i",$periodForDay[$i2]['starttime']),date("H:i",$periodForDay[$i2]['endtime']),$endtime,$againstworktimetime,$asworktimetime);
				else
					$tocsvarray[] = array("","","",date("H:i",$periodForDay[$i2]['starttime']),date("H:i",$periodForDay[$i2]['endtime']),"","","");
			}
			
			if($breaks == "") 
				$tocsvarray[] = array($i,$day,$starttime,"","",$endtime,$againstworktimetime,$asworktimetime);

			
			$worktime = timestampToDecTime($workTimePeriod);
			
			$workTimeMonth += $workTimePeriod;
			
			
			if($writedate){
				echo "<div class='monthlist_item {$rowcolor}'><div class='monthlist_date'>{$i}</div><div class='monthlist_day'>{$day}</div><div class='monthlist_starttime'>{$starttime}</div><div class='monthlist_breaks'>{$breaks}</div><div class='monthlist_endtime'>{$endtime}</div><div class='monthlist_againstworktime'>{$againstworktimetime}</div><div class='monthlist_asworktime'>{$asworktimetime}</div><div class='monthlist_worktime'>{$worktime}</div></div>";
				$writedate = false;
			}else{
				echo "<div class='monthlist_item {$rowcolor}'><div class='monthlist_date'></div><div class='monthlist_day'></div><div class='monthlist_starttime'>{$starttime}</div><div class='monthlist_breaks'>{$breaks}</div><div class='monthlist_endtime'>{$endtime}</div><div class='monthlist_againstworktime'></div><div class='monthlist_asworktime'></div><div class='monthlist_worktime'>{$worktime}</div></div>";	
			}
			
		}	
	}else{
		echo "<div class='monthlist_item {$rowcolor}'><div class='monthlist_date'>{$i}</div><div class='monthlist_day'>{$day}</div><div class='monthlist_starttime'></div><div class='monthlist_breaks'></div><div class='monthlist_endtime'></div><div class='monthlist_againstworktime'>{$againstworktimetime}</div><div class='monthlist_asworktime'>{$asworktimetime}</div><div class='monthlist_worktime'></div></div>";
		$tocsvarray[] = array($i,$day,"","","","",$againstworktimetime,$asworktimetime);	
	}
			
 }


echo "</div><div class='monthview_worked'>Worked time: ".timestampToDecTime($monthstats['worked']+$monthstats['asworktime']['sum'])." (incl asworktime)</div>";

//echo "<pre>";
//var_dump($return);


echo "<div class='monthview_worked'>Time to work: ".timestampToDecTime($monthstats['towork']-$monthstats['againstworktime']['sum'])." (incl time off)</div>";

echo "<div id='monthlist_alltotals'>".timestampToDecTime($monthstats['againstworktime']['sum'] + $monthstats['worked'] + $monthstats['asworktime']['sum'] - $monthstats['towork'] )."</div>";
echo "</div>";
echo "<div class='subpage_menu'>";
echo "<ul class='subpage_menu_list subpage_menu_style'><li class='bheader'>View</li><ul class='subpage_menu_list'><li><a href='summary.php?summary=month_view&m={$_GET['m']}&y={$_GET['y']}'>List</a></li><li><a href='summary.php?summary=month_cal&m={$_GET['m']}&y={$_GET['y']}'>Calendar</a></li></ul><li class='bheader'>Actions</li><ul class='subpage_menu_list'><li><a href='#' id='subpage_menu_add_vac'>Add asworktime</a></li><li><a href='#' id='subpage_menu_add_free'>Add Time off</a></li><li><a href='#' id='subpage_menu_add_work'>Add work</a></li><li><a href='#' id='subpage_menu_add_break'>Add break</a></li></ul><li class='bheader'>Download</li><ul class='subpage_menu_list'><li><a href='".$_SERVER['REQUEST_URI']."&CSV=Y'>CSV</a></li></ul></ul>";
echo "</div>";
echo "</div>";
echo "<div class='clear'></div>";


if($_GET['CSV'] == "Y"){
	$filename = __SITE_BASE__.'tmp/'.$_SESSION['SESS_MEMBER_ID'].'_month.csv';
	
	$fp = fopen($filename, 'w');
	foreach ($tocsvarray as $fields) {
	    fputcsv($fp, $fields);
	}
	fclose($fp);
	printf("<script>location.href='download.php'</script>");
}

include("includes/add_forms.php");
*/
?>
<input type="hidden" id="pageyear" value="<?php echo $year;?>" next="" prev="">
<input type="hidden" id="pagemonth" value="<?php echo $month;?>" next="" prev="">
<div class='row'>
<div class='span6 offset2 marketing'>
<h1>Summary</h1>
<p><a href='#' id='goprev'><i class='icon-chevron-left'></i></a> <span id='sumsubheader' class='marketing-byline'>Month stats</span> <a href='#' id='gonext'><i class='icon-chevron-right'></i></a></p>
</div>
<div class='span2' style="margin-top:50px;">
<a href="#" id="gocalendar"><i class="icon-calendar icon-2x pull-right"></i></a> <a href="#" id="golist"><i class="icon-list icon-2x pull-right"></i></a> <a href="#" id="gocsv"><i class="icon-download icon-2x pull-right"></i></a>
</div>
</div>
<div class="row">
<div class="span8 offset2">
<?php include_once("includes/pans/resultpan.php"); ?>
</div>
</div>
<div class="row">
<table id="monthView" class="table table-striped span10 offset1">
<tr>
	<th>
		Day
	</th>	
	<th>
		Start
	</th>
	<th>
		Breaks
	</th>
	<th>
		End
	</th>
	<th>
		Free time
	</th>
	<th>
		asworktime
	</th>
	<th>
		Worked time (dec)
	</th>
	<th>
	</th>
	<th>
	</th>
</tr>
</table>
</div>
<div class="row">
<div class="span5 offset1">
<div class='monthview_worked'>Time to work: <span id="sumtowork">---</span> (incl time that reduces total work time)</div>
<div class='monthview_worked'>Worked time: <span id="sumworked">---</span> (incl time that counts as work time)</div>
<div id='monthlist_alltotals'></div>
</div>
</div>