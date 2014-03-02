<style>
.bootstrap-datetimepicker-widget{z-index:9999 !important;}
</style>
<!-- Modal -->
<div id="workModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="workModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="workModalLabel">Work</h3>
  </div>
  <div class="modal-body" id="work-body">
  <div id="workResult" class=""></div>
  <form>
  	<fieldset>
		<input type="hidden" name="work_id" id="work_id" value="" tabindex="-1"/>
		<label for="work_sdate">Set start time</label>
		<div class="input-append datetimepicker">
			<input data-format="yyyy-MM-dd hh:mm:ss" type="text" name="work_start" id="work_stime" value="" tabindex="-1" data-mask="9999-99-99 99:99:99"/>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
		</div>
		<label for="work_etime">Set end time</label>
		<div class="input-append datetimepicker">
			<input data-format="yyyy-MM-dd hh:mm:ss" type="text" name="work_end" id="work_etime" value="" tabindex="-1" data-mask="9999-99-99 99:99:99"/>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
		</div>
		<br><br>
		<div id="workBreaks"></div>
		<div id="workVacFree"></div>
	</fieldset>
  </form>
  </div>
  <div class="modal-body" id="work-loader-body">
	  <div class="progress progress-striped active">
		  <div class="bar" style="width: 100%;"></div>Loading...
	  </div>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" id="workCancel" aria-hidden="true">Close</button>
    <button class="btn btn-primary" id="workSave">Save changes</button>
  </div>
</div>

<!-- Modal -->
<div id="breakModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="breakModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="breakModalLabel">Break</h3>
  </div>
  <div class="modal-body" id="break-body">
  <div id="breakResult" class=""></div>
  <form>
  	<fieldset>
  		<label for="break_work">Break for work period:</label>
  		<table id="breakWorkTime" name="break_work" class="table table-striped">
			
		</table>
  		<input type="hidden" name="break_id" id="break_id" value="" tabindex="-1"/>
		<label for="break_sdate">Set start time</label>
		<div class="input-append datetimepicker">
			<input data-format="yyyy-MM-dd hh:mm:ss" type="text" name="break_start" id="break_stime" value="" tabindex="-1" data-mask="9999-99-99 99:99:99"/>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
		</div>
		<label for="break_etime">Set end time</label>
		<div class="input-append datetimepicker">
			<input data-format="yyyy-MM-dd hh:mm:ss" type="text" name="break_end" id="break_etime" value="" tabindex="-1" data-mask="9999-99-99 99:99:99"/>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
		</div>
	</fieldset>
  </form>
  </div>
  <div class="modal-body" id="break-loader-body">
	  <div class="progress progress-striped active">
		  <div class="bar" style="width: 100%;"></div>Loading...
	  </div>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" id="breakCancel" aria-hidden="true">Close</button>
    <button class="btn btn-primary" id="breakSave">Save changes</button>
  </div>
</div>

<!-- Modal -->
<div id="asworktimeModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="asworktimeModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="asworktimeModalLabel">As work time</h3>
  </div>
  <div class="modal-body" id="asworktime-body">
  <div id="asworktimeResult" class=""></div>
  <form>
  	<fieldset>
  		<input type="hidden" name="asworktime_id" id="asworktime_id" value="" tabindex="-1"/>
		<label for="asworktime_sdate">Set start date</label>
		<div class="input-append datetimepicker">
			<input data-format="yyyy-MM-dd" type="text" name="asworktime_start" id="asworktime_sdate" value="" tabindex="-1" data-mask="9999-99-99"/>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
		</div>
		<label for="asworktime_edate">Set end date</label>
		<div class="input-append datetimepicker">
			<input data-format="yyyy-MM-dd" type="text" name="asworktime_end" id="asworktime_edate" value="" tabindex="-1" data-mask="9999-99-99"/>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
		</div>
		<label for="asworktime_time">Time per day</label>
		<div class="input-append datetimepicker">
			<input data-format="hh:mm:ss" type="text" name="asworktime_time" id="asworktime_time" value="08:00:00" tabindex="-1" data-mask="99:99:99"/>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
		</div>
		<label for="asworktime_type">Type</label>
		<select name="asworktime_type" id="asworktime_type">
			<?php 
				$lookup = lookupTable("asworktime");
				foreach($lookup as $lu){
					printf("<option value='%s'>%s</option>",$lu->code, $lu->name);
				}
			?>
		</select>
	</fieldset>
  </form>
  </div>
  <div class="modal-body" id="asworktime-loader-body">
	  <div class="progress progress-striped active">
		  <div class="bar" style="width: 100%;"></div>Loading...
	  </div>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" id="asworktimeCancel" aria-hidden="true">Close</button>
    <button class="btn btn-primary" id="asworktimeSave">Save changes</button>
  </div>
</div>

<!-- Modal -->
<div id="freeModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="freeModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="freeModalLabel">Reduced work time</h3>
  </div>
  <div class="modal-body" id="free-body">
  <div id="freeResult" class=""></div>
  <form>
  	<fieldset>
  		<input type="hidden" name="free_id" id="free_id" value="" tabindex="-1"/>
		<label for="free_sdate">Set start date</label>
		<div class="input-append datetimepicker">
			<input data-format="yyyy-MM-dd" type="text" name="free_start" id="free_sdate" value="" tabindex="-1" data-mask="9999-99-99"/>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
		</div>
		<label for="free_edate">Set end date</label>
		<div class="input-append datetimepicker">
			<input data-format="yyyy-MM-dd" type="text" name="free_end" id="free_edate" value="" tabindex="-1" data-mask="9999-99-99"/>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
		</div>
		<label for="free_time">Time per day</label>
		<div class="input-append datetimepicker">
			<input data-format="hh:mm:ss" type="text" name="free_time" id="free_time" value="08:00:00" tabindex="-1" data-mask="99:99:99"/>
			    <span class="add-on">
			    	<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
			    </span>
		</div>
		<label for="free_type">Type</label>
		<select name="freee_type" id="free_type">
			<?php 
				$lookup = lookupTable("againstworktime");
				foreach($lookup as $lu){
					printf("<option value='%s'>%s</option>",$lu->code, $lu->name);
				}
			?>
		</select>
	</fieldset>
  </form>
  </div>
  <div class="modal-body" id="free-loader-body">
	  <div class="progress progress-striped active">
		  <div class="bar" style="width: 100%;"></div>Loading...
	  </div>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" id="freeCancel" aria-hidden="true">Close</button>
    <button class="btn btn-primary" id="freeSave">Save changes</button>
  </div>
</div>

<!-- Modal -->
<div id="choiceModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="choicefreeModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="fchoiceModalLabel">Choose type</h3>
  </div>
  <div class="modal-body" id="choice-body">
  <div id="choiceResult" class=""></div>
  <form>
  	<fieldset>
  		<input type="hidden" name="choice_start" id="choice_start" value="" tabindex="-1"/>
   		<input type="hidden" name="choice_end" id="choice_end" value="" tabindex="-1"/>
   		<div id="choice_work_alt">
   			<label for="choice_work_btn">Work period</label>
			<div><button type="button" name="choice_work_btn" id="choice_work_btn" class="btn btn-primary">Work</button></div>
   		</div>
   		<div id="choice_break_alt">
			<label for="choice_break_btn">Break period</label>
			<div><button type="button" name="type" id="choice_break_btn" class="btn btn-primary">Break</button></div>	
		</div>
		<div id="choice_free_alt">
	   		<label for="choice_free_btn">Mark as reduced work time provided by employer (public holidays etc)</label>
			<div><button type="button" name="choice_free_btn" id="choice_free_btn" class="btn btn-primary">Reduction</button></div>
		</div>
		<div id="choice_asworktime_alt">
			<label for="choice_asworktime_btn">Mark as time counted as work time</label>
			<div><button type="button" name="type" id="choice_asworktime_btn" class="btn btn-primary">Addition</button></div>
		</div>

	</fieldset>
  </form>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" id="choiceCancel" aria-hidden="true">Close</button>
  </div>
</div>
