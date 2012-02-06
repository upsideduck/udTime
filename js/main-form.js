$(document).ready(function() {
	
	$("#mainform_submit").button();
	$("#result").hide();
	
	$("#free_choices").hide().buttonset();
    $("#work_choices").hide().buttonset();
	$("#break_choices").hide().buttonset();
	$("#clock").text("0:00:00");
	
	var main_timer;
	var currenttime = new Date();
	var checkboxsaved;
	
	checkStatus();
	
	function onSuccessExec(xml) {
		var notificationtype = "";
		var messages = [];
		
		if ($("result", xml).attr("success") == "true")
		{ 
			notificationtype = "result";
			checkStatus();
		}
		else
		{
			$(checkboxsaved).prop('checked', true);
			notificationtype = "error";
		}
		
		$(xml).find("message").each(function()
		{
		   	messages.push($(this).text());
		});
		$("#result").showNotification(notificationtype,messages);
	}
	
	function checkStatus() {
	
		$.ajax({
		   type: "POST",
		   url: "proc/process_currentperiod.php",
		   dataType: "xml",
		   success: typeFetched
		});
	}
	
	function typeFetched (xml) {
		var project = $(xml).find("project").text();
	   	var type = $(xml).find("type").text();  
	   	var starttime = $(xml).find("starttime").text();   	
	   	var allbreaktime = $(xml).find("allBreakTime").text();   
	   	var comment =  $(xml).find("comment").text(); 
	   	var time = 10;
<<<<<<< HEAD

=======
	   	
>>>>>>> parent of 86fefc5... Clean up problems
	   	if(project == "") project = "none";	 
	   	
	   	$('#mainForm input[type="radio"]').each(function(){
      		$(this).checked = false;  
      		$(this).next().attr('aria-pressed', false).removeClass("ui-state-active");
  		});
	  	   	
	   	switch (type){
	   		case '':
	   			$("#free_choices").show()
	   			$("#free_choices input:radio:first").attr('checked', true);
	   			$("#f_work").next().attr('aria-pressed', true).addClass("ui-state-active");
	   			$("#work_choices").hide();
	   			$("#break_choices").hide();
				$("#clock").text("0:00:00");
	   			$("#script").val("proc/process_newperiod.php");
	   			clearInterval(main_timer);
	   			$("#mfheader").text("User free");
	   			$("#comment_header").text("Comment: ");
	   			$("#mf_project").text(project);
	   			$("#comment").val(comment);
	   			$("#now").attr('checked', true );
	   			break;
	   		case 'work':
	   			$("#free_choices").hide();
	   			$("#work_choices").show();
	   			$("#work_choices input:radio:first").attr('checked', true);
	   			$("#w_break").next().attr('aria-pressed', true).addClass("ui-state-active");
	   			$("#break_choices").hide();
	   			$("#script").val("proc/process_ongoingperiod.php");
	   			$("#mfheader").text("User working");
	   			$("#comment").val(comment);
	   			$("#comment_header").text("Work comment: ");
	   			$("#mf_project").text(project);
	   			clearInterval(main_timer);
	   			$("#clock").text(updateClock(starttime, allbreaktime));
	   			main_timer = setInterval(function()
	   				{
	   				$("#clock").text(updateClock(starttime, allbreaktime));
	   				}
	   				, 1000);
   				$("#clock").show();
   				$("#now").attr('checked', true );
	   			break;
	   		case 'break':
	   			$("#free_choices").hide();
	   			$("#work_choices").hide();
	   			$("#break_choices").show();
	   			$("#break_choices input:radio:first").attr('checked', true);
	   			$("#b_break").next().attr('aria-pressed', true).addClass("ui-state-active");
	   			$("#script").val("proc/process_endbreak.php");
	   			$("#mfheader").text("User on break"); 				   			
	   			$("#comment_header").text("Break comment: ");
	   			$("#comment").val("");
	   			$("#mf_project").text(project);
	   			clearInterval(main_timer);
	   			$("#clock").text(updateClock(starttime, allbreaktime));
	   			main_timer = setInterval(function()
	   				{
	   				$("#clock").text(updateClock(starttime, allbreaktime));
	   				}
	   				, 1000);
	   			$("#clock").show();
	   			$("#now").attr('checked', true );
	   			break;
		}
	}
	
	$("#mainForm").submit(function(event) {
		//$("#result").hide();
	    /* stop form from submitting normally */
	    event.preventDefault();
	    
	    var type = $("input[name='type']:checked").val(),
	    	comment = $("input#comment").val(),
	    	url = $("input#script").val();
	    
	    checkboxsaved = "#"+$("input[name='type']:checked").attr("id");	
	    $("input[name='type']").prop('checked', false);
	    	
	    if($("#now").attr('checked')) 
	    {
	    	var timestamp = "";
	    } 
	    else 
	    {
	    	var timestamp = $("#time").val();
	    }
	    
	    $.ajax({
	       type: "POST",
	       data: { type : type, comment : comment, timestamp : timestamp},
	       url: url,
	       dataType: "xml",
	       success: onSuccessExec
	    });
	    
	    
	});
	currentHours = currenttime.getHours();
	currentMinutes = currenttime.getMinutes();
	currentHours = ( currentHours < 10 ? "0" : "" ) + currentHours;
	currentMinutes = ( currentMinutes < 10 ? "0" : "" ) + currentMinutes;
	
	$("#time").val(currentHours + ":" + currentMinutes);
	$("#time").click(function(){ 
		$("#now").prop('checked', false );
	}); 
	/*$("#time").timePicker({
		startTime: new Date(0,0,0,currenttime.getHours()-2,Math.round(currenttime.getMinutes()/10)*10,0),  // Using string. Can take string or Date object.
		endTime: new Date(0,0,0,currenttime.getHours()+2,Math.round(currenttime.getMinutes()/10)*10,0),  // Using Date object.
		show24Hours: true,
		separator:':',
		step: 5});
		*/
	$("#time").timepicker({});
	$("#time").mask("99:99",{placeholder: "0"});
	setInterval(function()
	   				{
	   					checkForUpdate();
	   				}
	   				, 1000);
	function checkForUpdate() {
	if(updateMainForm) {
		updateMainForm = false;
		checkStatus();	
	}
	
}
});

var updateMainForm = false;