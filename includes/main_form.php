<?php
require_once('config.php');
require_once(__SITE_BASE__ . 'func/func_misc.php');		
    
   	echo "<div id='period_description'></div>";
    echo "<div id='clock'>&nbsp;</div>";
    
    echo "<form id='mainForm' name='mainForm' method='post' action=''> \n";
    echo "<input type='hidden' name='script' value='' id='script'/> \n";
    echo "<div id='free_choices'>";
    echo "<label>
          <input type='radio' name='type' value='work' id='type' checked/>
          Work	
         </label>";
	echo "</div>";             
	echo "<div id='work_choices'>";
	echo "<label>
	     <input type='radio' name='type' value='break' id='type'/>
	     Go on break	
	     </label><br />";
	echo "<label>
	     <input type='radio' name='type' value='work' id='type'/>
	     End current active period
	     </label>";     
	echo "</div>";
	echo "<div id='break_choices'>";
	echo "<label>
	     <input type='radio' name='type' value='break' id='type'/>
	     End break	
	     </label><br />";
	echo "<label>
	     <input type='radio' name='type' value='work' id='type'/>
	     Go home from work
	     </label>";     
	echo "</div>"; 
	echo "<div><input type='checkbox' id='now' checked>Now or: <input type='text' id='time' size='10' value=''/></div>";
    echo "Comment: <br />
          <input type='textbox' name='comment' value='' id='comment'>
          <br />
          <input type='submit' name='button' id='button' value='Submit' />
          </form>";
?>
<div id="result" class="notification_mainform"></div>
<a href="edit.php?a=new&type=work">Manual add work period</a>
</p>