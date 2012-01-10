$(document).ready(function(){
	updateelements();
	
	function projectclicked($pid) {
	
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

			$("#projectspan").append("<span class='projectlist' id='p_"+messages[1]+"'>"+messages[2]+"</span>");
		}
		else
		{
			var notificationtype = "error";
		}
			
		$("#projectspan").append("<span class='projectlist' id='p_add'>add</span>");	
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
			    var id = ($(this).attr("id").split("_"))[1];
			    if(id == "add"){
			    	$("#p_add").remove();
			    	$("#projectspan").append("<input type='text' id='add_project'>");
			    	$("#add_project").focus();
			    	$("#add_project").blur(function(){
			    		$("#add_project").remove();
			    		$("#projectspan").append("<span class='projectlist' id='p_add'>add</span>");
			    		updateelements();
			    	});
			    	$("#add_project").keyup(function(event){
					    if(event.keyCode == 13){
					    	var new_project = $("#add_project").val();				    
					    	$("#add_project").remove();
					    	addProject(new_project);					    	
					    }
					});
			    }
		});
	}
	
});
