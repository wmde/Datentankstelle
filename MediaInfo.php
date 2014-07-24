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

require_once('lib/getid3/getid3.php');

class MediaInfo {
	
	public function MediaInfo() {
		
	}

	public function getID3Info( $filename ) {
		$getID3 = new getID3();
		$fileInfo = $getID3->analyze( $filename );

		# put media info of different ID3 versions into one place
		getid3_lib::CopyTagsToComments($fileInfo);

		if ( isset( $fileInfo['comments']['copyright_message'] ) ) {
			$artist = preg_replace(
				'/^[0-9T:\-+]*\s/',
				'',
				 $this->_selectEntry( $fileInfo['comments']['copyright_message'] )
			);
		}
		if ( isset( $fileInfo['comments']['title'] ) ) {
			$title = $this->_selectEntry( $fileInfo["comments"]["title"] );
		}
		
		return array(
			'artist' =>  isset( $artist ) ? $artist : _( 'unknown' ),
			'title' => isset( $title ) ? $title : _( 'unknown' ),
		);
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