$(document).ready(function() {
	var g_week = $("input#week").val(),
 		g_year = $("input#year").val();

	$("#update_weekForm").submit(function(event) {
	  /* stop form from submitting normally */
		event.preventDefault();
		
					
		$(".week_container_list").fadeTo('slow', 0.2, function(){
			$.ajax({
				type: "POST",
		    	data: { week : g_week, year : g_year },
			    url: "proc/process_update_week.php",
			    dataType: "xml",
			    success: onSuccessExec
			});
			
		 }); 
	});
	
	function onSuccessExec(xml) {
		$("#result").empty();
		if ($("result", xml).attr("success") == "true")
		{ 
			$(".notification_mainform").css("color", "#5a7800");
	
		}
		else
		{
			$(".notification_mainform").css("color", "#f90");
		}
		$(xml).find("message").each(function()
		{
		    $("#result").append($(this).text() + "<br />");
		});
		$.ajax({
			type: "POST",
			data: { w : g_week, y : g_year },
		    url: "includes/week_list.php",
		    dataType: "html",
		    success: function(html){
		   	 	$(".week_container_list").html(html);
		   	 	initList();
		   		$(".week_container_list").fadeTo('slow',1);
		    }
		});  
	}	
	
	
});

