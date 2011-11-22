$(document).ready(function() {
	
	$("#free_choices").hide();
	$("#work_choices").hide();
	$("#break_choices").hide();
	$("#clock").text("0:00:00").hide();
	
	var main_timer;
	var currenttime = new Date();
	
	checkStatus();
	
	function onSuccessExec(xml) {
		$("#result").empty();
		if ($("result", xml).attr("success") == "true")
		{ 
			$(".notification_mainform").css("color", "#5a7800");
			checkStatus();
	
		}
		else
		{
			$(".notification_mainform").css("color", "#f90");
		}
		$(xml).find("message").each(function()
		{
		    $("#result").append($(this).text() + "<br />");
		});
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
	
	   	var type = $(xml).find("type").text();  
	   	var starttime = $(xml).find("starttime").text();   	
	   	var allbreaktime = $(xml).find("allBreakTime").text();   
	   	var comment =  $(xml).find("comment").text(); 
	   	var time = 10;	
	   	
	   	switch (type){
	   		case '':
	   			$("#free_choices").show()
	   			$("#free_choices input:radio:first").attr('checked', true);
	   			$("#work_choices").hide();
	   			$("#break_choices").hide();
	   			$("#clock").hide();
	   			$("#script").val("proc/process_newperiod.php");
	   			clearInterval(main_timer);
	   			$("#period_description").text("User free");
	   			$("#comment").text(comment);
	   			$("#now").attr('checked', true );
	   			break;
	   		case 'work':
	   			$("#free_choices").hide();
	   			$("#work_choices").show();
	   			$("#work_choices input:radio:first").attr('checked', true);
	   			$("#break_choices").hide();
	   			$("#script").val("proc/process_ongoingperiod.php");
	   			$("#period_description").text("User working");
	   			$("#comment").text(comment);
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
	   			$("#script").val("proc/process_endbreak.php");
	   			$("#period_description").text("User on break");
	   			$("#comment").text("");
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
	    /* stop form from submitting normally */
	    event.preventDefault();
	    
	    var type = $("input[name='type']:checked").val(),
	    	comment = $("input#comment").val(),
	    	url = $("input#script").val();
	    	
	    	$("input[name='type']").prop('checked', false);
	    	
	    if($("#now").attr('checked')) 
	    {
	    	var timestamp = "";
	    } 
	    else 
	    {
	    	var timestamp = $("#mtime").val();
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
	$("#time").timePicker({
		startTime: new Date(0,0,0,currenttime.getHours()-2,Math.round(currenttime.getMinutes()/10)*10,0),  // Using string. Can take string or Date object.
		endTime: new Date(0,0,0,currenttime.getHours()+2,Math.round(currenttime.getMinutes()/10)*10,0),  // Using Date object.
		show24Hours: true,
		separator:':',
		step: 5});
		
	$("#time").mask("99:99",{placeholder: " "});
		
	
});