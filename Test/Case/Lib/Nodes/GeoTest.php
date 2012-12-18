<?php
use \Nodes\Geo;

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
		Geo::getBox(null, null);
	}

/**
 * testGetBoxThrowsWithMalformedTopLeft
 *
 * @expectedException InvalidArgumentException
 */
	public function testGetBoxThrowsWithMalformedTopLeft() {
		Geo::getBox('5610', '55,11');
	}

/**
 * testGetBoxThrowsWithMalformedBottomRight
 *
 * @expectedException InvalidArgumentException
 */
	public function testGetBoxThrowsWithMalformedBottomRight() {
		Geo::getBox('56,10', '5511');
	}

/**
 * testGetBoxThrowsWithMalformedBottomRight
 *
 * @expectedException InvalidArgumentException
 */
	public function testGetBoxThrowsWithNoBox() {
		Geo::getBox('56,11', '55,10');
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
		$return = Geo::getBox('56,10', '55,11');
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
		$return = Geo::getBox('56.123,10.456', '55.123,11.456');
		$this->assertSame($expected, $return);
	}

	public function testGetBoundary() {
		$lat = 0;
		$lng = 0;

		$radiusKm = 1;
		$radiusDeg = 360 * $radiusKm / (Geo::EARTH_RADIUS * 2 * M_PI);
		$diameterDegInnerBox = $radiusDeg * 2;

		$return = Geo::getBoundary($diameterDegInnerBox, $lat, $lng);
		foreach ($return as &$val) {
			$val = number_format($val, 6);
		}
		unset ($val);

		$expected = array(
			'lat1' => -0.006382,
			'lat2' => 0.006382,
			'lng1' => -0.006382,
			'lng2' => 0.006382
		);
		foreach ($expected as &$val) {
			$val = number_format($val, 6);
		}
		unset ($val);

		$this->assertSame($expected, $return);

		$distances = array(
			'n' => Geo::getDistance($lat, $lng, $return['lat2'], $lng),
			'ne' => Geo::getDistance($lat, $lng, $return['lat2'], $return['lng2']),
			'e' => Geo::getDistance($lat, $lng, $lat, $return['lng2']),
			'se' => Geo::getDistance($lat, $lng, $return['lat1'], $return['lng2']),
			's' => Geo::getDistance($lat, $lng, $return['lat1'], $lng),
			'sw' => Geo::getDistance($lat, $lng, $return['lat1'], $return['lng1']),
			'w' => Geo::getDistance($lat, $lng, $lat, $return['lng1']),
			'nw' => Geo::getDistance($lat, $lng, $return['lat2'], $return['lng1'])
		);
		foreach ($distances as &$val) {
			$val = number_format($val, 6);
		}
		unset ($val);

		$expected = array (
			'n' => 0.709846,
			'ne' => 1.003874,
			'e' => 0.709846,
			'se' => 1.003874,
			's' => 0.709846,
			'sw' => 1.003874,
			'w' => 0.709846,
			'nw' => 1.003874
		);
		foreach ($expected as &$val) {
			$val = number_format($val, 6);
		}
		unset ($val);

		$this->assertSame($expected, $distances, "Expected distances, in KM differ");
	}
}
