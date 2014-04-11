<?php
require_once( "ApiRequest.php" );
require_once( "MediaInfo.php" );
require_once( "CategoryInfo.php" );

class SetInfo extends ApiRequest {

	private $_setTitle;
	private $_setImage;
	private $_dataSet;
	private $_dataSets;
	private $_breadCrumbs = array();
	private $_fileList = array();

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
	
	private $_licences = array(
		"CC-0"						=> "https://creativecommons.org/publicdomain/zero/1.0/deed.de",
		"CC-BY 3.0"					=> "https://creativecommons.org/licenses/by/3.0/deed.de",
		"CC-BY 3.0 Deutschland"		=> "https://creativecommons.org/licenses/by/3.0/deed.de",
		"CC-BY 4.0"					=> "https://creativecommons.org/licenses/by/4.0/deed.de",
		"CC-BY-SA 2.0"				=> "https://creativecommons.org/licenses/by-sa/2.0/deed.de",
		"CC-BY-SA 2.0 Österreich"	=> "https://creativecommons.org/licenses/by-sa/2.0/at/",
		"CC-BY-SA 2.5"				=> "https://creativecommons.org/licenses/by-sa/2.5/deed.de",
		"CC-BY-SA 3.0"				=> "https://creativecommons.org/licenses/by-sa/3.0/deed.de",
		"CC-BY-SA 3.0 Deutschland"	=> "https://creativecommons.org/licenses/by-sa/3.0/de/",
		"GeoNutzV"					=> "http://urheberrecht.wikimedia.de/2013/04/geodaten-geonutzv/",
		"ODC-BY"					=> "http://opendatacommons.org/licenses/by/summary/",
		"PD"						=> "https://creativecommons.org/publicdomain/zero/1.0/deed.de"
	);

	public function SetInfo( $setTitle, $displayType ) {
		$this->_setTitle = $setTitle;

		if ( $displayType === "singleSet" ) {
			$this->_dataSet = $this->populateSetInfo( $setTitle );
			$parentCat = new CategoryInfo( $this->_dataSet["ParentCategory"], false );
			$this->_breadCrumbs = $parentCat->getBreadcrumbs(
					array(
						$this->_setTitle,
						$this->_dataSet["ParentCategory"]
					)
				);

			switch( $this->_dataSet["MediaType"] ) {
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

	public function populateSetInfo( $title ) {
		$params = array(
			"action" => "askargs",
			"format" => "php",
			"conditions" => $title,
			"printouts" => $this->_dataFields
		);
		$response = $this->sendRequest( $params );
		foreach( $response["query"]["results"][html_entity_decode( $this->_setTitle, ENT_QUOTES )]["printouts"] as $key => $value ) {
			$this->_dataSet[$key] = $this->_filterResults( $value );
		}
		return $this->_dataSet;
	}

	public function createImageThumb() {
		if ( !empty( $this->_dataSet["Image"] ) ) {
			$params = array(
				"action" => "query",
				"format" => "php",
				"titles" => $this->_dataSet["Image"],
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
			if( !array_key_exists( "error", $dataSets ) ) {
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
	
	public function includeMediaPreview() {
		if ( is_array( $this->_fileList ) && count( $this->_fileList ) > 0 ) {
			switch( $this->_dataSet["MediaType"][0] ) {
				case "images":
					include( "templates/gallery.tpl.phtml" );
					break;
				case "audio":
					include( "templates/audio-player.tpl.phtml" );
					break;
				default:
					break;
			}
		} else if ( $this->createImageThumb() ) {
			echo '<img src="' . $this->_setImage . '" class="img-rounded" style="float: left; margin-right: 10px;" />';
		}
	}

	public function calcFileSize() {
		$unitIndex = 0;
		$size = 0;
		$units = array( "B", "kB", "MB", "GB" );

		if ( !empty( $this->_dataSet["FileName"] ) && file_exists( "downloads/" . $this->_dataSet["FileName"] ) ) {
			$size = filesize( "downloads/" . $this->_dataSet["FileName"] );

			while ( $size > 1024 ) {
				$unitIndex ++;
				$size /= 1024;
			}
		}

		return number_format( $size, 2, ',', "" ) . " " . $units[$unitIndex];
	}
	
	public function getFileType() {
		if ( !empty( $this->_dataSet["FileName"] ) && file_exists( "downloads/" . $this->_dataSet["FileName"] ) ) {
			return pathinfo( "downloads/" . $this->_dataSet["FileName"], PATHINFO_EXTENSION );
		}

		return "&nbsp;";
	}

	public function getLicenceLink( $lName ) {
		if ( array_key_exists( $lName, $this->_licences ) ) {
			return '<a href="' . $this->_licences[$lName] . '">' . $lName . '</a>';
		}

		return $lName;
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
	
	public function isLocalSystem() {
		return ( $_SERVER["REMOTE_ADDR"] === "127.0.0.1" ? true : false );
	}
}
