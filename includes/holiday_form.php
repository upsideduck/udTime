<?php 	
	$holiday = "00:00:00";
	if ($_GET['w'] && $_GET['y']){
		
		$holiday_result = fetchHoliday($_GET['w'], $_GET['y']);
	 	if ($holiday_result[0] != null) $holiday = timestamptotime($holiday_result[0]);
	}	

	echo "<form id='holiday' name='holiday' method='post' action='proc/process_holiday.php?type=".$_GET['type']."&a=".$_GET['a']."'><p>\n";
	echo "Time: <input type='textbox' maxlength='8' name='time' value='$holiday' id='holidaytime'> <br />\n";	
	echo "Year: 20<input type='textbox' maxlength='2' size='2' name='year' value='".$_GET['y']."' id='year'>&nbsp;\n";
	echo "Week: <input type='textbox' maxlength='2' size='2' name='week' value='".$_GET['w']."' id='week'><br />\n";
	echo "<input type='submit' name='button' id='button' value='Submit' />\n";
	echo "</p>
	      </form>\n";
echo "<a href='".$_SERVER['HTTP_REFERER'].substr($_SERVER['REQUESTED_URI'],strpos($_SERVER['REQUESTED_URI'],'&')+1,0)."'>Return</a>\n";
?>