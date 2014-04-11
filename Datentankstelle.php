<?php
require_once( "CategoryInfo.php" );
require_once( "SetInfo.php" );
require_once( "InfoPage.php" );

class Datentankstelle {

	private $_action;
	private $_subject;
	
	private $_catItems = array();
	private $_dataSet = array();
	private $_fileList = array();
	
	public function Datentankstelle() {
		$this->_parseQueryString();
	}

	public function processRequest() {
		include( "templates/_head.tpl.phtml" );
		switch( $this->_action ) {
			case "category":
				# show template for given category
				new CategoryInfo( $this->_subject );
				new SetInfo( $this->_subject, "setList" );
				break;
			case "dataset":
				# show template for given data set
				$setInfo = new SetInfo( $this->_subject, "singleSet" );
				break;
			case "download":
				# show template for given data set
				$setInfo = new SetInfo();
				$this->_dataSet = $setInfo->populateSetInfo( $this->_subject );
				$this->_fileList = $setInfo->getFileListByTitle( $this->_subject );
				include( "templates/dataset.tpl.phtml" );

				# download file to connected usb device
				if( $setInfo->copyToDevice( $this->_dataSet["FileName"], $this->_dev ) ) echo "success";
				else echo "failed";

				break;
			case "search":
				# show template for search results
				$setInfo = new SetInfo( $this->_subject, "searchResult" );
				break;
			case "check":
				# check file system for connected usb storage devices
				# should only be called asynchronously
				$setInfo = new SetInfo();
				echo json_encode( $setInfo->checkForDevices() );
				break;
			case "info":
				# retrieve content from mediawiki and display it
				new InfoPage( $this->_subject );
				break;
			default:
				# show template of main entry point
				include( "templates/start.tpl.phtml" );
				break;
		}
		include( "templates/_foot.tpl.phtml" );
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
	}
}
