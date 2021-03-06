<?php
namespace Datentankstelle;

class LanguageHandler {
	const defaultLanguage = 'en';

	private $_language;

	// The keys of this array are used to query pages by language. They have to be 2 chars long.
	private static $_supportedLanguages = [
		'en' => 'en_US.UTF-8',
		'de' => 'de_DE.UTF-8',
	];

	function __construct() {
		if ( isset( $_SESSION['language'] ) ) {
			$this->_setLanguage( $_SESSION['language'] );
		} else {
			$this->changeTo( self::defaultLanguage );
		}
	}

	public function languageToken() {
		return array_flip( self::supportedLanguages() )[$this->_language];
	}

	public static function supportedLanguages() {
		return self::$_supportedLanguages;
	}

	private function _supported( $language ) {
		return isset( $language, self::$_supportedLanguages );
	}

	public function changeTo( $language ) {
		if ( $this->_supported( $language ) ) {
			$this->_setLanguage( self::$_supportedLanguages[$language] );
		} else {
			$this->_setLanguage( self::$_supportedLanguages[self::defaultLanguage] );
		}
	}

	private function _setLanguage( $language ) {
		$_SESSION['language'] = $language;
		$this->_language = $language;

		putenv( "LANG=" . $language );
		setlocale( LC_ALL, $language ); // TODO: Figure out whether $language really has to be a language that the operating system knows

		$domain = 'messages';
		bindtextdomain( $domain, 'Locale' );
		bind_textdomain_codeset( $domain, 'UTF-8' );
		textdomain( $domain );
	}
}
