<?php 

    $id = clean($_REQUEST["id"]);  
	$output->results["removework"] = removeWork($id);

?>