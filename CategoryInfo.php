<?php
require_once( "ApiRequest.php" );

class CategoryInfo extends ApiRequest {

	private $_catTitle;
	private $_catInfo;
	private $_breadCrumbs = array();

	private $_dataFields = array(
		"ParentCategory",
		"ShortDescription",
		"LongDescription",
		"Icon"
	);
	

	public function CategoryInfo( $title, $fetchSubCats = true ) {
		$this->_catTitle = $title;
		$this->populateItemInfo( $title );
		if ( $fetchSubCats ) {
			$this->_subCats = $this->getSubcategories( $title );
			$this->_breadCrumbs = $this->getBreadcrumbs( array( $this->_catTitle ) );

			include( "templates/category.tpl.phtml" );
		}
	}

	public function populateItemInfo() {
		$params = array(
			"action" => "askargs",
			"format" => "php",
			"conditions" => $this->_catTitle,
			"printouts" => $this->_dataFields
		);
		$response = $this->sendRequest( $params );

		if ( array_key_exists( "query", $response ) && count( $response["query"]["results"] ) > 0 ) {
			foreach( $response["query"]["results"][$this->_catTitle]["printouts"] as $key => $value ) {
				$this->_catInfo[$key] = $this->_filterResults( $value );
			}
		}
	}
	
	public function getBreadcrumbs( $breadCrumbs ) {
		if ( !empty( $this->_catInfo["ParentCategory"] ) ) {
			$parentCat = new CategoryInfo( $this->_catInfo["ParentCategory"], false );
			$breadCrumbs[] = $parentCat->_catTitle;
			$breadCrumbs = $parentCat->getBreadCrumbs( $breadCrumbs );
		}

		return $breadCrumbs;
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
