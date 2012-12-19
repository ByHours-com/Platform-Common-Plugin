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
 * testGetBoxNWSE
 *
 * For the box:
 *  56,10-56,11
 *  |         |
 *  55,10-55-11
 *
 * @return void
 */
	public function testGetBoxNWSE() {
		$expected = array(
			'north' => 56,
			'east' => 11,
			'south' => 55,
			'west' => 10,
			'lat1' => 55,
			'lat2' => 56,
			'lng1' => 10,
			'lng2' => 11
		);
		$return = Geo::getBox('56,10', '55,11');
		$this->assertSame($expected, $return);
	}

/**
 * testGetBoxSWNE
 *
 * For the box:
 *  56,10-56,11
 *  |         |
 *  55,10-55-11
 *
 * @return void
 */
	public function testGetBoxSWNE() {
		$expected = array(
			'north' => 56,
			'east' => 11,
			'south' => 55,
			'west' => 10,
			'lat1' => 55,
			'lat2' => 56,
			'lng1' => 10,
			'lng2' => 11
		);
		$return = Geo::getBox('55,10', '56,11');
		$this->assertSame($expected, $return);
	}

/**
 * testGetBoxNESW
 *
 * For the box:
 *  56,10-56,11
 *  |         |
 *  55,10-55-11
 *
 * @return void
 */
	public function testGetBoxNESW() {
		$expected = array(
			'north' => 56,
			'east' => 11,
			'south' => 55,
			'west' => 10,
			'lat1' => 55,
			'lat2' => 56,
			'lng1' => 10,
			'lng2' => 11
		);
		$return = Geo::getBox('56,11', '55,10');
		$this->assertSame($expected, $return);
	}

/**
 * testGetBoxSENW
 *
 * For the box:
 *  56,10-56,11
 *  |         |
 *  55,10-55-11
 *
 * @return void
 */
	public function testGetBoxSENW() {
		$expected = array(
			'north' => 56,
			'east' => 11,
			'south' => 55,
			'west' => 10,
			'lat1' => 55,
			'lat2' => 56,
			'lng1' => 10,
			'lng2' => 11
		);
		$return = Geo::getBox('55,11', '56,10');
		$this->assertSame($expected, $return);
	}

/**
 * testGetBoxFloat
 *
 */
	public function testGetBoxFloat() {
		$expected = array(
			'north' => 56.123,
			'east' => 11.456,
			'south' => 55.123,
			'west' => 10.456,
			'lat1' => 55.123,
			'lat2' => 56.123,
			'lng1' => 10.456,
			'lng2' => 11.456
		);
		$return = Geo::getBox('56.123,10.456', '55.123,11.456');
		$this->assertSame($expected, $return);
	}

/**
 * testGetBoundary
 *
 * Getboundary draws a box where the corners are <x> from the center
 * where <x> is half the first parameter which defines the diagonal
 * distance across the resultant box
 */
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

/**
 * testGetBoundaryOuterBox
 *
 * Demonstrate how to use getBoundary to define a box which includes all coordinates
 * that are x km from the central coordinate
 * @return void
 */
	public function testGetBoundaryOuterBox() {
		$lat = 0;
		$lng = 0;

		$radiusKm = 1;
		$radiusDeg = 360 * $radiusKm / (Geo::EARTH_RADIUS * 2 * M_PI);
		$diameterDegInnerBox = $radiusDeg * 2 * sqrt(2);

		$return = Geo::getBoundary($diameterDegInnerBox, $lat, $lng);
		foreach ($return as &$val) {
			$val = number_format($val, 6);
		}
		unset ($val);

		$expected = array(
			'lat1' => -0.009026,
			'lat2' => 0.009026,
			'lng1' => -0.009026,
			'lng2' => 0.009026
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
			'n' => 1.003929,
			'ne' => 1.419769,
			'e' => 1.003929,
			'se' => 1.419769,
			's' => 1.003929,
			'sw' => 1.419769,
			'w' => 1.003929,
			'nw' => 1.419769
		);
		foreach ($expected as &$val) {
			$val = number_format($val, 6);
		}
		unset ($val);

		$this->assertSame($expected, $distances, "Expected distances, in KM differ");
	}
}
