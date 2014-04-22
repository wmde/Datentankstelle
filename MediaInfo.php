<?php
require_once('lib/getid3/getid3.php');

class MediaInfo {
	
	public function MediaInfo() {
		
	}

	public function getID3Info( $filename ) {
		$getID3 = new getID3();
		$fileInfo = $getID3->analyze( $filename );

		# put media info of different ID3 versions into one place
		getid3_lib::CopyTagsToComments($fileInfo);

		$title = $this->_selectEntry( $fileInfo["comments"]["title"] );
		$copyrightMessage = preg_replace(
				"/^[0-9T:\-+]*\s/",
				"", 
				$this->_selectEntry( $fileInfo["comments"]["copyright_message"] )
		);
		
		return array( "artist" => $copyrightMessage, "title" => $title );
	}

	private function _selectEntry( $element ) {
		$selectedEntry = null;

		if ( is_array( $element ) ) {
			foreach( $element as $entry ) {
			if ( $selectedEntry === null || strlen( $entry ) > $element[$selectedEntry] ) {
					$selectedEntry = key( $element );
				}
			}
		}

		return $element;
	}

	public function getID3InfoByFilelist( $filelist ) {
		$arrFiles = array();

		foreach( $filelist as $file ) {
			$obj = new stdClass();
			$obj->mp3 = $file;

			$info = $this->getID3Info( $file );
			$obj->title = $info["title"];
			$obj->artist = $info["artist"];

			$arrFiles[] = $obj;
		}

		return $arrFiles;
	}
}