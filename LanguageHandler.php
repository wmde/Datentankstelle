<?php
class LanguageHandler {
	const defaultLanguage = 'en';

	private $_supportedLanguages = [
		'en' => 'en_US.UTF-8',
		'de' => 'de_DE.UTF-8',
	];

	function __construct() {
		if ( isset( $_SESSION['language'] ) ) {
			$this->_setLanguage( $_SESSION['language'] );
		} else {
			$this->changeTo( substr( $_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2 ) );
		}
	}

	private function _supported( $language ) {
		return isset( $language, $this->_supportedLanguages );
	}

	public function changeTo( $language ) {
		if ( $this->_supported( $language ) ) {
			$this->_setLanguage( $this->_supportedLanguages[$language] );
		} else {
			$this->_setLanguage( $this->_supportedLanguages[self::defaultLanguage] );
		}
	}

	private function _setLanguage( $language ) {
		$_SESSION['language'] = $language;

		putenv( "LANG=" . $language );
		setlocale( LC_ALL, $language ); // TODO: Figure out whether $language really has to be a language that the operating system knows

		$domain = 'messages';
		bindtextdomain( $domain, 'Locale' );
		bind_textdomain_codeset( $domain, 'UTF-8' );
		textdomain( $domain );
	}
}
