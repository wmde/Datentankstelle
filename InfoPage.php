<?php
require_once( "ApiRequest.php" );

class InfoPage extends ApiRequest {

	protected $_template = "templates/info.tpl.phtml";

	private $_pageTitle;
	private $_pageContent;

	public function InfoPage( $pageTitle ) {
		$this->_pageTitle = $pageTitle;
		$this->_pageContent = $this->getPageContent( $this->_pageTitle );

		include( "templates/info.tpl.phtml" );
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
