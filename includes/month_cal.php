<div id="add-fv-dialog-form" title="Add vacation/time off">
	<span id="starttimeform"></span> - <span id="endtimeform"></span>
	<form>
	<fieldset>
		<label for="name">Set time per day</label><br>
		<input type="text" name="name" id="time" class="text ui-widget-content ui-corner-all" value="0" /><br>
		<div id="radio_set_type">
			<label for='b_settimeoff'>Time off</label>
			<input type='radio' name='type' value='setfreedays' id='b_settimeoff' checked/>
		    <label for='b_setvacation'>Vacation</label> 
			<input type='radio' name='type' value='setvacation' id='b_setvacation' />
		</div>
	</fieldset>
	</form>
</div>
<div id="detail-fv-dialog-form" title="Details vacation/time off">
	More info
	<form>
		<input type="hidden" id="d_fv_itemid" value="" />
		<input type="hidden" id="d_fv_type" value="" />
	</form>
</div>

<?php 

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
	echo "<li><a href='#' id='subpage_menu_add_vac'>Add Vacation</a></li>";
	echo "<li><a href='#' id='subpage_menu_add_free'>Add Time off</a></li>";
	echo "<li><a href='#' id='subpage_menu_add_work'>Add work</a></li>";
echo "</ul>";
echo "</ul>";
echo "</div>";

	echo "<div id='calendar'></div>";
	echo "To Work this month: <span id='towork'></span> (incl holidays)<br />";
	echo "Time worked this month: <span id='worked'></span> (incl vacation)";
include("includes/add_forms.php");
?>