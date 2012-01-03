<html>
<head>
<script type="text/javascript" src="js/jquery.js"></script>
<style type="text/css">

ul.topnav {
	list-style: none;
	position: relative;
	left: 80px;
	padding: 0px;
	margin: 0;
	float: left;
	background-color: #6493d2;
	-moz-box-shadow: 0 0 5px #000000;
	-webkit-box-shadow: 0 0 5px #000000;
}
ul.topnav li {
	height: 37px;
	float: left;
	margin: 0px;
	padding-right: 10px;
	position: relative; /*--Declare X and Y axis base for sub navigation--*/
	   cursor: pointer;

}
ul.topnav li a{
	display: block;
	text-decoration: none;
	float: left;
	padding-top: 10px;
	padding-left: 20px;
	padding-right: 5px;
	color: #fff;
	z-index: 200;
}
ul.topnav li:first-child a {
	padding-left: 8px !important;
	z-index: 199999;
}
ul.topnav li:last-child {
	padding-right: 0px !important;
}
ul.topnav li .leftsep {
 	position:absolute;           
    width:100%;    
    height:80%;
    /* display under the Anchor tag */
    z-index:10;       
}
ul.topnav li .rightsep {
	/* mouseover image  */
    border-right: 2px solid #fff; 
   	/* must be postion absolute     */
 	position:absolute;           
    width:100%;    
    height:20px;
    /* display under the Anchor tag */
    z-index:10;       
    margin-top: 8px;
}
ul.topnav li:first-child .leftsep {
	background:url(images/leftmenuend.png) no-repeat left center; 
	left: -21px; 
	height: 100%;
	border: 0px;
}
ul.topnav li ul.subnav li .leftsep {
	display: none;    
}   
ul.topnav li:last-child .rightsep {
	background:url(images/rightmenuend.png) no-repeat right center; 
	left: 21px; 
	height: 100%;
	border: 0px;
	margin: 0px !important;
}
ul.topnav li ul.subnav li .rightsep {
	display: none;    
}       
ul.topnav li .hover {
	/* mouseover image  */
    background:url(images/downarrow.png) no-repeat center center;  
    background-color: #c0c0c0;     
    opacity: 0.5;
   	/* must be postion absolute     */
 	position:absolute;           
    /*  width, height, left and top to fill the whole LI item   */
    width:100%;    
    height:100%;
    /* display under the Anchor tag */
    z-index:0;      
    /* hide it by default   */
    display:none;   
    cursor: pointer;
}   
ul.topnav li span { /*--Drop down trigger styles--*/
	width: 10px;
	height: 40px;
	float: left;
	padding-right: 3px;
	background: url(images/downarrow.png) no-repeat left center;
}
ul.topnav li ul.subnav {
	list-style: none;
	position: absolute; /*--Important - Keeps subnav from affecting main navigation flow--*/
	left: 0; top: 37px;
	margin-left: 0; padding: 0;
	display: none;
	float: left;
	width: 170px;
	-moz-box-shadow: 0 1px 5px #000000;
	-webkit-box-shadow: 0 1px 5px #000000;
	box-shadow: 0px 1px 5px #000000;
	
}
ul.topnav li ul.subnav li{
	margin: 0; padding: 0;
	background-color: #6493d2;
	clear: both;
	width: 170px;
	z-index: 0;
}
html ul.topnav li ul.subnav li a {
	float: left;
	width: 135px;
	padding-left: 20px;
}
html ul.topnav li ul.subnav li:first-child a {
	padding-left: 20px !important;
}
body {
	margin: 0px;
	background-color: #f0f0f0;
}
</style>
<script type="text/javascript">
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

</script>
</head>
<body>
<div class="container">
<ul class="topnav">
    <li><a href="#">Home</a></li>
    <li>
        <a href="#">Summary</a>
        <ul class="subnav">
            <li><a href="http://www.google.com">Sub Nav Link</a></li>
            <li><a href="#">Sub Nav Link</a></li>
            <li><a href="#">Sub Nav Link</a></li>
            <li><a href="#">Sub Nav Link</a></li>	
        </ul>
    </li>
    <li>
        <a href="#">Profile</a>
        <ul class="subnav">
            <li><a href="#">Sub Nav Link</a></li>
            <li><a href="#">Sub Nav Link</a></li>
        </ul>
    </li>
</ul>
</div>
</body>
</html>