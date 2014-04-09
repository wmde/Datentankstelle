<?php
require_once( "ApiRequest.php" );

class CategoryInfo extends ApiRequest {

	private $_catTitle;
	private $_catInfo;

	private $_dataFields = array(
		"ParentCategory",
		"ShortDescription",
		"LongDescription",
		"Icon"
	);
	

	public function CategoryInfo( $title ) {
		$this->_catTitle = $title;
		$this->populateItemInfo( $title );
		$this->_subCats = $this->getSubcategories( $title );
		include( "templates/category.tpl.phtml" );
	}

	public function populateItemInfo() {
		# TODO: consider pipe sign?
		$params = array(
			"action" => "askargs",
			"format" => "php",
			"conditions" => $this->_catTitle,
			"printouts" => $this->_dataFields
		);
		$response = $this->sendRequest( $params );

		foreach( $response["query"]["results"][$this->_catTitle]["printouts"] as $key => $value ) {
			$this->_catInfo[$key] = $this->_filterResults( $value );
		}
	}
	
	public function getSubcategories( $catName ) {
		$params = array(
			"action" => "askargs",
			"format" => "php",
			"conditions" => array(
				"ParentCategory::" . $catName,
				"Category:" . TITLE_CATEGORIES
			),
			"printouts" => $this->_dataFields
		);
		$response = $this->sendRequest( $params );

		$subCats = array();

		$index = 0;
		foreach( $response["query"]["results"] as $name => $info ) {
			$subCats[$index] = array( "Title" => $name );
			foreach( $info["printouts"] as $key => $value ) {
				$subCats[$index][$key] = $this->_filterResults( $value );
			}
			$index ++;
		}

		return $subCats;
	}
}
