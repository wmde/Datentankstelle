<?php
### configuration ###
require_once( "local-config.php" );
if( !defined( "WIKI_API_URL" ) || !defined( "TITLE_CATEGORIES" ) || !defined( "TITLE_SETS" ) ) {
	die( "The application initialization failed, please check your configuration files." );
}

### application initialization ###
require_once( "Datentankstelle.php" );
$dts = new Datentankstelle();
$dts->processRequest();
