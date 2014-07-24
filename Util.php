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

class Util {

	public static function checkForDevices() {
		if ( file_exists( USB_MOUNT_DIR ) ) {
			$list = array_diff( scandir( USB_MOUNT_DIR ), array( ".", ".." ) );
			foreach( $list as $key => $device ) {
				// HACK: remove mounted internal hard drive from list of connected devices
				if ( $device === "Data" && ( disk_total_space( USB_MOUNT_DIR . $device ) / pow( 1024, 4 ) ) > 3 ) {
					unset( $list[$key] );
				}
			}
			return $list;
		}

		return false;
	}

	public static function unmountDevice( $deviceLabel ) {
		$output = shell_exec( "pumount " . USB_MOUNT_DIR . $deviceLabel );
		if ( $output === null ) {
			return array( "status" => "success", "message" => "Das Gerät wurde getrennt." );
		} else {
			return array( "status" => "failed", "message" => "Das Gerät konnte nicht getrennt werden." );
		}
	}

	public static function copyToDevice( $fileName, $deviceName ) {
		Util::writeLog( "copying " . $fileName . " to flash drive\n" );
		if ( @copy( DOWNLOAD_FOLDER . $fileName, USB_MOUNT_DIR . $deviceName . "/" . $fileName ) ) {
			return true;
		}

		return false;
	}
	
	public static function writeLog( $msg ) {
		file_put_contents( "dts.log", date('Y-m-d H:i:s') . ": " . $msg, FILE_APPEND );
	}

	public static function isLocalSystem() {
		return ( $_SERVER["REMOTE_ADDR"] === "127.0.0.1" ? true : false );
	}

	public static function idToDirectoryHash( $id ) {
		return md5( substr( $id, 0, -3 ) );
	}

	// TODO: This is a bit hacky. Maybe come up with a better solution at a later point.
	public static function filePathToWebPath( $file ) {
		return BASE_DIR . 'downloads' . substr( $file, strlen( DOWNLOAD_FOLDER ) - 1 );
	}

	public static function calcFileSize( $file ) {
		$unitIndex = 0;
		$size = 0;
		$units = array( "B", "kB", "MB", "GB" );

		if ( !empty( $file ) && file_exists( DOWNLOAD_FOLDER . $file ) ) {
			$size = filesize( DOWNLOAD_FOLDER . $file );

			while ( $size > 1024 ) {
				$unitIndex ++;
				$size /= 1024;
			}
		}

		return number_format( $size, 2, ',', "" ) . " " . $units[$unitIndex];
	}

	public static function getFileType( $file ) {
		if ( !empty( $file ) && file_exists( DOWNLOAD_FOLDER . $file ) ) {
			return pathinfo( DOWNLOAD_FOLDER . $file, PATHINFO_EXTENSION );
		}

		return "&nbsp;";
	}
	
	public static function getImageDescription( $fileName ) {
		$descFile = preg_replace( "/.jpg$/", ".txt", $fileName );
		if ( !empty( $descFile ) && file_exists( $descFile ) ) {
			$description = file_get_contents( $descFile );
			return str_replace( "\n", "", $description );
		}

		return false;
	}
}