<?php
require_once( "CategoryInfo.php" );
require_once( "SetInfo.php" );
require_once( "InfoPage.php" );
require_once( "Util.php" );

class Datentankstelle {

	private $_action;
	private $_subject;
	
	private $_dataSet = array();
	private $_fileList = array();
	
	public function Datentankstelle() {
		$this->_parseQueryString();
	}

	public function processRequest() {
		switch( $this->_action ) {
			case "category":
				include( "templates/" . $_SESSION["skin"] . "/_head.tpl.phtml" );
				# show template for given category
				new CategoryInfo( $this->_subject );
				new SetInfo( $this->_subject, "setList" );
				include( "templates/" . $_SESSION["skin"] . "/_foot.tpl.phtml" );
				break;
			case "dataset":
				include( "templates/" . $_SESSION["skin"] . "/_head.tpl.phtml" );
				# show template for given data set
				$setInfo = new SetInfo( $this->_subject, "singleSet" );
				include( "templates/" . $_SESSION["skin"] . "/_foot.tpl.phtml" );
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
				include( "templates/" . $_SESSION["skin"] . "/_head.tpl.phtml" );
				# show template for search results
				$setInfo = new SetInfo( $this->_subject, "searchResult" );
				include( "templates/" . $_SESSION["skin"] . "/_foot.tpl.phtml" );
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
				include( "templates/" . $_SESSION["skin"] . "/_head.tpl.phtml" );
				# retrieve content from mediawiki and display it
				new InfoPage( $this->_subject );
				include( "templates/" . $_SESSION["skin"] . "/_foot.tpl.phtml" );
				break;
			default:
				include( "templates/" . $_SESSION["skin"] . "/_head.tpl.phtml" );
				# show template of main entry point
				$mainCat = new CategoryInfo( TOP_CATEGORY, false );
				include( "templates/" . $_SESSION["skin"] . "/start.tpl.phtml" );
				include( "templates/" . $_SESSION["skin"] . "/_foot.tpl.phtml" );
				break;
		}
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
	}
}
