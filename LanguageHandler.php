<?php
class LanguageHandler {
	const defaultLanguage = 'en_US.UTF-8';

	function __construct() {
		if ( isset( $_SESSION['language'] ) ) {
			$this->changeTo( $_SESSION['language'] );
		} else { // TODO: Try getting the language from the user's language settings
			$this->changeTo( self::defaultLanguage );
		}
	}

	public function changeTo( $language ) {
		// FIXME: Check if $language is one of our supported languages

		$this->_setLanguage( $language );
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
