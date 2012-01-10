<?php
require_once('config.php');
require_once(__SITE_BASE__ . 'func/func_misc.php');		

       
    echo("<div class='ui-widget ui-widget-content ui-corner-all mf'>");
    echo "<div class='ui-widget-header ui-corner-all' id='mfheader'></div>";
    
   	echo "<span id='period_description'></span>";
    echo "<div id='clock'>&nbsp;</div>";
    
    echo "<form id='mainForm' name='mainForm' method='post' action=''> \n";
    echo "<input type='hidden' name='script' value='' id='script'/> \n";
    echo "<div id='free_choices'>";
    echo "<input type='radio' name='type' value='work' id='f_work' checked/>	
         <label for='f_work'>Work</label>";
	echo "</div>";             
	echo "<div id='work_choices'>";
	echo "<input type='radio' name='type' value='break' id='w_break'/>
	     <label for='w_break'>Go on break</label>";
	echo "<input type='radio' name='type' value='work' id='w_work'/>
	     <label for='w_work'>Go home</label>";     
	echo "</div>";
	echo "<div id='break_choices'>";
	echo "<input type='radio' name='type' value='break' id='b_break'/>
	     <label for='b_break'>End break</label>";
	echo "<input type='radio' name='type' value='work' id='b_work'/>
	     <label for='b_work'>Go home</label>";     
	echo "</div>"; 
	echo "<div class='mftime'><input type='checkbox' id='now' checked>Now or: <input type='text' id='time' size='10' value=''/></div>";
    echo "<div id='mfcomment'><span id='comment_header'>Comment: </span> <br \>
          <input type='textbox' name='comment' value='' id='comment'>
          </div>
          <input type='submit' name='button' id='mainform_submit' value='Set' /> \n
          <a href='edit.php?a=new&type=work'>Manual</a> \n
          </form>";
 	echo "</div>";

?>
</p>