var updateStatTable;

function timeToDate(date,addDays,dateOnly){
		var d = new Date(date);
		d.setDate(d.getDate()+addDays);
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


$(document).ready(function() {

  
  
  var daysarray = {"":'--',1:'Mon',2:'Tue',3:'Wed',4:'Thu',5:'Fri',6:'Sat',7:'Sun'};
  updateStatTable = function(){

  	$.ajax({
		type: "GET",
		url: "proc/process_weekview.php",
		data: {w: $("#pageweek").val(),y: $("#pageyear").val()},
		dataType: "xml"
		}).done(function( xml ) {
			
			$("#weekView").empty();
			$("#weekView").append("<tr><th>Day</th><th>Date</th><th>Start</th><th>Breaks</th><th>End</th><th>Reduction</th><th>Addition</th><th>Worked time (dec)</th><th></th><th></th></tr>");
			$("#sumsubheader").html("Week: "+$("#pageweek").val()+" - Year: "+$("#pageyear").val());
			var days = $(xml).find("weekinfo").find("days").text();
			var prevperiodday = 0;
			var fromDate = $(xml).find("weekinfo").find("fromdate").text();
			for(var i = 1; i <= 7; i++){
				var addButton = "<a href='#' class='addChoice' data-toggle='modal' period-id='' start-time='"+timeToDate(fromDate,(i-1),true)+"' end-time='"+timeToDate(fromDate,(i-1),true)+"'><i class='icon-plus-sign'></i></a>";
				if($(xml).find("d"+i).text() != ""){
					$(xml).find("d"+i).each(function(){
						
						var start = $(this).find("start").text(),
						end = $(this).find("end").text(),
						worked = $(this).find("worked").text();
						workeddec = $(this).find("workeddec").text(),
						id = $(this).find("id").text();
						
						//Do not print just zeros, easier to read
						($(this).find("break").text() != "00:00:00") ? breaks = $(this).find("break").text() : breaks = "";
						($(this).find("againstworktime").text() != "00:00:00") ? free = $(this).find("againstworktime").text() : free = "";
						($(this).find("asworktime").text() != "00:00:00") ? vac = $(this).find("asworktime").text() : vac = "";
						
						var breaks, free, vac, day, date;
						// Only print day and date if new day
						day = $(this).find("day").text();
						date = $(this).find("date").text();
					
						if(day == prevperiodday){
							vac = "";
							free = "";
							day = "";
							date = ""; 
							addButton = "";
						} else {
							date = $(this).find("date").text();
							prevperiodday = day;
						}
						var buttons = "";
						if($(this).find("id").text() != "") buttons += "<a href='#' class='updateWork' data-toggle='modal' period-id='"+id+"'><i class='icon-pencil'></i></a> <a href='#' class='deleteWork' period-id='"+id+"'><i class='icon-trash'></i></a>";
						$("#weekView").append("<tr><td>"+daysarray[day]+"</td><td>"+date+"</td><td>"+start+"</td><td>"+breaks+"</td><td>"+end+"</td><td>"+free+"</td><td>"+vac+"</td><td>"+worked+" ("+workeddec+")</td><td>"+buttons+"</td><td>"+addButton+"</td></tr>");
					});
				}else{
					$("#weekView").append("<tr><td>"+daysarray[parseInt(i)]+"</td><td></td><td></td><td></td><td></td><td></td><td></td><td>00:00:00 (0.00)</td><td></td><td>"+addButton+"</td></tr>");
				}
			}
			$("#pageweek").attr("next",$(xml).find("weekinfo").find("nextweek").text());
			$("#pageweek").attr("prev",$(xml).find("weekinfo").find("prevweek").text());			
			$("#pageyear").attr("next",$(xml).find("weekinfo").find("nextyear").text());
			$("#pageyear").attr("prev",$(xml).find("weekinfo").find("prevyear").text());
			var sumtoworkinclfree = $(xml).find("weekinfo").find("sumtoworkinclfree").text();
			var sumworkedinclvac =  $(xml).find("weekinfo").find("sumworkedinclvac").text();
			$("#sumworked").html(sumworkedinclvac);
			$("#sumtowork").html(sumtoworkinclfree);
			$("#weeklist_alltotals").html((sumworkedinclvac-sumtoworkinclfree).toFixed(2));
			colorcode("week");
			
			
		}).fail(function() {
		
		});

	}
  
  $("#goprev").click(function(e){
  	 e.preventDefault();
  	 $("#pageweek").val($("#pageweek").attr("prev"));
  	 $("#pageyear").val($("#pageyear").attr("prev"));
  	 updateStatTable();
	
  });
  $("#gonext").click(function(e){
  	 e.preventDefault();
	  $("#pageweek").val($("#pageweek").attr("next"));
  	 $("#pageyear").val($("#pageyear").attr("next"));
  	 updateStatTable();
  });

  $("#gocalendar").click(function(e){
  	 e.preventDefault();
  	 location.href="summary.php?summary=week_cal&w="+$("#pageweek").val()+"&y="+$("#pageyear").val();	
  });
  $("#golist").click(function(e){
  	 e.preventDefault();
	 location.href="summary.php?summary=week_view&w="+$("#pageweek").val()+"&y="+$("#pageyear").val();
  });  
  updateStatTable();
	
});	


