<?php 	
	

	echo "<form id='new' name='manualnew' method='post' action='proc/process_edit.php?p=".$_GET['p']."&type=".$_GET['type']."&a=".$_GET['a']."'>\n<p>\n";
	echo "Start: <br />\n";
	echo "<input type='textbox' maxlength='10' name='start_date' value='' id='start_date'> <br />\n";
	echo "<input type='textbox' maxlength='8' name='start_time' value='' id='start_time'> <br />\n";
	echo "End: <br />\n";
	echo "<input type='textbox' maxlength='10' name='end_date' value='' id='end_date'> <br />\n";
	echo "<input type='textbox' maxlength='8' name='end_time' value='' id='end_time'> <br />\n";
	echo "Comment: <br />\n
	        <input type='textbox' name='comment' value='' id='comment'>\n
	        <br />\n
	  	    <input type='submit' name='button' id='button' value='Submit' />\n";
	echo "</p>\n
	      </form>\n";

?>