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

require_once( "ApiRequest.php" );

class CategoryInfo extends ApiRequest {

	private $_catTitle;
	private $_catInfo;

	private $_dataFields = array(
		"ParentCategory",
		"ShortDescription",
		"LongDescription",
		"Icon",
		"Id"
	);

	public function CategoryInfo( $title, $fetchSubCats = true ) {
		$id = $title . '/' . Datentankstelle::getInstance()->getLanguage()->languageToken();
		$this->populateItemInfo( $id );
		if ( $fetchSubCats ) {
			$this->_subCats = $this->getSubcategories( $title );

			if ( empty( $this->_catInfo["ParentCategory"] ) ) {
				include( "templates/" . $_SESSION["skin"] . "/category.tpl.phtml" );
			} else {
				include( "templates/" . $_SESSION["skin"] . "/subcategory.tpl.phtml" );
				include( "templates/" . $_SESSION["skin"] . "/cat-list.tpl.phtml" );
			}
		}
	}

	public function populateItemInfo( $id ) {
		$params = array(
			"action" => "askargs",
			"format" => "php",
			"conditions" => array(
				'Id::' . $id,
			),
			"printouts" => $this->_dataFields
		);
		$response = $this->sendRequest( $params );
		if ( isset( $response['query']['results'] ) ) {
			// The key of this array returned by the API is the title of the category.
			$this->_catTitle = key($response['query']['results']);
		}

		if ( array_key_exists( "query", $response ) && count( $response["query"]["results"] ) > 0 ) {
			foreach( $response["query"]["results"][$this->_catTitle]["printouts"] as $key => $value ) {
				$this->_catInfo[$key] = $this->_filterResults( $value );
			}
		}
	}
	
	public function getCatInfo() {
		return $this->_catInfo;
	}
	
	public function getCatTitle() {
		return $this->_catTitle;
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

			// retrieve currently displayed category's subcategories
			//$tree[$this->_catTitle]["SubCats"] = $this->getSubcategories( $this->_catTitle );
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
				echo '<a href="?action=category&subject=' . $title .  '" style="padding-left: ' . ( 15 + $indentation * 32 ) . 'px;" class="list-group-item">';
				if ( !empty( $info["Icon"] ) ) {
					echo '<img src="' . $this->getIconFileUrl( $info["Icon"], 32 ) . '" style="padding-right: 10px;" />';
				}
				if ( $title === $this->_catTitle ) {
					echo "<strong>" . $title . "</strong>";
				} else {
					echo $title;
				}
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
