var updateStatTable;
var numberperpage = 10;
var showpages = 10;
var numberwt = 0;
var active = 1;
var current_first_page = 1;
$(document).ready(function() {

  updateStatTable = function(page){
  	$.ajax({
		type: "GET",
		url: "proc/process_weektotals.php",
		//data: data,
		dataType: "xml"
		}).done(function( xml ) {
			var first = $(xml).find("weektotal:first");
			var last = $(xml).find("weektotal:last");
			$("#weekTotals").empty();
			$("#weekTotals").append("<tr><th>Year</th><th>Week</th><th>Worked</th><th>Differece</th><th>Total difference</th><th></th></tr>");
			$("#sumsubheader").html("Week stats: "+$(first).find("year").text()+" W"+$(first).find("week").text()+" - "+$(last).find("year").text()+" W"+$(last).find("week").text());
			var weektotals = $(xml).find("weektotal").get();
			numberwt = weektotals.length;

			updatePagination(current_first_page,(current_first_page+showpages-1),page);
			
			var from = -numberperpage*page;
			var to = from + numberperpage;
			if(to == 0){
				var slicedwt = weektotals.slice(from);
			}else{
				var slicedwt = weektotals.slice(from,to);
			}
			$(slicedwt.reverse()).each(function(index){
				var year = $(this).find("year").text(), 
				week = $(this).find("week").text(), 
				worked = $(this).find("workedtime").text(),
				diff = $(this).find("weekdifftime").text(),
				totaldiff = $(this).find("totaldifftime").text();
				var colorclass;
				if(diff[0] == "-") colorclass = "neg";
				else colorclass = "pos";
				$("#weekTotals").append("<tr><td>"+year+"</td><td>"+week+"</td><td>"+worked+"</td><td class='"+colorclass+"'>"+diff+"</td><td>"+totaldiff+"</td><td><a href='summary.php?summary=week_view&w="+week+"&y="+year+"'><i class='icon-eye-open'></i></a></td></tr>");
			});
		}).fail(function() {
		});

	}
	
	function updatePagination(from,to){
		if(current_first_page+showpages > Math.ceil(numberwt/numberperpage)) to = Math.ceil(numberwt/numberperpage);
		$("#paginationwt").empty();
		for(var i = from; i <= to; i++){
			if(active == i) $("#paginationwt").append("<li class='active'><a href='#' class='newpage'>"+i+"</a></li>");	
			else $("#paginationwt").append("<li><a href='#' class='newpage'>"+i+"</a></li>");	
		}
		if(numberwt > showpages*numberperpage) {
			 if(from == 1) $("#paginationwt").prepend("<li class='disabled'><a class='' start='"+(from-showpages)+"'>&laquo;</a></li>");
			 else $("#paginationwt").prepend("<li class=''><a href='#' class='prevpage' start='"+(from-showpages)+"'>&laquo;</a></li>");
			 if(to * numberperpage > numberwt) $("#paginationwt").append("<li class='disabled'><a class='' start='"+(from+showpages)+"'>&raquo;</a></li>");
			 else $("#paginationwt").append("<li class=''><a href='#' class='nextpage' start='"+(from+showpages)+"'>&raquo;</a></li>");
		}
	}
  
  updateStatTable(active);
 
  $(document).on("click", ".newpage", function(e){
  	 e.preventDefault();
  	 active = $(this).text();
  	  updateStatTable(active);
  });
  
  $(document).on("click", ".prevpage", function(e){
  	 e.preventDefault();
  	 current_first_page = parseInt($(this).attr("start"));
  	 updatePagination(current_first_page,current_first_page+showpages-1,0);
  });
  
  $(document).on("click", ".nextpage", function(e){
  	 e.preventDefault();
  	 current_first_page = parseInt($(this).attr("start"));
  	 updatePagination(current_first_page,current_first_page+showpages-1,0);
  });
	
});	


