$(document).ready(function() {

	var d = new Date();	
	var year = d.getFullYear();
	var month = d.getMonth()+1;
	var day = d.getDate();
	var today = year+"-"+month+"-"+day;

	$("#vac_sdate").datepicker({
		dateFormat: 'yy-mm-dd',
		showWeek: true,
		changeMonth: true,
		changeYear: true,
		defaultDate: today,
		constrainInput: true,
		firstDay: 1
	});
	$("#vac_sdate").inputmask({mask: "9999-99-99", placeholder: "0"});

	$("#vac_edate").datepicker({
		dateFormat: 'yy-mm-dd',
		showWeek: true,
		changeMonth: true,
		changeYear: true,
		defaultDate: today,
		constrainInput: true,
		firstDay: 1 
	});
	$("#vac_edate").inputmask({mask: "9999-99-99", placeholder: "0"});
	/*$("#vac_time").timepicker({
		timeFormat: 'hh:mm:ss', 
	});*/
	$("#vac_time").inputmask({mask: "99:99:99", placeholder: "0"});						

	$("#subpage_menu_add_vac").click(function(){
		$( "#add-vac-dialog-form" ).dialog( "open" );
	});
	$("#subpage_menu_add_free").click(function(){
		$( "#add-free-dialog-form" ).dialog( "open" );
	});
	$("#subpage_menu_add_work").click(function(){
		$( "#add-work-dialog-form" ).dialog( "open" );
	});
	$("#subpage_menu_add_break").click(function(){
		$( "#add-break-dialog-form" ).dialog( "open" );
	});

	$( "#add-vac-dialog-form" ).dialog({
		autoOpen: true,
		height: 240,
		width: 350,
		modal: true,
		buttons: [{
        	text: "Add",
        	"id": "vac_add_button",
        	click: function () {
            	var timeStr = $("#vac_time").val(), starttime = Date.parse($("#vac_sdate").val())/1000,
				endtime = Date.parse($("#vac_edate").val())/1000+5;		//+5 to include next day 		
				var timeArr = timeStr.split(":");
				var time = parseInt(timeArr[0])*60*60+ parseInt(timeArr[1])*60+parseInt(timeArr[2]);
				$.ajax({
				   type: "POST",
				   url: "proc/process_setasworktime.php",
				   data: { starttime : starttime, endtime : endtime, time : time },
				   dataType: "xml",
				   success: function(xml){
				 	  $( "#add-vac-dialog-form" ).dialog( "close" );
				 	  window.location.reload();
				 	  
				   },
				   error: function(xhr, textStatus, errorThrown){
				       alert('Request failed');
				       $( "#add-vac-dialog-form" ).dialog( "close" );
		
				    }
				});
				},

	        }, {
	        text: "Cancel",
	        click: function () {
	            $( this ).dialog( "close" );
	        },
        }],
		close: function() {
			
		}
	});
	
	$("#free_sdate").datepicker({
		dateFormat: 'yy-mm-dd',
		showWeek: true,
		changeMonth: true,
		changeYear: true,
		defaultDate: today,
		constrainInput: true,
		firstDay: 1  
	});
	$("#free_sdate").inputmask({mask: "9999-99-99", placeholder: "0"});
	$("#free_edate").datepicker({
		dateFormat: 'yy-mm-dd',
		showWeek: true,
		changeMonth: true,
		changeYear: true,
		defaultDate: today,
		constrainInput: true,
		firstDay: 1  
	});
	$("#free_edate").inputmask({mask: "9999-99-99", placeholder: "0"});
	/*$("#free_time").timepicker({
		timeFormat: 'hh:mm:ss', 
	});*/
	$("#free_time").inputmask({mask: "99:99:99", placeholder: "0"});						

	$( "#add-free-dialog-form" ).dialog({
		autoOpen: false,
		height: 240,
		width: 350,
		modal: true,
		buttons: {
			"Add": function() {
				var timeStr = $("#free_time").val(), starttime = Date.parse($("#free_sdate").val())/1000,
				endtime = Date.parse($("#free_edate").val())/1000+5;		//+5 to include next day 		
				var timeArr = timeStr.split(":");
				var time = parseInt(timeArr[0])*60*60+ parseInt(timeArr[1])*60+parseInt(timeArr[2]);
				
				$.ajax({
				   type: "POST",
				   url: "proc/process_setagainstworktime.php",
				   data: { starttime : starttime, endtime : endtime, time : time },
				   dataType: "xml",
				   success: function(xml){
				 	  $( "#add-free-dialog-form" ).dialog( "close" );
				 	   window.location.reload();
				   },
				   error: function(xhr, textStatus, errorThrown){
				       alert('Request failed');
				       $( "#add-free-dialog-form" ).dialog( "close" );
		
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
	

	$("#work_stime").datetimepicker({
		dateFormat: 'yy-mm-dd',
		timeFormat: 'hh:mm:ss',
		showWeek: true,
		changeMonth: true,
		changeYear: true,
		defaultDate: today,
		constrainInput: true,
		firstDay: 1  
	});
	$("#work_etime").datetimepicker({
		dateFormat: 'yy-mm-dd',
		timeFormat: 'hh:mm:ss',
		showWeek: true,
		changeMonth: true,
		changeYear: true,
		defaultDate: today,
		constrainInput: true,
		firstDay: 1  	
	});			

	$( "#add-work-dialog-form" ).dialog({
		autoOpen: false,
		height: 180,
		width: 350,
		modal: true,
		buttons: {
			"Add": function() {
				var stime = $("#work_stime").val(), etime = $("#work_etime").val();		 		
				$.ajax({
				   type: "POST",
				   url: "proc/process_setwork.php",
				   data: { start_time : stime, end_time : etime },
				   dataType: "xml",
				   success: function(xml){
				 	  $( "#add-work-dialog-form" ).dialog( "close" );
				 	  window.location.reload();
				   },
				   error: function(xhr, textStatus, errorThrown){
				       alert('Request failed');
				       $( "#add-work-dialog-form" ).dialog( "close" );
		
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
	
	$("#break_stime").datetimepicker({
		dateFormat: 'yy-mm-dd',
		timeFormat: 'hh:mm:ss',
		showWeek: true,
		changeMonth: true,
		changeYear: true,
		defaultDate: today,
		constrainInput: true,
		firstDay: 1  
	});
	$("#break_etime").datetimepicker({
		dateFormat: 'yy-mm-dd',
		timeFormat: 'hh:mm:ss',
		showWeek: true,
		changeMonth: true,
		changeYear: true,
		defaultDate: today,
		constrainInput: true,
		firstDay: 1  	
	});			

	$( "#add-break-dialog-form" ).dialog({
		autoOpen: false,
		height: 180,
		width: 350,
		modal: true,
		buttons: {
			"Add": function() {
				var stime = $("#break_stime").val(), etime = $("#break_etime").val();		 		
				$.ajax({
				   type: "POST",
				   url: "proc/process_setbreak.php",
				   data: { start_time : stime, end_time : etime },
				   dataType: "xml",
				   success: function(xml){
				 	  $( "#add-break-dialog-form" ).dialog( "close" );
				 	   window.location.reload();
				   },
				   error: function(xhr, textStatus, errorThrown){
				       alert('Request failed');
				       $( "#add-break-dialog-form" ).dialog( "close" );
		
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