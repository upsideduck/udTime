<?php
	require_once('auth.php');
    require_once("includes/config.php");
    require_once("func/func_misc.php");
    include("includes/header.php");
?>
<script type="text/javascript" src="js/jquery.qtip-1.0.0-rc3.min.js"></script>
<script type="text/javascript" src="js/jquery.getUrlParam.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput-1.3.min.js"></script>
<link rel='stylesheet' type='text/css' href='css/fullcalendar.css' />
<script type='text/javascript' src='js/fullcalendar.min.js'></script>
<script type='text/javascript'>

	$(document).ready(function() {
		
		$(function() {
			$( "#radio_set_type" ).buttonset();
		});
		
		$("#time").mask("99:99:99",{placeholder: "0"});
	
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
			selectable: true,
			selectHelper: true,
			select: function(start, end, allDay) {
				$("#starttimeform").text(start.toLocaleDateString());
				$("#endtimeform").text(end.toLocaleDateString());
				$("#add-dialog-form" ).dialog( "open" );
				calendar.fullCalendar('unselect');
			},
			editable: true,
			eventRender: function(event, element, view)
			{
				element.qtip({ content:  event.title+"<br>"+event.itemid });
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
							   			type: "freeday"
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
							   			type: "vacationday"
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
							   			type: "work"
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
		    var month_int = date.getMonth()+1;
		    var year_int = date.getFullYear();
			$.ajax({
				type: "POST",
				url: "proc/process_statistics.php",
				data: { type : "month", month: month_int, year: year_int },
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
		

	});

</script>
<style type='text/css'>

	body {
		margin-top: 40px;
		text-align: center;
		font-size: 14px;
		font-family: "Lucida Grande",Helvetica,Arial,Verdana,sans-serif;
		}

	#calendar {
		width: 600px;
		margin: 0 auto;
		}

</style>
	<script>
$(function() {
				
		$( "#add-dialog-form" ).dialog({
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
					 	  $( "#add-dialog-form" ).dialog( "close" );
					 	  $("#calendar").fullCalendar( 'refetchEvents' )
					   },
					   error: function(xhr, textStatus, errorThrown){
					       alert('Request failed');
					       $( "#add-dialog-form" ).dialog( "close" );
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

});
	</script>




<div id="add-dialog-form" title="Add vacation/time off">
	<span id="starttimeform"></span> - <span id="endtimeform"></span>
	<form>
	<fieldset>
		<label for="name">Set time per day</label><br>
		<input type="text" name="name" id="time" class="text ui-widget-content ui-corner-all" value="0" /><br>
		<div id="radio_set_type">
			<label for='b_settimeoff'>Time off</label>
			<input type='radio' name='type' value='setfreedays' id='b_settimeoff' checked/>
		    <label for='b_setvacation'>Vacation</label> 
			<input type='radio' name='type' value='setvacation' id='b_setvacation' />
		</div>
	</fieldset>
	</form>
</div>
<div id="detail-fv-dialog-form" title="Details vacation/time off">
	More info
	<form>
		<input type="hidden" id="d_fv_itemid" value="" />
		<input type="hidden" id="d_fv_type" value="" />
	</form>
</div>
<?php
echo "<div id='calendar'></div>";
echo "To Work this month: <span id='towork'></span> (incl holidays)<br />";
echo "Time worked this month: <span id='worked'></span> (incl vacation)";

$timesspan = new timespan("2012-01-01", "2012-06-06");
echo("<pre>");	
var_dump(workingdaysmonth($_GET['m'],$_GET['y']));

require_once('includes/footer.php');
?>