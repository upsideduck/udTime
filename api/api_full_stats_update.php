<?php
/************************************************************************
/*
/*	All stats updated
/*
/*
/*	Requires: 		func_misc.php
/*					
/*	
/*	Post values:	
/*
/*	Output:			$xml_output - $result_arr as xml
/*
/************************************************************************/
$result_arr = null;

$regiserspan = new timespan($user->statsstartdate, time());
$result_arr = timespan::updateStats($regiserspan);
$xml_output .= resultXMLoutput($result_arr, "fullstatsupdate");

?>