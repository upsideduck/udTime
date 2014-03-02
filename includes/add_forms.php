<div id="add-vac-dialog-form" title="Add time that counts as work time">
<form>
	<fieldset>
		<label for="vac_sdate">Set start date</label><br>
		<input type="text" name="date" id="vac_sdate" class="text ui-widget-content ui-corner-all" value="0" tabindex="-1"/><br>
		<label for="vac_edate">Set end date</label><br>
		<input type="text" name="date" id="vac_edate" class="text ui-widget-content ui-corner-all" value="0" tabindex="-1"/><br>
		<label for="vac_time">Set time per day</label><br>
		<input type="text" name="time" id="vac_time" class="text ui-widget-content ui-corner-all" value="0" tabindex="-1"/><br>
		<select>
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
<div id="add-free-dialog-form" title="Add reduced work time">
<form>
	<fieldset>
	<label for="free_sdate">Set start date</label><br>
		<input type="text" name="date" id="free_sdate" class="text ui-widget-content ui-corner-all" value="0" tabindex="-1"/><br>
		<label for="free_edate">Set end date</label><br>
		<input type="text" name="date" id="free_edate" class="text ui-widget-content ui-corner-all" value="0" tabindex="-1"/><br>
		<label for="free_time">Set time per day</label><br>
		<input type="text" name="time" id="free_time" class="text ui-widget-content ui-corner-all" value="0" tabindex="-1"/><br>
	</fieldset>
	</form>
</div>
<div id="add-work-dialog-form" title="Add work">
<form>
	<fieldset>
		<label for="work_sdate">Set start time</label><br>
		<input type="text" name="work_start" id="work_stime" class="text ui-widget-content ui-corner-all" value="0" tabindex="-1"/><br>
	
		<label for="work_etime">Set end time</label><br>
		<input type="text" name="work_end" id="work_etime" class="text ui-widget-content ui-corner-all" value="0" tabindex="-1"/><br>
		</fieldset>
	</form>
</div>
<div id="add-break-dialog-form" title="Add break">
<form>
	<fieldset>
		<label for="break_sdate">Set start time</label><br>
		<input type="text" name="break_start" id="break_stime" class="text ui-widget-content ui-corner-all" value="0" tabindex="-1"/><br>
	
		<label for="break_etime">Set end time</label><br>
		<input type="text" name="break_end" id="break_etime" class="text ui-widget-content ui-corner-all" value="0" tabindex="-1"/><br>
		</fieldset>
	</form>
</div>