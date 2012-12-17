<?php
App::uses('Nodes\Geo', 'Common.Nodes');

/**
 * GeoTest
 *
 */
class GeoTest extends CakeTestCase {

/**
 * testGetBoxThrowsWithMissingArguments
 *
 * @expectedException InvalidArgumentException
 */
	public function testGetBoxThrowsWithMissingArguments() {
		\Nodes\Geo::getBox(null, null);
	}

/**
 * testGetBoxThrowsWithMalformedTopLeft
 *
 * @expectedException InvalidArgumentException
 */
	public function testGetBoxThrowsWithMalformedTopLeft() {
		\Nodes\Geo::getBox('5610', '55,11');
	}

/**
 * testGetBoxThrowsWithMalformedBottomRight
 *
 * @expectedException InvalidArgumentException
 */
	public function testGetBoxThrowsWithMalformedBottomRight() {
		\Nodes\Geo::getBox('56,10', '5511');
	}

/**
 * testGetBoxThrowsWithMalformedBottomRight
 *
 * @expectedException InvalidArgumentException
 */
	public function testGetBoxThrowsWithNoBox() {
		\Nodes\Geo::getBox('56,11', '55,10');
	}

/**
 * testGetBoxSimple
 *
 */
	public function testGetBoxSimple() {
		$expected = array(
			'lat1' => 56,
			'lat2' => 55,
			'lng1' => 10,
			'lng2' => 11
		);
		$return = \Nodes\Geo::getBox('56,10', '55,11');
		$this->assertSame($expected, $return);
	}

/**
 * testGetBoxFloat
 *
 */
	public function testGetBoxFloat() {
		$expected = array(
			'lat1' => 56.123,
			'lat2' => 55.123,
			'lng1' => 10.456,
			'lng2' => 11.456
		);
		$return = \Nodes\Geo::getBox('56.123,10.456', '55.123,11.456');
		$this->assertSame($expected, $return);
	}
}
