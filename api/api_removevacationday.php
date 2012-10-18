<?php 
/************************************************************************
/*
/*	remove vacation days
/*
/*
/*	Requires: 			
/*	
/*	Post values:	
/*
/*	Output:			$xml_output - $result_arr as xml
/*
/************************************************************************/

$result_arr = null;

$id = clean($_REQUEST['itemid']);

if(is_numeric($id)){	
	$result_arr = removeVacationDay($id);
}else{
	$result_arr[0] = false;
	$result_arr[] = "Item id not a valid id";
}

$xml_output .= resultXMLoutput($result_arr, "removevacationday");
?>