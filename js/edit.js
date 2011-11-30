$(document).ready(function() {
	var changeHeader = $(this).getUrlParam("a");	
	$("#page_headline").text(changeHeader);
	$("#start_date").mask("9999-99-99",{placeholder: "0"});
	$("#end_date").mask("9999-99-99",{placeholder: "0"});
	$("#start_time").mask("99:99:99",{placeholder: "0"});
	$("#end_time").mask("99:99:99",{placeholder: "0"});
	$("#holidaytime").mask("99:99",{placeholder: "0"});
});