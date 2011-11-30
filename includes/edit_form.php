<?php 	
	
	if($_GET['type'] == "work") {		// only show if type is work
		if(count($period[2]) > 0) {
			echo("<p>Breaks:</p>"); 
			foreach($period[2] as $break) {
				printf("%s -> %s <a href='proc/process_remove.php?id=%s&rtype=break&%s'><img src='images/remove.gif'></a><br />", date("Y-m-d H:i:s", $break->starttime), date("Y-m-d H:i:s", $break->endtime), $break->id, $_SERVER['QUERY_STRING']);
			}
		}
	}
	
	echo "<form id='edit' name='manualedit' method='post' action='proc/process_edit.php?p=".$_GET['p']."&type=".$_GET['type']."&a=".$_GET['a']."'>\n<p>\n";
	echo "Start: <br />";
	echo "<input type='textbox' maxlength='10' name='start_date' value='".date("Y-m-d", $period[1]->starttime)."' id='start_date'> <br />\n";
	echo "<input type='textbox' maxlength='8' name='start_time' value='".date("H:i:s", $period[1]->starttime)."' id='start_time'><br>\n";
	echo "End: <br />\n";
	echo "<input type='textbox' maxlength='10' name='end_date' value='".date("Y-m-d", $period[1]->endtime)."' id='end_date'> <br />\n";
	echo "<input type='textbox' maxlength='8' name='end_time' value='".date("H:i:s", $period[1]->endtime)."' id='end_time'><br>\n";
	echo "Comment: <br />
	        <input type='textbox' name='comment' value='".$period[1]->comment."' id='comment'>\n
	        <br />\n
	        <input type='hidden' name='fetched_period' id='fetched_period' value='".serialize($period)."'>\n
	        <input type='submit' name='button' id='button' value='Submit' />\n";
	printf("<a href='proc/process_remove.php?id=%s&rtype=%s&%s'><img src='images/remove.gif'></a><br />\n", $period[1]->id, $_GET['type'], $_SERVER['QUERY_STRING']);
	echo "</p>\n
	      </form>\n";

?>