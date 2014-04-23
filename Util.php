<?php
class Util {

	public static function checkForDevices() {
		if ( file_exists( USB_MOUNT_DIR ) ) {
			$list = array_diff( scandir( USB_MOUNT_DIR ), array( ".", ".." ) );
			return $list;
		}

		return false;
	}

	public static function copyToDevice( $fileName, $deviceName ) {
		if ( @copy( DOWNLOAD_FOLDER . $fileName, USB_MOUNT_DIR . $deviceName . "/" . $fileName ) ) {
			return true;
		}

		return false;
	}

	public static function isLocalSystem() {
		return ( $_SERVER["REMOTE_ADDR"] === "127.0.0.1" ? true : false );
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
}