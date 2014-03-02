<div id="projectspan">
<span class="projectlist project" id="p_none">none</span>
<?php
	$projects = fetchProjects();	
	foreach($projects as $project) {
		echo "<span class='projectlist project' id='p_".$project['id']."'>".$project['name']."</span>\n";
	}
?>
<span class="projectlist" id="p_add">add</span>
<input type='text' id='add_project'>
</div>

<div id="project-assign-dialog" title="Assign project">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 30px 0;"></span>A project is already assigned to this work-period. Do you want to change the project for this work-period or start a new work-period for the this project?</p>
</div>