<?php 	
	

	echo "<form id='new' name='manualnew' method='post' action='proc/process_edit.php?p=".$_GET['p']."&type=".$_GET['type']."&a=".$_GET['a']."'><p>";
	echo "Start: <br />";
	echo "<input type='textbox' maxlength='10' name='start_date' value=''> <br />";
	echo "<input type='textbox' maxlength='2' size='2' name='start_hour' value='' id='start_hour'>&nbsp;";
	echo "<input type='textbox' maxlength='2' size='2' name='start_min' value='' id='start_min'>&nbsp;";
	echo "<input type='textbox' maxlength='2' size='2' name='start_sec' value='' id='start_sec'><br />";
	echo "End: <br />";
	echo "<input type='textbox' maxlength='10' name='end_date' value='' id='end_date'> <br />";
	echo "<input type='textbox' maxlength='2' size='2' name='end_hour' value='' id='end_hour'>&nbsp;";
	echo "<input type='textbox' maxlength='2' size='2' name='end_min' value='' id='end_min'>&nbsp;";
	echo "<input type='textbox' maxlength='2' size='2' name='end_sec' value='' id='end_sec'><br />";	
	echo "Comment: <br />
	        <input type='textbox' name='comment' value='' id='comment'>
	        <br />
	  	    <input type='submit' name='button' id='button' value='Submit' />";
	echo "</p>
	      </form>";

?>