$(document).ready(function() {
	var changeHeader = $(this).getUrlParam("a");	
	$("#page_headline").text(changeHeader);
	$("#start_date").inputmask({mask:"9999-99-99",placeholder: "0"});
	$("#end_date").inputmask({mask:"9999-99-99",placeholder: "0"});
	$("#start_time").inputmask({mask:"99:99:99",placeholder: "0"});
	$("#end_time").inputmask({mask:"99:99:99",placeholder: "0"});
	$("#holidaytime").inputmask({mask:"99:99",placeholder: "0"});
});