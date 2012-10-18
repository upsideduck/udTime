$(document).ready(function() {
	initList();
	
	var summary_type = $(this).getUrlParam("summary");
	if(summary_type){
		summary_type = summary_type.split("_");
		if(summary_type[0] == "month") colorcode("month");
		else colorcode("week");
	}
});

function initList() {
	$(".break_list").hide();
	
	$(".break_list")
		.before("&nbsp;&nbsp;&nbsp;<a href='#' class='toggle_list'>+</a>");
	
	$("a.toggle_list")
			.click(function(e) {
			e.preventDefault();
			$(this).next().slideToggle();
		
	});
}

function colorcode(type) {
	$("."+type+"list_diff").each(function(){
		if($(this).text().charAt(0) == "-") $(this).addClass("neg");
		else $(this).addClass("pos");
	});
	if($("#"+type+"list_alltotals").text().charAt(0) == "-") $("#"+type+"list_alltotals").addClass("neg");
	else $("#"+type+"list_alltotals").addClass("pos");
	
}