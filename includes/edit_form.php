<?php 	
	
	if($_GET['type'] == "work") {		// only show if type is work
		if(count($period[2]) > 0) {
			echo("<p>Breaks:</p>"); 
			foreach($period[2] as $break) {
				printf("%s -> %s <a href='proc/process_remove.php?id=%s&rtype=break&%s'><img src='images/remove.gif'></a><br />", date("Y-m-d H:i:s", $break->starttime), date("Y-m-d H:i:s", $break->endtime), $break->id, $_SERVER['QUERY_STRING']);
			}
		}
	}
	echo "<form id='edit' name='manualedit' method='post' action='proc/process_edit.php?p=".$_GET['p']."&type=".$_GET['type']."&a=".$_GET['a']."'><p>";
	echo "Start: <br />";
	echo "<input type='textbox' maxlength='10' name='start_date' value='".date("Y-m-d", $period[1]->starttime)."' id='start_date'> <br />";
	echo "<input type='textbox' maxlength='2' size='2' name='start_hour' value='".date("H", $period[1]->starttime)."' id='start_hour'>&nbsp;";
	echo "<input type='textbox' maxlength='2' size='2' name='start_min' value='".date("i", $period[1]->starttime)."' id='start_min'>&nbsp;";
	echo "<input type='textbox' maxlength='2' size='2' name='start_sec' value='".date("s", $period[1]->starttime)."' id='start_sec'><br />";
	echo "End: <br />";
	echo "<input type='textbox' maxlength='10' name='end_date' value='".date("Y-m-d", $period[1]->endtime)."' id='end_date'> <br />";
	echo "<input type='textbox' maxlength='2' size='2' name='end_hour' value='".date("H", $period[1]->endtime)."' id='end_hour'>&nbsp;";
	echo "<input type='textbox' maxlength='2' size='2' name='end_min' value='".date("i", $period[1]->endtime)."' id='end_min'>&nbsp;";
	echo "<input type='textbox' maxlength='2' size='2' name='end_sec' value='".date("s", $period[1]->endtime)."' id='end_sec'><br />";	
	echo "Comment: <br />
	        <input type='textbox' name='comment' value='".$period[1]->comment."' id='comment'>
	        <br />
	        <input type='hidden' name='fetched_period' id='fetched_period' value='".serialize($period)."'>
	        <input type='submit' name='button' id='button' value='Submit' />";
	printf("<a href='proc/process_remove.php?id=%s&rtype=%s&%s'><img src='images/remove.gif'></a><br />", $period[1]->id, $_GET['type'], $_SERVER['QUERY_STRING']);
	echo "</p>
	      </form>";

?>