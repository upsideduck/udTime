$(document).ready(function() {
	var changeHeader = $(this).getUrlParam("a");	
	$("#page_headline").text(changeHeader);
	
		$("#holidaytime").mask("99:99",{placeholder: " "});
});