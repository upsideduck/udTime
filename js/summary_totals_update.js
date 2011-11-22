$(document).ready(function() {
	colorcode();
	$("#update_all_weeks").submit(function(event) {
	  /* stop form from submitting normally */
		event.preventDefault();
		
					
		$(".week_totals_list").fadeTo('slow', 0.2, function(){
			$.ajax({
				type: "POST",
		    	url: "proc/process_update_all_weeks.php",
			    dataType: "xml",
			    success: onSuccessExec
			});
			
		 }); 
	});
	
	function colorcode() {
		$(".weeklist_diff").each(function(){
			if($(this).text().charAt(0) == "-") $(this).addClass("neg");
			else $(this).addClass("pos");
		});
		if($("#weeklist_alltotals").text().charAt(0) == "-") $("#weeklist_alltotals").addClass("neg");
		else $("#weeklist_alltotals").addClass("pos");
	}
	
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
		    url: "includes/week_totals.php",
		    dataType: "html",
		    success: function(html){
		   	 	$(".week_totals_list").html(html);
		   		$(".week_totals_list").fadeTo('slow',1);
		   		colorcode();
		    }
		});  
	}	
	
	
});

