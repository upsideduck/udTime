<?php 
/************************************************************************
/*
/*	remove asworktime days
/*
/*
/*	Requires: 			
/*	
/*	Post values:	
/*
/*	Output:			
/*
/************************************************************************/

$result_arr = null;

$id = clean($_REQUEST['itemid']);

if(is_numeric($id)){	
	$result_arr = removeasworktime($id);
}else{
	$result_arr[0] = false;
	$result_arr[] = "Item id not a valid id";
}

$output->results['removeasworktime'] = $result_arr;
?>