<?php
define(__SCRIPT_NAME__,"download");
	
session_start();
      //Include database connection details
require_once('includes/config.php');

$filename = __SITE_BASE__.'tmp/'.$_SESSION['SESS_MEMBER_ID'].'_month.csv'; // of course find the exact filename....        
header( 'Pragma: public' ); // required
header( 'Expires: 0' );
header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
header( 'Cache-Control: private', false ); // required for certain browsers 
header( 'Content-Type: text/csv' );

header( 'Content-Disposition: attachment; filename="month.csv";' );
header( 'Content-Transfer-Encoding: binary' );
header( 'Content-Length: ' . filesize( $filename ) );

readfile( $filename );

session_write_close();
exit;
?>