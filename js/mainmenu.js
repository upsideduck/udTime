$(document).ready(function(){

	$("ul.subnav").parent().append("<span></span>"); //Only shows drop down trigger when js is enabled (Adds empty span tag after ul.subnav*)
	$('ul.topnav li').prepend('<div class="leftsep"></div><div class="hover"></div><div class="rightsep"></div>');
	//$('ul.topnav li').prepend('<div class="hover"></div>');
	$('.hover').each(function(){
		var link = $(this).parent().find("a").attr("href");
		$(this).parent().click(
		function()
		{
		    window.location = link;
		    return false;
		});
	});
	$("ul.topnav li").hover( 
		function() {  
			$(this).find("ul.subnav").slideDown('fast').show(); //Drop down the subnav on clic      
			$(this).children('.hover').stop(true, true).fadeIn('fast');     
	    }, 
	    function() {
	        $(this).children('.hover').stop(true, true).fadeOut('fast');   
	        $(this).find("ul.subnav").slideUp('fast'); //When the mouse hovers out of the subnav, move it back up     
	    });

});
