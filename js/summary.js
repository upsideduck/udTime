$(document).ready(function() {
	initList();
	
	
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