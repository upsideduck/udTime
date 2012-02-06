$(document).ready(function(){
	var latestProjectClicked;
	$("#add_project").hide();
	$("#p_add").click(
		function()
		{
	    	$("#p_add").hide();
	    	$("#add_project").show();
	    	$("#add_project").focus();
	    	$("#add_project").blur(function(){
	    		$("#add_project").hide();
	    		$("#p_add").show();
	    	});
	    	$("#add_project").keyup(function(event){
			    if(event.keyCode == 13){
			    	var new_project = $("#add_project").val();				    
			    	$("#add_project").hide();
			    	$("#add_project").val("");
			    	addProject(new_project);					    	
			    }
			});
	});
	$(".project").click(function(){
		var pid = ($(this).attr("id").split("_"))[1];
		latestProjectClicked = pid;
		projectclicked(pid);
	});

	
	function projectclicked(pid) {
	
		$.ajax({
		   type: "POST",
		   url: "proc/process_currentperiod.php",
		   dataType: "xml",
		   success: 
		   function(xml){
		   		var type = $(xml).find("type").text(); 
		   		var project = $(xml).find("project").text();
		   		var periodid = $(xml).find("id").text();
		   		var parent_id = $(xml).find("parent_id").text();  
		   		
		   		if(project == "none") assignProject(pid, "add");
		   		else if(project != "") $("#project-assign-dialog").dialog("open");
		   		
		   			
		   }
		});
	}
	
	function successAddProject(xml) {

		var messages = [];
		$(xml).find("message").each(function()
		{
		   	messages.push($(this).text());
		});
		if ($("result", xml).attr("success") == "true")
		{ 
			var notificationtype = "result";

			
			$("#p_add").before("<span class='projectlist' id='p_"+messages[1]+"'>"+messages[2]+"</span>");
			var pid = messages[1];
			$("#p_"+pid).click(function()
							{
								latestProjectClicked = pid;
								projectclicked(pid);
							});
			messages.splice(1,2);
		}
		else
		{
			var notificationtype = "error";
		}
			
		$("#p_add").show();
		$("#result").showNotification(notificationtype,messages);
	}
	
	function addProject(pname){
		$.ajax({
		   type: "POST",
		   data: { pname : pname },
		   url: "proc/process_newproject.php",
		   dataType: "xml",
		   success: successAddProject
		 });
	}
	


	$("#project-assign-dialog").dialog({
		resizable: false,
		autoOpen: false,
		position: 'center',		
		height: 180,
		modal: true,
		buttons: {
			"Change": function() {
				assignProject(latestProjectClicked, "update");
				$( this ).dialog( "close" );
			},
			"New period": function() {
				assignProject(latestProjectClicked, "newperiod");
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		}
	});

	function assignProject(pid,action) {
			$.ajax({
			   type: "POST",
			   data: { project_id : pid, action: action},
			   url: "proc/process_attachproject.php",
			   dataType: "xml",
			   success: function(xml){
			   				var messages = [];
								$(xml).find("message").each(function()
								{
								   	messages.push($(this).text());
								});

			   				if ($("result", xml).attr("success") == "true")
							{ 
								var notificationtype = "result";
								var pname = messages[0].split(" ")[1];
								if(messages[0] == "Project removed") pname = "none";
								if($("#mf_project").length) {
									//$("#mf_project").text(pname);
									updateMainForm = true;
								}

							}
							else
							{
								var notificationtype = "error";
							}
							$("#result").showNotification(notificationtype,messages);
					
			   			}
			});
	
	}
	
});



