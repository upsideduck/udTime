$(document).ready(function() {
	var sumpage = "";
	var filename = window.location.pathname.split( '/' ).pop();
	if (filename == "summary.php") 
		sumpage = $(this).getUrlParam("summary");
	/*********************
	** 	Arbitrary functions
	**/
	
	function timestampToDate(timestamp,dateOnly){
		var d = new Date(timestamp*1000);
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
	
	
	
	function responseFromServer(xml,box) {
		var notificationtype = "";
		var messages = [];
		
		if ($("result", xml).attr("success") == "true") notificationtype = "result";
		else notificationtype = "error";
		
		$(xml).find("message").each(function()
		{
		   	messages.push($(this).text());
		});
		$(box).showNotification(notificationtype,messages);
	}
	
	
	
	/*$('.datetimepicker').datetimepicker({
		xalanguage: 'pt-BR',
		pickDate: true,
		pickSeconds: true,
	});*/
	

	/*********************
	** 	Work functions
	**/
		
	$("#work-loader-body").hide();
	$('#workDelete').hide();
		
	$(document).on("click", ".updateWork", function (e) {
		$('#workModal').modal('show');
		$("#work-loader-body").show();
		$("#work-body").hide();
		$('#workDelete').show();
	    $.ajax({
			  type: "GET",
			  url: "proc/process_fetchperiods.php",
			  data: { wid: $(this).attr('period-id') },
			  dataType: "xml"
			}).done(function( xml ) {
				$("#work-loader-body").hide();
				$("#work-body").show();
				$(xml).find('period').each(function() {
					$('#work_id').val($(this).find('>id').text());
					$('#work_stime').val(timestampToDate($(this).find('>starttime').text()));
					$('#work_etime').val(timestampToDate($(this).find('>endtime').text()));
				});
				
				if($(xml).find('break').text() != ""){
					
					$('#workBreaks').append("<label for='work_breaks'>Breaks during period:</label><table id='workBreaksTable' name='work_breaks' class='table table-striped'><tr><th>Start time</th><th>End time</th><th></th></tr></table>");

					$(xml).find('break').each(function() {
						var id = $(this).find("id").text(); 
						$('#workBreaksTable').append("<tr><td>"+timestampToDate($(this).find('starttime').text())+"</td><td>"+timestampToDate($(this).find('endtime').text())+"</td><td><a href='#' class='updateBreak' data-toggle='modal' period-id='"+id+"'><i class='icon-pencil'></i></a> <a href='#' class='deleteBreak' period-id='"+id+"'><i class='icon-trash'></i></a></td></tr>");
					});
					
				}
				
				if($(xml).find('asworktime').text() != "" || $(xml).find('againstworktime').text() != ""){
					
					$('#workVacFree').append("<label for='work_vacfree'>Reduced work time and time counted as work time during period:</label><table id='workVacFreeTable' name='work_vacfree' class='table table-striped'><tr><th>As work time</th><th></th><th>Reduced work time</th><th></th></tr></table>");

					var vactime = $(xml).find('asworktime').find("time").text();
					var againstworktime = $(xml).find('againstworktime').find("time").text();
						
					$('#workVacFreeTable').append("<tr><td>"+vactime+"</td><td></td><td>"+againstworktime+"</td><td></td></tr>");				
				}
		
			}).fail(function() { alert("error"); 	
		});
	});
	
	$(document).on("click", ".deleteWork", function (e) {

	    	$.ajax({
			  type: "GET",
			  url: "proc/process_removework.php",
			  data: { id: $(this).attr('period-id') },
			  dataType: "xml"
			}).done(function( xml ) {
				if ($("result", xml).attr("success") == "true"){
					responseFromServer(xml,"#result");
				}else{
					responseFromServer(xml,"#workResult");
				}
				if(sumpage == 'week_cal' || sumpage == 'month_cal'){
					$('#calendar').fullCalendar('refetchEvents');
				}
				else if(sumpage == 'week_view' || sumpage == 'month_view'){
					updateStatTable();
				}
				
			}).fail(function() { alert("error"); 	
		});
	});
	
	$("#addWork").on("click", function (e) {
		$('#workModal').modal('show');
		$("#work-loader-body").hide();
		$("#work-body").show();
	});
	
	$("#workSave").on("click", function (e) {
		$("#workCancel").addClass("disabled")
		$("#workSave").addClass("disabled")
		var url;
		var data;
		if($('#work_id').val() != ""){
			url = "proc/process_updatework.php";
			data = { id: $('#work_id').val(), start_time:  $('#work_stime').val(), end_time: $('#work_etime').val()}
		}else{
			url = "proc/process_setwork.php";
			data = {start_time:  $('#work_stime').val(), end_time: $('#work_etime').val()}
		}
	    $.ajax({
			  type: "GET",
			  url: url,
			  data: data,
			  dataType: "xml"
			}).done(function( xml ) {
				if ($("result", xml).attr("success") == "true"){
					/*setTimeout(function() {
					      $('#workModal').modal('hide');
					}, 2000);*/
					responseFromServer(xml,"#result");
					$('#workModal').modal('hide');
					$("#workCancel").removeClass("disabled");
					$("#workSave").removeClass("disabled");
				}else{
					responseFromServer(xml,"#workResult");
					$("#workCancel").removeClass("disabled");
					$("#workSave").removeClass("disabled");
				}
				if(sumpage == 'week_cal' || sumpage == 'month_cal'){
					$('#calendar').fullCalendar('refetchEvents');
				}else if(sumpage == 'week_view' || sumpage == 'month_view'){
					updateStatTable();
				}
			}).fail(function() {
				$("#workCancel").removeClass("disabled");
				$("#workSave").removeClass("disabled");
				$("#workResult").append('<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">&times;</button>Error occured</div>');
		
		});
	});
	
	$('#workModal').on('hidden', function () {
    	$("#workResult").empty();
    	$("#workBreaks").empty();
    	$("#workVacFree").empty();
    	$('#work_id').val("");
		$('#work_stime').val("");
		$('#work_etime').val("");
		$('#workDelete').hide();
	});
	

	/*********************
	** 	Break functions
	**/
	
	$("#break-loader-body").hide();
	$('#breakDelete').hide();

	$(document).on("click",".updateBreak", function (e) {
		$('#breakModal').modal('show');
		$("#break-loader-body").show();
		$("#break-body").hide();
		$('#breakDelete').show();
		$('#workModal').modal('hide');
	    $.ajax({
			  type: "GET",
			  url: "proc/process_fetchperiods.php",
			  data: { bid: $(this).attr('period-id') },
			  dataType: "xml"
			}).done(function( xml ) {
				$("#break-loader-body").hide();
				$("#break-body").show();
				var theBreak = $(xml).find('break')
				var theWork = $(xml).find('period');
				if(theBreak){
					$('#breakWorkTime').append("<tr><th>Start time</th><th>End time</th></tr>");
					$('#breakWorkTime').append("<tr><td>"+timestampToDate(theWork.find('>starttime').text())+"</td><td>"+timestampToDate(theWork.find('>endtime').text())+"</td></tr>");
					$('#break_id').val(theBreak.find('id').text());
					$('#break_stime').val(timestampToDate(theBreak.find('starttime').text()));
					$('#break_etime').val(timestampToDate(theBreak.find('endtime').text()));
				};
				
			}).fail(function() { alert("error"); 	
		});
	});
	
	$(document).on("click", ".deleteBreak", function (e) {

	    	$.ajax({
			  type: "GET",
			  url: "proc/process_removebreak.php",
			  data: { id: $(this).attr('period-id') },
			  dataType: "xml"
			}).done(function( xml ) {
				if ($("result", xml).attr("success") == "true"){
					responseFromServer(xml,"#result");
					$('#workModal').modal('hide');
				}else{
					responseFromServer(xml,"#breakResult");
				}
				if(sumpage == 'week_cal' || sumpage == 'month_cal'){
					$('#calendar').fullCalendar('refetchEvents');
				}else if(sumpage == 'week_view' || sumpage == 'month_view'){
					updateStatTable();
				}
				
			}).fail(function() { alert("error"); 	
		});
	});
	
	
	$(document).on("click", ".addBreak", function (e) {
		$("#break_stime").val(($(this).attr("start-time")));
		$("#break_etime").val(($(this).attr("end-time")));
		$('#breakModal').modal('show');
		$("#break-loader-body").hide();
		$("#break-body").show();
	});	
	
	$("#breakSave").on("click", function (e) {
		$("#breakCancel").addClass("disabled")
		$("#breakSave").addClass("disabled")
		var url;
		var data;
		if($('#break_id').val() != ""){
			url = "proc/process_updatebreak.php";
			data = { id: $('#break_id').val(), start_time:  $('#break_stime').val(), end_time: $('#break_etime').val()}
		}else{
			url = "proc/process_setbreak.php";
			data = {start_time:  $('#break_stime').val(), end_time: $('#break_etime').val()}
		}
	    $.ajax({
			  type: "GET",
			  url: url,
			  data: data,
			  dataType: "xml"
			}).done(function( xml ) {
				if ($("result", xml).attr("success") == "true"){
					/*setTimeout(function() {
					      $('#breakModal').modal('hide');
					}, 2000);*/
					responseFromServer(xml,"#result");
					$('#breakModal').modal('hide');
					$("#breakCancel").removeClass("disabled");
					$("#breakSave").removeClass("disabled");
				}else{
					responseFromServer(xml,"#breakResult");
					$("#breakCancel").removeClass("disabled");
					$("#breakSave").removeClass("disabled");
				}
				if(sumpage == 'week_cal' || sumpage == 'month_cal'){
					$('#calendar').fullCalendar('refetchEvents');
				}else if(sumpage == 'week_view' || sumpage == 'month_view'){
					updateStatTable();
				}
			}).fail(function() {
				$("#breakCancel").removeClass("disabled");
				$("#breakSave").removeClass("disabled");
				$("#breakResult").append('<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">&times;</button>Error occured</div>');
		
		});
	});
	
	$('#breakModal').on('hidden', function () {
    	$("#breakResult").empty();
    	$("#breakWorkTime").empty();
    	$('#break_id').val("");
		$('#break_stime').val("");
		$('#break_etime').val("");
		$('#breakDelete').hide();
	});
	
	/*********************
	** 	asworktime functions
	**/
	
	$("#asworktime-loader-body").hide();
	
	$("#addasworktime").on("click", function (e) {
		$('#asworktimeModal').modal('show');
		$("#asworktime-loader-body").hide();
		$("#asworktime-body").show();
	});	
	
	$(document).on("click",".updateasworktime", function (e) {
		$('#asworktimeModal').modal('show');
		$("#asworktime-loader-body").hide();
		$("#asworktime_sdate").prop("disabled",true);
		$("#asworktime_edate").prop("disabled",true);
		$("#asworktime-body").show();
		$.ajax({
			  type: "GET",
			  url: "proc/process_fetchperiods.php",
			  data: { vid: $(this).attr('period-id') },
			  dataType: "xml"
			}).done(function( xml ) {
				$("#asworktime-loader-body").hide();
				$("#asworktime-body").show();
				var theVac = $(xml).find('asworktime');
				if(theVac){
					$('#asworktime_id').val(theVac.find('id').text());
					$('#asworktime_sdate').val(theVac.find('date').text());
					$('#asworktime_edate').val(theVac.find('date').text());
					$('#asworktime_time').val(theVac.find('time').text());
					$('#asworktime_type').val(theVac.find('type').text());
				};
				
			}).fail(function() { alert("error"); 	
		});

	});	
	
	$(document).on("click", ".deleteasworktime", function (e) {

		$.ajax({
			  type: "GET",
			  url: "proc/process_removeasworktime.php",
			  data: { itemid: $(this).attr('period-id') },
			  dataType: "xml"
		}).done(function( xml ) {
				if ($("result", xml).attr("success") == "true"){
					responseFromServer(xml,"#result");
					$('#asworktimeModal').modal('hide');
				}else{
					responseFromServer(xml,"#asworktimeResult");
				}
				if(sumpage == 'week_cal' || sumpage == 'month_cal'){
					$('#calendar').fullCalendar('refetchEvents');
				}else if(sumpage == 'week_view' || sumpage == 'month_view'){
					updateStatTable();
				}
				
			}).fail(function() { alert("error"); 	
		});
	});
	
	$("#asworktimeSave").on("click", function (e) {
		$("#asworktimeCancel").addClass("disabled")
		$("#asworktimeSave").addClass("disabled")
		var url;
		var data;
		var timeStr = $("#asworktime_time").val(), starttime = Date.parse($("#asworktime_sdate").val())/1000,
		endtime = Date.parse($("#asworktime_edate").val())/1000+5;		//+5 to include next day 		
		var timeArr = timeStr.split(":");
		var time = parseInt(timeArr[0])*60*60+ parseInt(timeArr[1])*60+parseInt(timeArr[2]);
		var type =  $("#asworktime_type").val();
		
		if($('#asworktime_id').val() != ""){
			url = "proc/process_updateasworktime.php";
			data = { id: $('#asworktime_id').val(), starttime: starttime, endtime: endtime, time: time, type : type}
		}else{
			url = "proc/process_setasworktime.php";
			data = {starttime:  starttime, endtime: endtime, time: time, type: type};
		}
		
	    $.ajax({
			  type: "GET",
			  url: url,
			  data: data,
			  dataType: "xml"
			}).done(function( xml ) {
				if ($("result", xml).attr("success") == "true"){
					/*setTimeout(function() {
					      $('#breakModal').modal('hide');
					}, 2000);*/
					responseFromServer(xml,"#result");
					$('#asworktimeModal').modal('hide');
					$("#asworktimeCancel").removeClass("disabled");
					$("#asworktimeSave").removeClass("disabled");
				}else{
					responseFromServer(xml,"#asworktimeResult");
					$("#asworktimeCancel").removeClass("disabled");
					$("#asworktimeSave").removeClass("disabled");
				}
				if(sumpage == 'week_cal' || sumpage == 'month_cal'){
					$('#calendar').fullCalendar('refetchEvents');
				}else if(sumpage == 'week_view' || sumpage == 'month_view'){
					updateStatTable();
				}
			}).fail(function() {
				$("#asworktimeCancel").removeClass("disabled");
				$("#asworktimeSave").removeClass("disabled");
				$("#asworktimeResult").append('<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">&times;</button>Error occured</div>');
		
		});
	});
	
	$('#asworktimeModal').on('hidden', function () {
    	$("#asworktimeResult").empty();
    	$('#asworktime_id').val("");
		$('#asworktime_sdate').val("").prop("disabled",false);
		$('#asworktime_edate').val("").prop("disabled",false);
	});

	/*********************
	** 	Free days functions
	**/
	
	$("#free-loader-body").hide();
	
	$("#addFree").on("click", function (e) {
		$('#freeModal').modal('show');
		$("#free-loader-body").hide();
		$("#free-body").show();
	});	
	
	$(document).on("click",".updateFree", function (e) {
		$('#freeModal').modal('show');
		$("#free-loader-body").hide();
		$("#free_sdate").prop("disabled",true);
		$("#free_edate").prop("disabled",true);
		$("#free-body").show();
		$.ajax({
			  type: "GET",
			  url: "proc/process_fetchperiods.php",
			  data: { fid: $(this).attr('period-id') },
			  dataType: "xml"
			}).done(function( xml ) {
				$("#free-loader-body").hide();
				$("#free-body").show();
				var theFree = $(xml).find('againstworktime');
				if(theFree){
					$('#free_id').val(theFree.find('id').text());
					$('#free_sdate').val(theFree.find('date').text());
					$('#free_edate').val(theFree.find('date').text());
					$('#free_time').val(theFree.find('time').text());
					$('#free_type').val(theFree.find('type').text());
				};
				
			}).fail(function() { alert("error"); 	
		});
	});
	
	$(document).on("click", ".deleteFree", function (e) {

	    	$.ajax({
			  	type: "GET",
			  	url: "proc/process_removeagainstworktime.php",
			  	data: { itemid: $(this).attr('period-id') },
			  	dataType: "xml"
			}).done(function( xml ) {
				if ($("result", xml).attr("success") == "true"){
					responseFromServer(xml,"#result");
					$('#freeModal').modal('hide');
				}else{
					responseFromServer(xml,"#freeResult");
				}
				if(sumpage == 'week_cal' || sumpage == 'month_cal'){
					$('#calendar').fullCalendar('refetchEvents');
				}else if(sumpage == 'week_view' || sumpage == 'month_view'){
					updateStatTable();
				}
				
			}).fail(function() { alert("error"); 	
		});
	});
	
	$("#freeSave").on("click", function (e) {
		$("#freeCancel").addClass("disabled")
		$("#freeSave").addClass("disabled")
		var url;
		var data;
		var timeStr = $("#free_time").val(), starttime = Date.parse($("#free_sdate").val())/1000,
		endtime = Date.parse($("#free_edate").val())/1000+5;		//+5 to include next day 		
		var timeArr = timeStr.split(":");
		var time = parseInt(timeArr[0])*60*60+ parseInt(timeArr[1])*60+parseInt(timeArr[2]);
		var type =  $("#free_type").val();

		if($('#free_id').val() != ""){
			url = "proc/process_updateagainstworktime.php";
			data = { id: $('#free_id').val(), starttime: starttime, endtime: endtime, time: time, type : type}
		}else{
			url = "proc/process_setagainstworktime.php";
			data = {starttime:  starttime, endtime: endtime, time: time, type : type};
		}
		
	    $.ajax({
			  type: "GET",
			  url: url,
			  data: data,
			  dataType: "xml"
			}).done(function( xml ) {
				if ($("result", xml).attr("success") == "true"){
					/*setTimeout(function() {
					      $('#breakModal').modal('hide');
					}, 2000);*/
					responseFromServer(xml,"#result");
					$('#freeModal').modal('hide');
					$("#freeCancel").removeClass("disabled");
					$("#freeSave").removeClass("disabled");
				}else{
					responseFromServer(xml,"#freeResult");
					$("#freeCancel").removeClass("disabled");
					$("#freeSave").removeClass("disabled");
				}
				if(sumpage == 'week_cal' || sumpage == 'month_cal'){
					$('#calendar').fullCalendar('refetchEvents');
				}else if(sumpage == 'week_view' || sumpage == 'month_view'){
					updateStatTable();
				}
			}).fail(function() {
				$("#freeCancel").removeClass("disabled");
				$("#freeSave").removeClass("disabled");
				$("#freeResult").append('<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">&times;</button>Error occured</div>');
		
		});
	});
	
	$('#freeModal').on('hidden', function () {
    	$("#freeResult").empty();
    	$('#free_id').val("");
		$('#free_sdate').val("").prop("disabled",false);
		$('#free_edate').val("").prop("disabled",false);
	});
	
	
	/*********************
	** 	choice functions
	**/
	
	$(document).on("click",".addChoice", function (e) {
		$('#choiceModal').modal('show');
		$("#choice_start").val($(this).attr("start-time"));
		$("#choice_end").val($(this).attr("end-time"))
	});
		
	$("#choice_work_btn").on('click',function(){
		$('#choiceModal').modal('hide');
		$('#work_stime').val($("#choice_start").val()+" 08:00:00");
		$('#work_etime').val($("#choice_end").val()+" 17:00:00");
		$('#workModal').modal('show')
	});
	$("#choice_break_btn").on('click',function(){
		$('#choiceModal').modal('hide');
		$('#break_stime').val($("#choice_start").val()+" 08:00:00");
		$('#break_etime').val($("#choice_end").val()+" 17:00:00");
		$('#breakModal').modal('show')
	});
	$("#choice_free_btn").on('click',function(){
		$('#choiceModal').modal('hide');
		$('#free_sdate').val($("#choice_start").val());
		$('#free_edate').val($("#choice_end").val());
		$('#freeModal').modal('show')
	});	
	$("#choice_asworktime_btn").on('click',function(){
		$('#choiceModal').modal('hide');
		$('#asworktime_sdate').val($("#choice_start").val());
		$('#asworktime_edate').val($("#choice_end").val());
		$('#asworktimeModal').modal('show')
	});
	
	$('#choiceModal').on('hidden', function () {
    	$("#choice_start").val();
    	$("#choice_end").val();
    	$("#choice_work_alt").show();
    	$("#choice_break_alt").show();
    	$("#choice_free_alt").show();
    	$("#choice_asworktime_alt").show();
	});

});
