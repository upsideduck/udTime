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
/*	Output:			
/*
/************************************************************************/
$result_arr = null;

$regiserspan = new timespan($user->statsstartdate, time());
$result_arr = timespan::updateStats($regiserspan);
$output->results['fullstatsupdate'] = $result_arr;

?>