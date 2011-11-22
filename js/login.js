$(document).ready(function() {

  $("#loading").hide();
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
  
  $("#loading").ajaxStart(function(){
      $(this).fadeIn("slow");;
   }).ajaxStop(function(){
      $(this).fadeOut('slow');
   });
});


function onSuccExecLogin( xml ) {
	$("#result").empty();
	if ($("result", xml).attr("success") == "true")
	{ 
		location.reload();
	}
	else
	{
  	$(xml).find("message").each(function()
		  {
		    $("#result").append($(this).text() + "<br />");
		  });
	}
}
	  