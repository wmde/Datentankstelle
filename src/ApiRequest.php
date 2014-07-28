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

class ApiRequest {

	/**
	 * Sends a request to the MediaWiki-API and return its response
	 * @param mixed $params array of parameters to specify API request
	 * @return boolean|mixed response object or false if the request failed
	 */
	protected function sendRequest( $params, $json = false ) {
		$params = $this->_preprocessParams( $params );

		$url = WIKI_API_URL . "?" . http_build_query( $params );
		$response = file_get_contents( $url );

		if ( $json ) {
			return ( $response ? json_decode( $response ) : false );
		}
		return ( $response ? unserialize( $response ) : false );
	}

	/**
	 * Concatenates multi-value parameters using pipe character
	 * @param mixed $param array of values
	 * @return string concatenated parameter values
	 */
	private function _concatParams( $param, $glue ) {
		if ( is_array ( $param ) ) {
			return implode( $glue, $param );
		}

		return $param;
	}

	/**
	 * Preprocesses parameters by concatenating multi-value
	 * @param mixed $params associative array of parameters
	 * @return mixed preprocessed parameters
	 */
	private function _preprocessParams( $params ) {
		$paramConcat = array(
			"printouts" => "|",
			"conditions" => "|"
		);

		if ( is_array( $params ) ) {
			foreach( $paramConcat as $name => $glue ) {
				if ( array_key_exists( $name, $params ) ) {
					$params[$name] = $this->_concatParams( $params[$name], $glue );
				}
			}
		}

		return $params;
	}

	/**
	 * 
	 * @param type $value
	 * @return string
	 */
	protected function _filterResults( $value ) {
		if ( count( $value ) > 0 && is_array( $value[0] ) ) {
			return $value[0]["fulltext"];
		} elseif ( !empty( $value ) && isset( $value[0] ) ) {
			return $value[0];
		} else {
			return "";
		}
	}
}
