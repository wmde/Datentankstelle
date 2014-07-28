<?php
/**
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
namespace Datentankstelle;

class Datentankstelle {

	private $_action;
	private $_subject;
	private $_language;
	
	public function __construct() {
		$this->_language = new LanguageHandler();
		$this->_parseQueryString();
	}

	public function processRequest() {
		switch( $this->_action ) {
			case "category":

				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/_head.tpl.phtml" );

				# show template for given category
				$category = new CategoryInfo( $this->_categoryNameToId( $this->_subject ) );

				new SetInfo( $category->getCatTitle(), "setList" );
				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/_foot.tpl.phtml" );
				break;
			case "dataset":
				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/_head.tpl.phtml" );
				# show template for given data set
				$setInfo = new SetInfo( $this->_subject, "singleSet" );
				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/_foot.tpl.phtml" );
				break;
			case "download":
				# download file to connected usb device
				$response = array();
				if( Util::copyToDevice( $this->_subject, $this->_dev ) ) {
					$response["status"] = "success";
					$response["message"] = "Betankung erfolgreich";
				} else {
					$response["status"] = "failed";
					$response["message"] = "Betankung fehlgeschlagen";
				}
				echo json_encode( $response );

				break;
			case "search":
				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/_head.tpl.phtml" );
				# show template for search results
				$setInfo = new SetInfo( $this->_subject, "searchResult" );
				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/_foot.tpl.phtml" );
				break;
			case "check":
				# check file system for connected usb storage devices
				# should only be called asynchronously
				echo json_encode( Util::checkForDevices() );
				break;
			case "unmount":
				# safely unmount connected device to prevent data loss
				# should only be called asynchronously
				echo json_encode( Util::unmountDevice( $this->_subject ) );
				break;
			case "info":
				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/_head.tpl.phtml" );
				# retrieve content from mediawiki and display it
				new InfoPage( $this->_subject );
				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/_foot.tpl.phtml" );
				break;
			default:
				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/_head.tpl.phtml" );
				# show template of main entry point
				$mainCat = new CategoryInfo( $this->_categoryNameToId( TOP_CATEGORY ), false );
				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/start.tpl.phtml" );
				include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/_foot.tpl.phtml" );
				break;
		}
	}

	private function _categoryNameToId( $name ) {
		return $name . '/' . $this->_language->languageToken();
	}

	private function _parseQueryString() {
		if ( !isset( $_GET["action"] ) ) {
			$this->_action = "";
		} else {
			$this->_action = filter_input( INPUT_GET, "action", FILTER_SANITIZE_SPECIAL_CHARS );
		}
		
		if ( !isset( $_GET["subject"] ) ) {
			$this->_subject = false;
		} else {
			$this->_subject = filter_input( INPUT_GET, "subject", FILTER_SANITIZE_SPECIAL_CHARS );
		}
		
		if ( !isset( $_GET["dev"] ) ) {
			$this->_dev = false;
		} else {
			$this->_dev = filter_input( INPUT_GET, "dev", FILTER_SANITIZE_SPECIAL_CHARS );
		}

		if( isset( $_GET["skin"] ) && file_exists( "templates/" . $_GET["skin"] ) ) {
			$_SESSION["skin"] = $_GET["skin"];
		} else {
			$_SESSION["skin"] = "simple";
		}

		if ( isset( $_GET['lang'] ) ) {
			$this->_language->changeTo( $_GET['lang'] );
		}
	}
}
