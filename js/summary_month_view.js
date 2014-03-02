var updateStatTable;

$(document).ready(function() {
	var months = ['---','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  updateStatTable = function(){
  	$.ajax({
		type: "GET",
		url: "proc/process_monthview.php",
		data: {m: $("#pagemonth").val(),y: $("#pageyear").val()},
		dataType: "xml"
		}).done(function( xml ) {
			
			$("#monthView").empty();
			$("#monthView").append("<tr><th>Day</th><th>Start</th><th>Breaks</th><th>End</th><th>Reduction</th><th>Addition</th><th>Worked time (dec)</th><th></th><th></th></tr>");
			$("#sumsubheader").html("Month: "+months[parseInt($("#pagemonth").val())]+" - Year: "+$("#pageyear").val());
			var days = $(xml).find("monthinfo").find("days").text();
			var prevperiodday = 0;
			for(var i = 1; i <= parseInt($(xml).find("monthinfo").find("days").text()); i++){
				var thisDate = $("#pageyear").val()+"-"+(parseInt($("#pagemonth").val()) < 10 ? "0"+parseInt($("#pagemonth").val()) : parseInt($("#pagemonth").val()))+"-"+(i < 10 ? "0"+i : i);
				var addButton = "<a href='#' class='addChoice' data-toggle='modal' period-id='' start-time='"+thisDate+"' end-time='"+thisDate+"'><i class='icon-plus-sign'></i></a>";

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
						if($(this).find("id").text() != "") buttons = "<a href='#' class='updateWork' data-toggle='modal' period-id='"+id+"'><i class='icon-pencil'></i></a> <a href='#' class='deleteWork' period-id='"+id+"'><i class='icon-trash'></i></a>";
						$("#monthView").append("<tr><td>"+day+"</td><td>"+start+"</td><td>"+breaks+"</td><td>"+end+"</td><td>"+free+"</td><td>"+vac+"</td><td>"+worked+" ("+workeddec+")</td><td>"+buttons+"</td><td>"+addButton+"</td></tr>");
					});
				}else{
					$("#monthView").append("<tr><td>"+parseInt(i)+"</td><td></td><td></td><td></td><td></td><td></td><td>00:00:00 (0.00)</td><td></td><td>"+addButton+"</td></tr>");
				}
			}
			$("#pagemonth").attr("next",$(xml).find("monthinfo").find("nextmonth").text());
			$("#pagemonth").attr("prev",$(xml).find("monthinfo").find("prevmonth").text());			
			$("#pageyear").attr("next",$(xml).find("monthinfo").find("nextyear").text());
			$("#pageyear").attr("prev",$(xml).find("monthinfo").find("prevyear").text());
			var sumtoworkinclfree = $(xml).find("monthinfo").find("sumtoworkinclfree").text();
			var sumworkedinclvac =  $(xml).find("monthinfo").find("sumworkedinclvac").text();
			$("#sumworked").html(sumworkedinclvac);
			$("#sumtowork").html(sumtoworkinclfree);
			$("#monthlist_alltotals").html((sumworkedinclvac-sumtoworkinclfree).toFixed(2));
			colorcode("month");
			
			
		}).fail(function() {
		});

	}
  
  $("#goprev").click(function(e){
  	 e.preventDefault();
  	 $("#pagemonth").val($("#pagemonth").attr("prev"));
  	 $("#pageyear").val($("#pageyear").attr("prev"));
  	 updateStatTable();
	
  });
  $("#gonext").click(function(e){
  	 e.preventDefault();
	  $("#pagemonth").val($("#pagemonth").attr("next"));
  	 $("#pageyear").val($("#pageyear").attr("next"));
  	 updateStatTable();
  });

  $("#gocalendar").click(function(e){
  	 e.preventDefault();
  	 location.href="summary.php?summary=month_cal&m="+$("#pagemonth").val()+"&y="+$("#pageyear").val();	
  });
  $("#golist").click(function(e){
  	 e.preventDefault();
	 location.href="summary.php?summary=month_view&m="+$("#pagemonth").val()+"&y="+$("#pageyear").val();
  }); 
    $("#gocsv").click(function(e){
  	 e.preventDefault();
	 location.href="download.php?type=csv&m="+$("#pagemonth").val()+"&y="+$("#pageyear").val();
  });  
  updateStatTable();
	
});	


