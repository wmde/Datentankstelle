<?php
/**
 * main entry point
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 */

### configuration ###
require_once( "local-config.php" );
if( !defined( "WIKI_API_URL" ) || !defined( "TITLE_CATEGORIES" ) || !defined( "TITLE_SETS" ) ) {
	die( "The application initialization failed, please check your configuration files." );
}

### application initialization ###
session_start();
if ( !file_exists( 'vendor/autoload.php' ) ) {
	die( 'The autoload file does not exist. Please run `composer install`' );
}
require 'vendor/autoload.php';
require_once( 'lib/getid3/getid3.php' );

$dts = new \Datentankstelle\Datentankstelle();
$dts->processRequest();
