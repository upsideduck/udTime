<?php 
	session_start();
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="js/notifications.js"></script>	
<link href="css/main.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">
$(document).ready(function(){
	updateelements();
	
	function projectclicked(pid) {
		alert(pid);
	}
	
	function successExec(xml) {
		if ($("result", xml).attr("success") == "true")
		{ 
			var notificationtype = "result";
			var messages = [];
			$(xml).find("message").each(function()
			{
			   	messages.push($(this).text());
			});

			$("#projects").append("<span class='projectlist' id='p_"+messages[1]+"'>"+messages[2]+"</span>");
		}
		else
		{
			var notificationtype = "error";
		}
			
		$("#projects").append("<span class='projectlist' id='p_add'>add</span>");	
		updateelements();
		$("#result").showNotification(notificationtype,messages[0]);
	}
	
	function addProject(pname){
		$.ajax({
		   type: "POST",
		   data: { pname : pname },
		   url: "proc/process_newproject.php",
		   dataType: "xml",
		   success: successExec
		 });
	}
	
	function updateelements(){
		$(".projectlist").click(
			function()
			{
			    var pid = ($(this).attr("id").split("_"))[1];
			    if(pid == "add"){
			    	$("#p_add").remove();
			    	$("#projects").append("<input type='text' id='add_project'>");
			    	$("#add_project").keyup(function(event){
					    if(event.keyCode == 13){
					    	var new_project = $("#add_project").val();				    
					    	$("#add_project").remove();
					    	addProject(new_project);					    	
					    }
					});
			    }
			    else
			    {
			    	projectclicked(pid);
			    }
		});
	}
	
});
</script>

<body>

<div id="projects">
<span class="projectlist" id="p_none">none</span>
<?php


    require_once("includes/config.php");
    require_once('func/func_fetch.php');
    require_once('func/func_misc.php');
    //require_once('includes/header.php');
	
	$projects = fetchProjects();
	
	foreach($projects as $project) {
		echo "<span class='projectlist' id='p_".$project['id']."'>".$project['name']."</span>\n";
	}

?>
<span class="projectlist" id="p_add">add</span>
</div>
<div id="result"></div>
</body>
</html>