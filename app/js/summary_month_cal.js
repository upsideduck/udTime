$(document).ready(function() {
	function dateTimeFormat(d, dateOnly){
		var year = d.getFullYear();
		var month = d.getMonth()+1;
		var day = d.getDate();
		var hours = d.getHours();
		var minutes = d.getMinutes();
		var seconds = d.getSeconds(); 
		
		month = ( month < 10 ? "0" : "" ) + month;
		day = ( day < 10 ? "0" : "" ) + day;
		hours = ( hours < 10 ? "0" : "" ) + hours;
  		minutes = ( minutes < 10 ? "0" : "" ) + minutes;
		seconds = ( seconds < 10 ? "0" : "" ) + seconds;

	   if(dateOnly == true) return year+"-"+month+"-"+day;
		else return year+"-"+month+"-"+day+" "+hours+":"+minutes+":"+seconds;
    }
	
	var year = $(this).getUrlParam("y");
	var month = $(this).getUrlParam("m");
	
	var calendar = $('#calendar').fullCalendar({
		header: {
			left: 'prev,next today',
			center: 'title',
			right: ''
		},
		year: year,
		month: month-1,
	    firstDay: 1,
		selectable: true,
		selectHelper: true,
		select: function(start, end, allDay, jsEvent, view) {
			    $("#choice_start").val(dateTimeFormat(start,true));
				$("#choice_end").val(dateTimeFormat(end, true));
				$("#choice_work_alt").show();
				$("#choice_break_alt").hide();
		    	$("#choice_free_alt").show();
		    	$("#choice_asworktime_alt").show();
				$("#choiceModal").modal("show");
				//calendar.fullCalendar('unselect');
		},
		editable: true,
		eventRender: function (event, element) {
            if (!event.url)
            {
            	var content = "";
            	if(event.type == "Break"){
            		content += "<table class='table' style='width:200px;'><tr><th>Start</th><td>"+dateTimeFormat(event.start)+"</td></tr><tr><th>End</th><td>"+dateTimeFormat(event.end)+"</td></tr></table>";
	            	content += "<a href='#' class='updateBreak' data-toggle='modal' period-id='"+event.itemid+"'><i class='icon-pencil'></i></a> <a href='#' class='deleteBreak' period-id='"+event.itemid+"'><i class='icon-trash'></i></a>";
            	}else if(event.type == "Work"){
            		content += "<table class='table' style='width:200px;'><tr><th>Start</th><td>"+dateTimeFormat(event.start)+"</td></tr><tr><th>End</th><td>"+dateTimeFormat(event.end)+"</td></tr></table>";
	            	content += "<a href='#' class='addBreak' data-toggle='modal' period-id='"+event.itemid+"' start-time='"+dateTimeFormat(event.start)+"' end-time='"+dateTimeFormat(event.end)+"'><i class='icon-plus-sign'></i></a> <a href='#' class='updateWork' data-toggle='modal' period-id='"+event.itemid+"'><i class='icon-pencil'></i></a> <a href='#' class='deleteWork' period-id='"+event.itemid+"'><i class='icon-trash'></i></a>";
            	}else{
            		content += "<table class='table' style='width:200px;'><tr><th>Date</th><td>"+dateTimeFormat(event.start,true)+"</td></tr><tr><th>Time</th><td>"+event.title+"</td></tr></table>";
            		content += "<a href='#' class='update"+event.type+"' data-toggle='modal' period-id='"+event.itemid+"'><i class='icon-pencil'></i></a> <a href='#' class='delete"+event.type+"' period-id='"+event.itemid+"'><i class='icon-trash'></i></a>";
            		}
                element.popover({
                    placement: 'right',
                    html:true,                        
                    title: "<h4>"+event.type+"</h4>",
                    content: content                                
                });
                 $('body').on('click', function (e) {
                    if (!element.is(e.target) && element.has(e.target).length === 0 && $('.popover').has(e.target).length === 0)
                        element.popover('hide');
                });
			}
		},
	    events: function(start, end, callback) {
	    	var date = $('#calendar').fullCalendar('getDate');
	    	var month_int = date.getMonth()+1;
	    	var year_int = date.getFullYear();
	        $.ajax({
			   type: "POST",
			   url: "proc/process_fetchperiods.php",
			   data: { m : month_int, y : year_int	 },
			   dataType: "xml",
			   success: function(xml){
				   			var events = [];
				   			$(xml).find('againstworktime').each(function() {
					   			var date = $(this).find('date');
					   			var id = $(this).find('id');
					   			var time = $(this).find('time').text();
	
					   			events.push({
						   			title: "Time off "+time,
						   			start: date.text(),
						   			color: "green",
						   			itemid: id.text(),
						   			type: "Free"
						   		});
						   	});
						   	$(xml).find('asworktime').each(function() {
					   			var date = $(this).find('date');
					   			var id = $(this).find('id');
					   			var time = $(this).find('time').text();
					   		
					   			
					   			events.push({
						   			title: "asworktime "+time,
						   			start: date.text(),
						   			color: "red",
						   			itemid: id.text(),
						   			type: "asworktime"
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
						   			type: "Work"
						   		});
						   	});
						   	$(xml).find('userinfo').each(function() {
					   			var statsstartdate = $(this).find('statsstartdate');
					   			var registerdate = $(this).find('registerdate');
					   			
					   			events.push({
						   			title: "Registered",
						   			start: registerdate.text(),
						   			color: "black"
						   		});
						   		events.push({
						   			title: "Stats begin->",
						   			start: statsstartdate.text(),
						   			color: "black"
						   		});
						   	});

						   	refreshStats();	
						   	callback(events);	
						   	}
			});
			 
	    }, 
	    /*eventClick: function(calEvent, jsEvent, view) {
	    	var type = calEvent.type;
	    	if(type == "asworktime"){
	    		$('#ui-dialog-title-detail-fv-dialog-form').text('Details of asworktime');
	       		$("#d_fv_itemid").val(calEvent.itemid);
	       		$("#d_fv_type").val(calEvent.type);
	       		$("#detail-fv-dialog-form" ).dialog( "open" );
	       	}
	       	else if(type == "againstworktime"){
	    		$('#ui-dialog-title-detail-fv-dialog-form').text('Details of time off');
		       	$("#d_fv_itemid").val(calEvent.itemid);
	       		$("#d_fv_type").val(calEvent.type);
	       		$("#detail-fv-dialog-form" ).dialog( "open" );
	       	}

	   }*/
	});
	
	function refreshStats(){
		var date = $('#calendar').fullCalendar('getDate');
	    var month_int = date.getMonth()+1;
	    var year_int = date.getFullYear();
		$.ajax({
			type: "POST",
			url: "proc/process_statistics.php",
			data: { stattype : "month", statmonth: month_int, statyear: year_int },
			dataType: "xml",
			success: function(xml){
				if ($("result", xml).attr("success") == "true") { 
					var towork = $(xml).find('towork');
					var worked = $(xml).find('worked');
					var asworktime = $(xml).find('asworktime');
					var againstworktime = $(xml).find('againstworktime');
					
					var sumtowork = parseInt(towork.text()) - parseInt(againstworktime.text());
					var sumworked = parseInt(worked.text()) + parseInt(asworktime.text());
					
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
	
 $("#gocalendar").click(function(e){
  	 e.preventDefault();
   	 $('#calendar').fullCalendar("refetchEvents");
  });
  $("#golist").click(function(e){
  	 e.preventDefault();
	 location.href="summary.php?summary=month_view&m="+$("#pagemonth").val()+"&y="+$("#pageyear").val();
  });  
	
});	


