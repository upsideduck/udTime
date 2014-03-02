<?php

      // check if something has been change outside this session
      if(isset($_SESSION["SESS_ACTIVE_PERIOD"]) && $user->activeperiod != $_SESSION["SESS_ACTIVE_PERIOD"]) updateSession($user);
	
	?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> 
<html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width">
        <title>udTime</title>
        <script src="js/jquery.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.10.3.custom.min.js"></script>
		<?php if(!isset($_SESSION['SESS_MEMBER_ID'])) : ?>
		<script type="text/javascript" src="js/login.js"></script>
		<?php endif; ?>
		<?php
		switch (__SCRIPT_NAME__) {
			case "index" :
				include("includes/headers/head_login.php");
				include("includes/headers/head_index.php");
				break;
			case "summary" :
				include("includes/headers/head_summary.php");
				break;
			case "edit" :
				include("includes/headers/head_edit.php");
				break;
			case "profile" :
				include("includes/headers/head_profile.php");
				break;
		}
		?>
		<link href="css/main.css" rel="stylesheet" type="text/css" />
		<link href="css/mainmenu.css" rel="stylesheet" type="text/css" />
		<link href="css/objects.css" rel="stylesheet" type="text/css" />
		<link href="css/jquery-ui-1.10.3.custom.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="css/bootstrap.min.css">       
        <link rel="stylesheet" href="css/bootstrap-responsive.min.css">
		<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
        <script src="js/vendor/modernizr-2.6.2-respond-1.1.0.min.js"></script>
        <script src="js/vendor/bootstrap.min.js"></script>
        <script src="js/main.js"></script>
        <script type="text/javascript" src="js/bootstrap-inputmask.min.js"></script>
		<script type="text/javascript" src="js/notifications.js"></script>
		<script type="text/javascript" src="js/forms.js"></script>
		

    </head>
    <body>
        <!--[if lt IE 7]>
            <p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
        <![endif]-->

        <!-- This code is taken from http://twitter.github.com/bootstrap/examples/hero.html -->

        <div class="navbar navbar-fixed-top">
            <div class="navbar-inner">
                <div class="container">
                    <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </a>
                    <a class="brand" href="index.php">udTime</a>
                    <div class="nav-collapse collapse">
                        <?php if(isset($_SESSION['SESS_MEMBER_ID'])) : ?>
					
                        <ul class="nav">
						    <li><a href='index.php'><i class="icon-home icon-black"></i> Home</a></li>
						    <li class="dropdown">
                            	<a href="#" class="dropdown-toggle" data-toggle="dropdown">Add <b class="caret"></b></a>
                                <ul class="dropdown-menu">
                                    <li><a id="addWork" data-toggle="modal">Work</a></li>
                                    <li><a class="addBreak" data-toggle="modal">Break</a></li>
                                    <li><a id="addFree" data-toggle="modal">Reduced work time</a></li>
                                    <li><a id="addasworktime" data-toggle="modal">As work time</a></li>
                                </ul>
                            </li>
							<li class="dropdown">
                            	<a href="#" class="dropdown-toggle" data-toggle="dropdown">Summary <b class="caret"></b></a>
                                <ul class="dropdown-menu">
                                    <li><a href="summary.php?summary=week_totals">Week totals</a></li>
						            <li><a href="summary.php?summary=month_totals">Month totals</a></li>
                                </ul>
                            </li>
						    <li><a href='profile.php'>Profile</a></li>
						</ul>
						<ul class="nav pull-right">
						     <?php if ($_SESSION['SESS_MEMBER_ID']): ?><li><a href='logout.php'>Logout</a></li> <?php endif; ?>
						</ul>
						<?php else: ?>
					
                        <form class="navbar-form pull-right" id="loginForm" method="post" action="/">
                            <input class="span2" type="text" placeholder="Username" name="username" id="username">
                            <input class="span2" type="password" placeholder="Password" name="password" id="password">
                            <button type="submit" class="btn">Sign in</button>
                        </form>
                        <?php endif; ?>
                    </div><!--/.nav-collapse -->
                </div>
            </div>
        </div>
        <?php if(!isset($_SESSION['SESS_MEMBER_ID'])) : ?>
        	
	        <?php if(__SCRIPT_NAME__ == "index") : ?>
		        <header class="jumbotron subhead" id="overview">
				  <div class="container">
				    <h1>udTime</h1>
				    <p class="lead">Time tracking for the work place</p>
				  </div>
				  <a class="extended-badge"><img src="img/logo.png"></a>
		        </header>
		     <?php endif; ?> 

	    <div class="row">
			<div class="span8 offset2">
			<?php include_once("includes/pans/resultpan.php"); ?>
			</div>
			</div>
       <?php endif; ?> 
       <div class="container">

