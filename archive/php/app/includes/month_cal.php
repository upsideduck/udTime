<?php 
/*
echo "<div id='button'>";
echo "<div id='button_image'>Menu</div>";
echo "<ul class='the_menu'>";
echo "<li class='bheader'>View</li>";
echo "<ul class='subpage_menu_list'>";
	echo "<li><a href='summary.php?summary=month_view&m={$_GET['m']}&y={$_GET['y']}'>List</a></li>";
	echo "<li><a href='summary.php?summary=month_cal&m={$_GET['m']}&y={$_GET['y']}'>Calendar</a></li>";
echo "</ul>";
echo "<li class='bheader'>Actions</li>";
echo "<ul class='subpage_menu_list'>";
	echo "<li><a href='#' id='subpage_menu_add_vac'>Add asworktime</a></li>";
	echo "<li><a href='#' id='subpage_menu_add_free'>Add Time off</a></li>";
	echo "<li><a href='#' id='subpage_menu_add_work'>Add work</a></li>";
	echo "<li><a href='#' id='subpage_menu_add_break'>Add break</a></li>";
echo "</ul>";
echo "</ul>";
echo "</div>";*/
?>
<input type="hidden" id="pageyear" value="<?php echo $_REQUEST['y'];?>" next="" prev="">
<input type="hidden" id="pagemonth" value="<?php echo $_REQUEST['m'];?>" next="" prev="">
<div class='row'>
<div class='span6 offset2 marketing'>
<h1>Summary</h1>
</div>
<div class='span2' style="margin-top:50px;">
<a href="#" id="gocalendar"><i class="icon-calendar icon-2x pull-right"></i></a> <a href="#" id="golist"><i class="icon-list icon-2x pull-right"></i></a>
</div>
</div>
<div class="row">
<div class="span8 offset2">
<?php include_once("includes/pans/resultpan.php"); ?>
</div>
</div>
<div class="row">
<div  class="span10 offset1">
<div id='calendar'></div>
</div>
</div>
<div class="row">
<div class="span10 offset1">
To Work this week: <span id='towork'></span> (incl time that reduces total work time)<br />
Time worked this week: <span id='worked'></span> (incl time that counts as work time)
</div>
</div>
