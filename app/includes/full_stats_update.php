<script type="text/javascript">
$(document).ready(function() {

	$("#update_statsForm").submit(function(event) {
	  /* stop form from submitting normally */
		event.preventDefault();
					
		$.ajax({
				type: "POST",
			    url: "proc/process_full_stats_update.php",
			    dataType: "xml",
			    success: onSuccessExec
		});

	});
	
	function onSuccessExec(xml) {
		var notificationtype = "";
		var messages = [];
		if ($("result", xml).attr("success") == "true")
		{ 
			notificationtype = "result";
		}
		else
		{
			notificationtype = "error";
		}
		
		$(xml).find("message").each(function()
		{
		   	messages.push($(this).text());
		});
		$("#result").showNotification(notificationtype,messages);
	}		
	
	
});
</script>
<form id='update_statsForm' name='update_stats' method='post' action=''><p>
	<input type='submit' name='button' id='button' value='Update' />
</form>