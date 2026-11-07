<?php
require_once('config.php');
require_once(__SITE_BASE__ . 'func/func_misc.php');		
?>
    <form id='mainForm' name='mainForm' method='post' action=''> 
    <input type='hidden' name='uid' value='<?php echo $_SESSION['SESS_MEMBER_ID']; ?>' id='uid'/> 
    <div class='row-fluid'>
    	<div class='span12'>
    		<h1 id='clock' class="thin">&nbsp;</h1>
    	</div>
    </div>

    <div class='row-fluid'>
    	<div class='span12 padded'>
		    <input type='hidden' name='script' value='' id='script'/> 
		    <input type="hidden" id="checkedValue" value="" />
			<div id="free_choices" class="btn-group checkedValue" data-toggle="buttons-radio">
			<button type="button" name="type" value="work" id="f_work" class="btn btn-primary">Work</button>
			</div>
			<div id="work_choices" class="btn-group checkedValue" data-toggle="buttons-radio">
			<button type="button" name="type" value="break" id="w_break" class="btn btn-danger">Go on break</button>
			<button type="button" name="type" value="work" id="w_work" class="btn btn-primary">Go home</button>
			</div>
			<div id="break_choices" class="btn-group checkedValue" data-toggle="buttons-radio">
			<button type="button" name="type" value="break" id="b_break" class="btn btn-danger">End break</button>
			<button type="button" name="type" value="work" id="b_work" class="btn btn-primary">Go home</button>
			</div>
    	</div>
    </div>
    <div class='row-fluid'>
    	<div class='span1'>
			<input type='checkbox' id='now' checked>
    	</div>
		<div class='span11 mftime'>
			Now or:
		</div>
    </div>
    <div class='row-fluid'>
			<div id="timepicker" class="input-append">
			 	<input data-format="hh:mm" type="text" id="time" class="span5"></input>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
			</div>
			<script type="text/javascript">
			  $(function() {
			    $('#timepicker').datetimepicker({
			       xalanguage: 'pt-BR',
			       pickDate: false,
				   pickSeconds: false,
			    });
			  });
			</script>
    </div>
    <div class='row-fluid'>
    	<div class='span12'>
		    <span id='comment_header'>Comment: </span>
  	 	</div>
    </div>
    <div class='row-fluid'>
	   	<input type='textbox' name='comment' value='' id='comment' class='span12'>
    </div>
    <div class='row-fluid'>
    	<div class='span12'>
		    <div id='mfproject'><span>Project: </span><span id='mf_project'></span></div>    
	 	</div>
    </div>  
    <div class='row-fluid'>
    	<div class='span12'>
		    <input type='submit' name='button' id='mainform_submit' value='Set'  class="btn"/> <a href='edit.php?a=new&type=work'>Manual</a> 
	    </div>
    </div>
    </form>
