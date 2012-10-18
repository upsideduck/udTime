$(document).ready(function() {
	$(function() {
		$( "#radio_set_type" ).buttonset();
	});
		
	$("#time").mask("99:99:99",{placeholder: "0"});

	var year = $(this).getUrlParam("y");
	var week = $(this).getUrlParam("w");
	
	var d = w2date(year,week);
	var month = d.getMonth();
	var day = d.getDate();
	
	var calendar = $('#calendar').fullCalendar({
		header: {
			left: 'prev,next today',
			center: 'title',
			right: ''
		},
		defaultView: 'agendaWeek',
		columnFormat: {
			week: 'ddd',
		},
		year: year,
		month: month,
		date: day,
	    timeFormat: {
		    // for agendaWeek and agendaDay
		    agenda: 'h:mm{ - h:mm}', // 5:00 - 6:30
		
		    // for all other views
		    '': 'h(:mm)t'            // 7p
		},
		selectable: true,
		selectHelper: true,
		select: function(start, end, allDay) {
			$("#starttimeform").text(start.toLocaleDateString());
			$("#endtimeform").text(end.toLocaleDateString());
			$("#add-fv-dialog-form" ).dialog( "open" );
			calendar.fullCalendar('unselect');
		},
		editable: false,
	    events: function(start, end, callback) {
	    	var date = $('#calendar').fullCalendar('getDate');
	    	var week_int = date.getWeek();
	    	var year_int = date.getFullYear();
	        $.ajax({
			   type: "POST",
			   url: "proc/process_fetchperiods.php",
			   data: { w : week_int, y : year_int	 },
			   dataType: "xml",
			   success: function(xml){
				   			var events = [];
				   			$(xml).find('freeday').each(function() {
					   			var date = $(this).find('date');
					   			var id = $(this).find('id');
					   			var timeStr = $(this).find('time');
					   			var timeInt = parseInt(timeStr.text());
					   			var timeHour = Math.floor(timeInt/60/60);
					   			var timeMin = Math.floor((timeInt-timeHour*60*60)/60);
					   			timeHour = ( timeHour < 10 ? "0" : "" ) + timeHour;
					   			timeMin = ( timeMin < 10 ? "0" : "" ) + timeMin;

					   			events.push({
						   			title: "Time off "+timeHour+":"+timeMin,
						   			start: date.text(),
						   			color: "green",
						   			itemid: id.text(),
						   			type: "freeday",
						   			allDay: true
						   		});
						   	});
						   	$(xml).find('vacationday').each(function() {
					   			var date = $(this).find('date');
					   			var id = $(this).find('id');
					   			var timeStr = $(this).find('time');
					   			var timeInt = parseInt(timeStr.text());
					   			var timeHour = Math.floor(timeInt/60/60);
					   			var timeMin = Math.floor((timeInt-timeHour*60*60)/60);
					   			timeHour = ( timeHour < 10 ? "0" : "" ) + timeHour;
					   			timeMin = ( timeMin < 10 ? "0" : "" ) + timeMin;
					   			
					   			events.push({
						   			title: "Vacation "+timeHour+":"+timeMin,
						   			start: date.text(),
						   			color: "red",
						   			itemid: id.text(),
						   			type: "vacationday",
						   			allDay: true
						   		});
						   	});
				   			$(xml).find('period').each(function() {
				   			    var timeInt = 0;
					   			var starttime = $(this).find('>starttime');
					   			var endtime = $(this).find('>endtime');
					   			var id = $(this).find('id');
					   			timeInt = parseInt(endtime.text()) - parseInt(starttime.text());
					   			$(this).find('break').each(function() {
					   				timeInt = timeInt - parseInt($(this).find('>endtime').text()) + parseInt($(this).find('>starttime').text());
					   			});
					   			
					   			var timeHour = Math.floor(timeInt/60/60);
					   			var timeMin = Math.floor((timeInt-timeHour*60*60)/60);
					   			timeHour = ( timeHour < 10 ? "0" : "" ) + timeHour;
					   			timeMin = ( timeMin < 10 ? "0" : "" ) + timeMin;
					   			
					   			events.push({
						   			title: "Work "+timeHour+":"+timeMin,
						   			start: starttime.text(),
						   			end:  endtime.text(),
						   			color: "blue",
						   			itemid: id.text(),
						   			type: "work",
						   			allDay: false
						   		});
						   	});
						   	$(xml).find('userinfo').each(function() {
					   			var statsstartdate = $(this).find('statsstartdate');
					   			var registerdate = $(this).find('registerdate');
					   			
					   			events.push({
						   			title: "Registered",
						   			start: registerdate.text(),
						   			color: "black",
						   			allDay: true
						   		});
						   		events.push({
						   			title: "Stats begin->",
						   			start: statsstartdate.text(),
						   			color: "black",
						   			allDay: true
						   		});
						   	});

						   	refreshStats();	
						   	callback(events);	
						   	}
			});
			 
	    }, 
	    eventClick: function(calEvent, jsEvent, view) {
	    	var type = calEvent.type;
	    	if(type == "vacationday"){
	    		$('#ui-dialog-title-detail-fv-dialog-form').text('Details of vacation');
	       		$("#d_fv_itemid").val(calEvent.itemid);
	       		$("#d_fv_type").val(calEvent.type);
	       		$("#detail-fv-dialog-form" ).dialog( "open" );
	       	}
	       	else if(type == "freeday"){
	    		$('#ui-dialog-title-detail-fv-dialog-form').text('Details of time off');
		       	$("#d_fv_itemid").val(calEvent.itemid);
	       		$("#d_fv_type").val(calEvent.type);
	       		$("#detail-fv-dialog-form" ).dialog( "open" );
	       	}

	   }
	});
	
	function refreshStats(){
		var date = $('#calendar').fullCalendar('getDate');
	    var week_int = date.getWeek();
	    var year_int = date.getFullYear();
		$.ajax({
			type: "POST",
			url: "proc/process_statistics.php",
			data: { type : "week", week: week_int, year: year_int },
			dataType: "xml",
			success: function(xml){
				if ($("result", xml).attr("success") == "true") { 
					var towork = $(xml).find('towork');
					var worked = $(xml).find('worked');
					var vacation = $(xml).find('vacation');
					var timeoff = $(xml).find('timeoff');
					
					var sumtowork = parseInt(towork.text()) - parseInt(timeoff.text());
					var sumworked = parseInt(worked.text()) + parseInt(vacation.text());
					
					var timeHour, timeMin, timeSec;
				
					timeHour = Math.floor(sumtowork/60/60);
					timeMin = Math.floor((sumtowork-timeHour*60*60)/60);
					timeSec = Math.floor((sumtowork-timeHour*60*60 - timeMin*60)/60);
					timeHour = ( timeHour < 10 ? "0" : "" ) + timeHour;
					timeMin = ( timeMin < 10 ? "0" : "" ) + timeMin;
					timeSec = ( timeSec < 10 ? "0" : "" ) + timeSec;
					$("#towork").text(timeHour+":"+timeMin+":"+timeSec);
					timeHour = Math.floor(sumworked/60/60);
					timeMin = Math.floor((sumworked-timeHour*60*60)/60);
					timeSec = Math.floor((sumworked-timeHour*60*60 - timeMin*60)/60);
					timeHour = ( timeHour < 10 ? "0" : "" ) + timeHour;
					timeMin = ( timeMin < 10 ? "0" : "" ) + timeMin;
					timeSec = ( timeSec < 10 ? "0" : "" ) + timeSec;
					$("#worked").text(timeHour+":"+timeMin+":"+timeSec);
				}else{
					$("#towork").text("Not available yet");
					$("#worked").text("Not available yet");
				}
			}
		 });
	}
	
	$( "#add-fv-dialog-form" ).dialog({
		autoOpen: false,
		height: 240,
		width: 350,
		modal: true,
		buttons: {
			"Add event": function() {
				var type = $("input[name='type']:checked").val(),
				timeStr = $("input#time").val(),
				starttime = Date.parse($("#starttimeform").text())/1000,
				endtime = Date.parse($("#endtimeform").text())/1000+5;		//+5 to include next day 
				
				var timeArr = timeStr.split(":");
				var time = parseInt(timeArr[0])*60*60+ parseInt(timeArr[1])*60+parseInt(timeArr[2]);
				
				$.ajax({
				   type: "POST",
				   url: "proc/process_"+type+".php",
				   data: { starttime : starttime, endtime : endtime, time : time },
				   dataType: "xml",
				   success: function(xml){
				 	  $( "#add-fv-dialog-form" ).dialog( "close" );
				 	  $("#calendar").fullCalendar( 'refetchEvents' )
				   },
				   error: function(xhr, textStatus, errorThrown){
				       alert('Request failed');
				       $( "#add-fv-dialog-form" ).dialog( "close" );
				       $("#calendar").fullCalendar( 'refetchEvents' )
		
				    }
				});

			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			
		}
	});

	$( "#detail-fv-dialog-form" ).dialog({
		autoOpen: false,
		height: 160,
		width: 350,
		modal: true,
		buttons: {
			"Delete": function() {
				var itemid = $("#d_fv_itemid").val();
				var type = $("#d_fv_type").val();	
				$.ajax({
				   type: "POST",
				   url: "proc/process_remove"+type+".php",
				   data: { itemid : itemid },
				   dataType: "xml",
				   success: function(xml){
				 	  $( "#detail-fv-dialog-form" ).dialog( "close" );
				 	  $("#calendar").fullCalendar( 'refetchEvents' )
				   },
				   error: function(xhr, textStatus, errorThrown){
				       alert('Request failed');
				       $( "#detail-fv-dialog-form" ).dialog( "close" );
				       $("#calendar").fullCalendar( 'refetchEvents' )
		
				    }
				 });

			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			
		}
	});


    $('#button').hover(
    function () {
		$('ul.the_menu').slideToggle('fast');
    },
     function () {
		$('ul.the_menu').slideToggle('fast');
    });
	
});	


