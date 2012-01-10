<div id="projectspan" class="ui-widget ui-widget-content ui-corner-all">
<span class="projectlist" id="p_none">none</span>
<?php
	$projects = fetchProjects();	
	foreach($projects as $project) {
		echo "<span class='projectlist' id='p_".$project['id']."'>".$project['name']."</span>\n";
	}
?>
<span class="projectlist" id="p_add">add</span>
</div>
