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

class InfoPage extends ApiRequest {

	private $_pageTitle;
	private $_pageContent;

	public function __construct( $pageTitle ) {
		$this->_pageTitle = $pageTitle;
		$this->_pageContent = $this->getPageContent( $this->_pageTitle );

		include( __DIR__ . "/../templates/" . $_SESSION["skin"] . "/info.tpl.phtml" );
	}

	public function getPageContent() {
		# hack: API ignores $wgDefaultUserOptions["editsection"] = false;
		$params = array(
			"action" => "parse",
			"format" => "php",
			"text" => "__NOTOC____NOEDITSECTION__{{:" . $this->_pageTitle . "}}"
		);

		$response = $this->sendRequest( $params );
		if( $response ) {
			return $response["parse"]["text"]["*"];
		}

		return false;
	}
}
