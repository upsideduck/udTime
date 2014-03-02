$(document).ready(function() {

 /* attach a submit handler to the form */
  $("#loginForm").submit(function(event) {
    /* stop form from submitting normally */
    event.preventDefault();
	
	var user = $("input#username").val(),
		pass = $("input#password").val();
	
	
    $.ajax({
       type: "POST",
       data: { username : user, password : pass },
       url: "proc/process_login.php",
       dataType: "xml",
       success: onSuccExecLogin
    });
  });
  
});


function onSuccExecLogin( xml ) {
	$("#result").empty();
	if ($("result", xml).attr("success") == "true")
	{ 
		window.location.href = "index.php";
	}
	else
	{
  		var notificationtype = "error";
		var messages = [];
		
		$(xml).find("message").each(function()
		{
			messages.push($(this).text());
		});
		$("#result").showNotification(notificationtype,messages);
	}
}
	 