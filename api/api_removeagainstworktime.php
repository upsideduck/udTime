<?php 
/************************************************************************
/*
/*	remove free days
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
	$result_arr = removeagainstworktime($id);
}else{
	$result_arr[0] = false;
	$result_arr[] = "Item id not a valid id";
}

$output->results['removeagainstworktime'] = $result_arr;
?>