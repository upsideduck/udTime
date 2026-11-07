<?php 

    $id = clean($_REQUEST["id"]);  
	$output->results["removebreak"] = removeBreak($id);

?>