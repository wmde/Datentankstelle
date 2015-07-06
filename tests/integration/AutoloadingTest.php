<?php

namespace Tests\Datentankstelle;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AutoloadingTest extends \PHPUnit_Framework_TestCase {

	public function testCanLoadOwnClasses() {
		$this->assertTrue( class_exists( 'Datentankstelle\Datentankstelle' ) );
	}

	public function testCanLoadClassesOfDependencies() {
		$this->assertTrue( class_exists( 'getID3' ) );
	}

}
