$(document).ready(function() {
	
	/*var socket = io.connect('http://doop.johanadell.com:8181');
	socket.emit('subscribe-id', $("#uid").val());
	socket.on('udtime-msg', function (data) {
		//alert("Got Redis");
		checkStatus();
	});*/	

	
	$("#mainform_submit").button();
	//$("#result").hide();
	
	$("#free_choices").hide();
    $("#work_choices").hide();
	$("#break_choices").hide();
	$("#clock").text("0:00:00");
	
	var main_timer;
	var currenttime = new Date();
	var checkboxsaved;
	var userid;
	
	checkStatus();
	
	function onSuccessExec(xml) {
		var notificationtype = "";
		var messages = [];
		
		if ($("result", xml).attr("success") == "true")
		{ 
			notificationtype = "result";
			checkStatus();
			//updateOtherClientsViaRedis();
		}
		else
		{
			//$(checkboxsaved).prop('checked', true);
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
		   success: typeFetched,
		});
	}
	
	function updateOtherClientsViaRedis() {
	
		$.ajax({
		   type: "GET",
		   url: "proc/process_messageredis.php",
		   dataType: "xml",
		   success: function(){
			    //alert('Redis sent');
			  },
				
		});
	}
	
	function typeFetched (xml) {
		var project = $(xml).find("project").text();
	   	var type = $(xml).find("type").text();  
	   	var starttime = $(xml).find("starttime").text();   	
	   	var allbreaktime = $(xml).find("allBreakTime").text();   
	   	var comment =  $(xml).find("comment").text(); 
	   	var time = 10;
	   	if(userid != $(xml).find("member_id").text() && userid == null){
	   		userid = $(xml).find("member_id").text();
	   		//lpStart();
	   	}
	   	

	   	if(project == "") project = "none";	 
	   	
	   	/*$('#mainForm input[type="radio"]').each(function(){
      		$(this).checked = false;  
      		$(this).next().attr('aria-pressed', false).removeClass("ui-state-active");
  		});*/
	  	   	
	   	switch (type){
	   		case '':
	   			$("#free_choices").show()
	   			$("#f_work").addClass("active");
	   			$("#checkedValue").val($("#f_work").val());
	   			$("#work_choices").hide();
	   			$("#break_choices").hide();
				$("#clock").text("0:00:00");
	   			$("#script").val("proc/process_newperiod.php");
	   			clearInterval(main_timer);
	   			$("#mfheader").text("User free");
	   			$("#comment_header").text("Comment: ");
	   			$("#mf_project").text(project);
	   			$("#comment").val(comment);
	   			$("#now").prop('checked', true );
	   			break;
	   		case 'work':
	   			$("#free_choices").hide();
	   			$("#work_choices").show();
	   			$("#w_break").addClass("active");
	   			$("#break_choices").hide();
	   			$("#checkedValue").val($("#w_break").val());
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
   				$("#now").prop('checked', true );
	   			break;
	   		case 'break':
	   			$("#free_choices").hide();
	   			$("#work_choices").hide();
	   			$("#break_choices").show();
	   			$("#checkedValue").val($("#b_break").val());
	   			$("#b_break").addClass("active");
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
	   			$("#now").prop('checked', true );
	   			break;
		}
	}
	$(".checkedValue .btn").click(function() {
	    // whenever a button is clicked, set the hidden helper
	    $("#checkedValue").val($(this).val());
	}); 
	
	$("#mainForm").submit(function(event) {
		//$("#result").hide();
	    /* stop form from submitting normally */
	    event.preventDefault();
	    
	    var type = $("#checkedValue").val(),
	    	comment = $("input#comment").val(),
	    	url = $("input#script").val();
	    
	    //checkboxsaved = "#"+$("button[name='type'] .active").attr("id");	
	    //$("input[name='type']").prop('checked', false);
	    	
	    if($("#now").prop('checked')) 
	    {
	    	var timestamp = "";
	    } 
	    else 
	    {
	    	var timestamp = $("#time").val().trim();
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
	$("#timepicker").datetimepicker({
		 pickDate: false,            // disables the date picker
		 pickTime: true,            // disables de time picker
		 pick12HourFormat: false,   // enables the 12-hour format time picker
		 pickSeconds: false,         // disables seconds in the time picker
	});
	/*$("#time").timePicker({
		startTime: new Date(0,0,0,currenttime.getHours()-2,Math.round(currenttime.getMinutes()/10)*10,0),  // Using string. Can take string or Date object.
		endTime: new Date(0,0,0,currenttime.getHours()+2,Math.round(currenttime.getMinutes()/10)*10,0),  // Using Date object.
		show24Hours: true,
		separator:':',
		step: 5});
		*/
	//$("#time").timepicker({});
	$("#time").inputmask({mask:"99:99",placeholder: "0"});
	
	function statsFetched(xml) {
		if ($("result", xml).attr("success") == "true")
		{ 
			var type = $(xml).find("period > type").text();
			
			if($("#up"+type).length) {
				var time = parseInt($(xml).find("worktime").text());
				
  				var Hours = Math.floor(time/60/60);
  				var Minutes = Math.floor((time - Hours*60*60)/60);
  				var Seconds = (time - Hours*60*60 - Minutes*60);

  				// Pad the minutes and seconds with leading zeros, if required
  				Hours = ( Hours < 10 ? "0" : "" ) + Hours;
  				Minutes = ( Minutes < 10 ? "0" : "" ) + Minutes;
				Seconds = ( Seconds < 10 ? "0" : "" ) + Seconds;
  
  				// Compose the string for display
  				var TimeString = Hours + ":" + Minutes + ":" + Seconds;
				$("#up"+type).text(TimeString);
			}
		}
		

	}
	
	/*function lpOnComplete(data) {
    	if(data != null) {
    		if(data.event == "period updated") checkStatus();
    		if(data.event == "period ended") {
    			var stattypes = ["today","thisweek","thismonth"];
    			$.each(stattypes, function(i,val){
    			$.ajax({
	      			 type: "POST",
	      			 data: { type : val },
	       			 url: "proc/process_statistics.php",
	       			 dataType: "xml",
	       			 success: statsFetched
	    			});
	    		});
    			checkStatus();
    		}
    	}
    	
    	// do more processing
    	lpStart();
	};
 
	function lpStart() {
		$.ajax({
		   type: "POST",
		   url: "events.php",
		   data: {id: userid},
		   dataType: "json",
		   success: lpOnComplete
		});
	};*/
	
});