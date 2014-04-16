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

			if ( empty( $this->_catInfo["ParentCategory"] ) ) {
				include( "templates/category.tpl.phtml" );
			} else {
				include( "templates/subcategory.tpl.phtml" );
			}
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

	public function getCategoryTree( $tree = array() ) {
		if( count( $tree ) === 0 ) {
			$tree[$this->_catTitle] = $this->_catInfo;
			$tree[$this->_catTitle]["SubCats"] = $this->getSubcategories( $this->_catTitle );
		}

		$siblings = $this->getSubcategories( $this->_catInfo["ParentCategory"] );
		if ( count( $siblings ) > 0 ) {
			$parentName = $this->_catInfo["ParentCategory"];

			$siblings[$this->_catTitle] = $tree[$this->_catTitle];
			$tree[$parentName] = $siblings;
			unset( $tree[$this->_catTitle] );

			$parent = new CategoryInfo( $this->_catInfo["ParentCategory"], false );
			$tree[$parentName] = $parent->_catInfo;
			$tree[$parentName]["SubCats"] = $siblings;

			$tree = $parent->getCategoryTree( $tree );
		}

		return $tree;
	}

	public function iterateCategoryTree( $tree, $indentation = 0 ) {
		foreach( $tree as $title => $info ) {
			// TODO: top category may have a different name
			if ( $title !== "Hauptkategorie" ) {
				echo '<a href="?action=category&subject=' . $title .  '" style="padding-left: ' . ( 15 + $indentation * 10 ) . 'px;" class="list-group-item">';
				if ( !empty( $info["Icon"] ) ) {
					echo '<img src="' . $this->getIconFileUrl( $info["Icon"], 32 ) . '" style="padding-right: 10px;" />';
				}
				echo $title;
				echo '</a>';
			}

			if( isset( $info["SubCats"] ) && count( $info["SubCats"] ) > 0 ) {
				// TODO: top category may have a different name
				if ( $title !== "Hauptkategorie" ) {
					$indentation ++;
				}
				$this->iterateCategoryTree( $info["SubCats"], $indentation );
				$indentation --;
			}
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

		if ( isset( $response["query"] ) ) {
			foreach( $response["query"]["results"] as $name => $info ) {
				foreach( $info["printouts"] as $key => $value ) {
					$subCats[$name][$key] = $this->_filterResults( $value );
				}
			}
		}

		return $subCats;
	}

	public function getIconFileUrl( $icon, $width ) {
		$iconUrl = "";
		if ( !empty( $icon ) && intval( $width > 0 ) ) {
			$params = array(
				"action" => "query",
				"format" => "php",
				"titles" => $icon,
				"prop" => "imageinfo",
				"iiprop" => "url",
				"iiurlwidth" => $width
			);
			$imgInfo = $this->sendRequest( $params );

			foreach( $imgInfo["query"]["pages"] as $id => $values ) {
				if ( intval( $id ) === -1 ) {
					return false;
				}

				$iconUrl = $values["imageinfo"][0]["thumburl"];
			}
		}

		return $iconUrl;
	}
}
