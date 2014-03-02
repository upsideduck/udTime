var updateStatTable;
var numberperpage = 10;
var showpages = 10;
var numbermt = 0;
var active = 1;
var current_first_page = 1;

$(document).ready(function() {
	
	var months = ['---','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
	
  updateStatTable = function(page){
  	$.ajax({
		type: "GET",
		url: "proc/process_monthtotals.php",
		//data: data,
		dataType: "xml"
		}).done(function( xml ) {
			var first = $(xml).find("monthtotal:first");
			var last = $(xml).find("monthtotal:last");
			$("#monthTotals").empty();
			$("#monthTotals").append("<tr><th>Year</th><th>Month</th><th>Worked</th><th>Differece</th><th>Total difference</th><th></th></tr>");
			$("#sumsubheader").html("Month stats: "+months[parseInt($(first).find("month").text())]+"/"+$(first).find("year").text()+" - "+months[parseInt($(last).find("month").text())]+"/"+$(last).find("year").text());
			
			var monthtotals = $(xml).find("monthtotal").get();
			numbermt = monthtotals.length;

			updatePagination(current_first_page,(current_first_page+showpages-1),page);
			
			var from = -numberperpage*page;
			var to = from + numberperpage;
			if(to == 0){
				var slicedmt = monthtotals.slice(from);
			}else{
				var slicedmt = monthtotals.slice(from,to);
			}
			
			$(slicedmt.reverse()).each(function(){
				var year = $(this).find("year").text(), 
				month = $(this).find("month").text(), 
				worked = $(this).find("workedtime").text(),
				diff = $(this).find("monthdifftime").text(),
				totaldiff = $(this).find("totaldifftime").text();
				var colorclass;
				if(diff[0] == "-") colorclass = "neg";
				else colorclass = "pos";
				$("#monthTotals").append("<tr><td>"+year+"</td><td>"+months[parseInt(month)]+"</td><td>"+worked+"</td><td class='"+colorclass+"'>"+diff+"</td><td>"+totaldiff+"</td><td><a href='summary.php?summary=month_view&m="+month+"&y="+year+"'><i class='icon-eye-open'></i></a></td></tr>");
			});
		}).fail(function() {
		});

	}
  
  updateStatTable(active);

  function updatePagination(from,to){
		if(current_first_page+showpages > Math.ceil(numbermt/numberperpage)) to = Math.ceil(numbermt/numberperpage);
		$("#paginationmt").empty();
		for(var i = from; i <= to; i++){
			if(active == i) $("#paginationmt").append("<li class='active'><a href='#' class='newpage'>"+i+"</a></li>");	
			else $("#paginationmt").append("<li><a href='#' class='newpage'>"+i+"</a></li>");	
		}
		if(numbermt > showpages*numberperpage) {
			 if(from == 1) $("#paginationmt").prepend("<li class='disabled'><a class='' start='"+(from-showpages)+"'>&laquo;</a></li>");
			 else $("#paginationmt").prepend("<li class=''><a href='#' class='prevpage' start='"+(from-showpages)+"'>&laquo;</a></li>");
			 if(to * numberperpage > numbermt) $("#paginationmt").append("<li class='disabled'><a class='' start='"+(from+showpages)+"'>&raquo;</a></li>");
			 else $("#paginationmt").append("<li class=''><a href='#' class='nextpage' start='"+(from+showpages)+"'>&raquo;</a></li>");
		}
	}	
 
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


