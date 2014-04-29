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

require_once( "ApiRequest.php" );
require_once( "MediaInfo.php" );
require_once( "CategoryInfo.php" );

class SetInfo extends ApiRequest {

	private $_setTitle;
	private $_dataSet;
	private $_dataSets;
	private $_breadCrumbs = array();
	private $_fileList = array();

	private $_dataFields = array(
		"ParentCategory",
		"ShortDescription",
		"LongDescription",
		"Image",
		"Icon",
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

			include( "templates/" . $_SESSION["skin"] . "/dataset.tpl.phtml" );
		} elseif( $displayType === "searchResult" ) {
			$this->_dataSets = $this->searchDataSets( $setTitle );
			include( "templates/" . $_SESSION["skin"] . "/search-result.tpl.phtml" );
		} elseif( $displayType === "setList" ) {
			$this->_dataSets = $this->getDataSets( $setTitle );
			include( "templates/" . $_SESSION["skin"] . "/set-list.tpl.phtml" );
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

	public function createImageThumb( $fileName, $width = 240 ) {
		$url = "";

		$params = array(
			"action" => "query",
			"format" => "php",
			"titles" => $fileName,
			"prop" => "imageinfo",
			"iiprop" => "url",
			"iiurlwidth" => $width
		);
		$imgInfo = $this->sendRequest( $params );

		foreach( $imgInfo["query"]["pages"] as $id => $values ) {
			if ( intval( $id ) === -1 ) {
				return false;
			}

			$url = $values["imageinfo"][0]["thumburl"];
		}
		return $url;
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
				"query" => "[[" . implode( "||", $titles ) . "]]|?" . implode( "|?", $this->_dataFields )
			);

			$dataSets = $this->sendRequest( $params );
			if( !array_key_exists( "error", $dataSets ) ) {
				$index = 0;
				foreach( $dataSets["query"]["results"] as $title => $info ) {
					$retVal[$index] = array( "Title" => $title );
					foreach( $info["printouts"] as $key => $value ) {
						$retVal[$index][$key] = $this->_filterResults( $value );
					}
					$index ++;
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

		$dir = DOWNLOAD_FOLDER . $pageId . "/";

		if ( file_exists( $dir ) ) {
			$fileList = array_values( array_diff( scandir( $dir ), array( '..', '.' ) ) );
			foreach( $fileList as $index => $item ) {
				if ( preg_match( "/\.txt$/", $item ) !== 0 ) {
					unset( $fileList[$index] );
				} else {
					$fileList[$index]  = $dir . $item;
				}
			}
			return $fileList;
		}

		return false;
	}
	
	public function includeMediaPreview( $count, $type, $fileList = array() ) {
		if ( is_array( $fileList ) && count( $fileList ) > 0 ) {
			switch( $type ) {
				case "images":
					include( "templates/" . $_SESSION["skin"] . "/gallery.tpl.phtml" );
					break;
				case "audio":
					include( "templates/" . $_SESSION["skin"] . "/audio-player.tpl.phtml" );
					break;
				default:
					break;
			}
		}
	}

	public function getAudioFileInfo( $filelist ) {
		$mediaInfo = new MediaInfo();
		$info = $mediaInfo->getID3InfoByFilelist( $filelist );
		return $info;
	}

	public function getLicenceLink( $lName ) {
		if ( array_key_exists( $lName, $this->_licences ) ) {
			return '<a href="' . $this->_licences[$lName] . '">' . $lName . '</a>';
		}

		return $lName;
	}
}
