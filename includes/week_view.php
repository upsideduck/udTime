<?php

$year = $_REQUEST['y'];
$week = $_REQUEST['w'];

$weekstats = timespan::updateWeekStats($year,$week);
if(!$weekstats){
	echo "Stats not able to be calculated! Contact webmaster.";
	exit;
}

?>
<input type="hidden" id="pageyear" value="<?php echo $year;?>" next="" prev="">
<input type="hidden" id="pageweek" value="<?php echo $week;?>" next="" prev="">
<div class='row'>
<div class='span6 offset2 marketing'>
<h1>Summary</h1>
<p><a href='#' id='goprev'><i class='icon-chevron-left'></i></a> <span id='sumsubheader' class='marketing-byline'>Week stats</span> <a href='#' id='gonext'><i class='icon-chevron-right'></i></a></p>
</div>
<div class='span2' style="margin-top:50px;">
<a href="#" id="gocalendar"><i class="icon-calendar icon-2x pull-right"></i></a><a href="#" id="golist"><i class="icon-list icon-2x pull-right"></i></a>
</div>
</div>
<div class="row">
<div class="span8 offset2">
<?php include_once("includes/pans/resultpan.php"); ?>
</div>
</div>
<div class="row">
<table id="weekView" class="table table-striped span10 offset1">
<tr>
	<th>
		Day
	</th>	
	<th>
		Date
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
		Reduction
	</th>
	<th>
		Addition
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
<div id='weeklist_alltotals'></div>
</div>
</div>