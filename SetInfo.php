<?php
require_once( "ApiRequest.php" );
require_once( "MediaInfo.php" );

class SetInfo extends ApiRequest {

	private $_setTitle;
	private $_setImage;
	private $_dataSet;
	private $_dataSets;
	private $_fileList;

	private $_dataFields = array(
		"ParentCategory",
		"ShortDescription",
		"LongDescription",
		"Image",
		"Author",
		"Supplier",
		"Licence",
		"FileName",
		"MediaType"
	);

	public function SetInfo( $setTitle, $displayType ) {
		$this->_setTitle = $setTitle;

		if ( $displayType === "singleSet" ) {
			$this->_dataSet = $this->getDataSet( $setTitle );
			switch( $this->_dataSet["MediaType"][0] ) {
				case "images":
					$this->_fileList = $this->getFileListByTitle( $setTitle );
					break;
				case "audio":
					$this->_fileList = $this->getAudioFileInfo( $this->getFileListByTitle( $setTitle ) );
					break;
			}

			include( "templates/dataset.tpl.phtml" );
		} elseif( $displayType === "searchResult" ) {
			$this->_dataSets = $this->searchDataSets( $setTitle );
			include( "templates/search-result.tpl.phtml" );
		} elseif( $displayType === "setList" ) {
			$this->_dataSets = $this->getDataSets( $setTitle );
			include( "templates/set-list.tpl.phtml" );
		}
	}

	public function getDataSets( $catName ) {
		$params = array(
			"action" => "askargs",
			"format" => "php",
			"conditions" => array(
				"ParentCategory::" . $catName,
				"Category:" . TITLE_SETS
			),
			"printouts" => $this->_dataFields
		);
		$response = $this->sendRequest( $params );

		$dataSets = array();

		$index = 0;
		foreach( $response["query"]["results"] as $name => $info ) {
			$dataSets[$index] = array( "Title" => $name );
			foreach( $info["printouts"] as $key => $value ) {
				$dataSets[$index][$key] = $this->_filterResults( $value );
			}
			$index ++;
		}

		return $dataSets;
	}

	public function getDataSet( $title ) {
		$params = array(
			"action" => "askargs",
			"format" => "php",
			"conditions" => $title,
			"printouts" => $this->_dataFields
		);
		$response = $this->sendRequest( $params );
		return $response["query"]["results"][html_entity_decode( $title, ENT_QUOTES )]["printouts"];
	}

	public function createImageThumb() {
		if ( is_array( $this->_dataSet["Image"] ) && count( $this->_dataSet["Image"] ) > 0 ) {
			$params = array(
				"action" => "query",
				"format" => "php",
				"titles" => $this->_dataSet["Image"][0]["fulltext"],
				"prop" => "imageinfo",
				"iiprop" => "url",
				"iiurlwidth" => 240
			);
			$imgInfo = $this->sendRequest( $params );

			foreach( $imgInfo["query"]["pages"] as $id => $values ) {
				if ( intval( $id ) === -1 ) {
					return false;
				}

				$info = $values["imageinfo"][0]["thumburl"];
			}
			$this->_setImage = $info;
			return true;
		}

		return false;
	}
	
	public function searchDataSets( $subject ) {
		$retVal = array();
		$titles = array();

		if ( !empty( $subject ) ) {
			# case-insensitive full text search to get matching page titles
			$params = array(
				"action" => "query",
				"format" => "php",
				"list" => "search",
				"srsearch" => $subject,
				"srwhat" => "text"
			);

			$pages = $this->sendRequest( $params );
			foreach( $pages["query"]["search"] as $page ) {
				$titles[] = $page["title"];
			}

			# get data sets matching any of the given titles (also filters non-dataset pages)
			$params = array(
				"action" => "ask",
				"format" => "php",
				"query" => "[[" . implode( "||", $titles ) . "]]|?ShortDescription"
			);

			$dataSets = $this->sendRequest( $params );
			foreach( $dataSets["query"]["results"] as $title => $info ) {
				if( is_array( $info["printouts"]["ShortDescription"] ) && 
						!empty( $info["printouts"]["ShortDescription"][0] ) ) {

					$retVal[] = array(
						"Title" => $title,
						"ShortDescription" => $info["printouts"]["ShortDescription"][0]
					);
				}
			}
		}

		return $retVal;
	}
	
	public function getFileListByTitle( $title ) {
		$params = array(
			"action" => "query",
			"format" => "php",
			"titles" => $title
		);

		$response = $this->sendRequest( $params );
		foreach( $response["query"]["pages"] as $id => $info ) {
			$pageId = $id;
		}

		$dir = "downloads/" . $pageId . "/";

		if ( file_exists( $dir ) ) {
			$fileList = array_values( array_diff( scandir( $dir ), array( '..', '.' ) ) );
			foreach( $fileList as $index => $item ) {
				$fileList[$index] = $dir . $item;
			}
			return $fileList;
		}

		return false;
	}
	
	public function getAudioFileInfo( $filelist ) {
		$mediaInfo = new MediaInfo();
		$info = $mediaInfo->getID3InfoByFilelist( $filelist );
		return $info;
	}

	public function checkForDevices() {
		if ( file_exists( USB_MOUNT_DIR ) ) {
			$list = array_diff( scandir( USB_MOUNT_DIR ), array( ".", ".." ) );
			return $list;
		}

		return false;
	}

	public function copyToDevice( $fileName, $deviceName ) {
		if ( copy( "downloads/" . $fileName, USB_MOUNT_DIR . $deviceName . "/" . $fileName ) ) {
			return true;
		}

		return false;
	}
}
