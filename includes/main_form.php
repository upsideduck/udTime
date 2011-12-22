<?php
require_once('config.php');
require_once(__SITE_BASE__ . 'func/func_misc.php');		

       
    echo("<div class='ui-widget ui-widget-content ui-corner-all main-form'>");
    echo "<div class='ui-widget-header ui-corner-all'>Header</div>";
    
   	echo "<span id='period_description'></span>";
    echo "<div id='clock'>&nbsp;</div>";
    
    echo "<form id='mainForm' name='mainForm' method='post' action=''> \n";
    echo "<input type='hidden' name='script' value='' id='script'/> \n";
    echo "<div id='free_choices'>";
    echo "<label>
          <input type='radio' name='type' value='work' id='type1' checked/>
          Work	
         </label>";
	echo "</div>";             
	echo "<div id='work_choices'>";
	echo "<label>
	     <input type='radio' name='type' value='break' id='type2'/>
	     Go on break	
	     </label><br />";
	echo "<label>
	     <input type='radio' name='type' value='work' id='type3'/>
	     End current active period
	     </label>";     
	echo "</div>";
	echo "<div id='break_choices'>";
	echo "<label>
	     <input type='radio' name='type' value='break' id='type4'/>
	     End break	
	     </label><br />";
	echo "<label>
	     <input type='radio' name='type' value='work' id='type5'/>
	     Go home from work
	     </label>";     
	echo "</div>"; 
	echo "<div><input type='checkbox' id='now' checked>Now or: <input type='text' id='time' size='10' value=''/></div>";
    echo "<span id='comment_header'>Comment: </span><br />
          <input type='textbox' name='comment' value='' id='comment'>
          <br />
          <input type='submit' name='button' id='mainform_submit' value='Set' />
          </form>";
 	echo "</div>";

?>
<div id="result" class="notification_mainform"></div>
<a href="edit.php?a=new&type=work">Manual add work period</a>
</p>