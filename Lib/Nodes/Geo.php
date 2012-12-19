<?php
namespace Nodes;

/**
 * Geo Class
 *
 * Utility class for geo related calculations
 *
 * Copyright 2010-2012, Nodes ApS. (http://www.nodesagency.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Nodes ApS, 2012
 */
class Geo {

/**
 * Mean radius of the earth in kilometers.
 *
 * @var double
 */
	const EARTH_RADIUS = 6372.797;

/**
 * Pi divided by 180 degrees. Calculated with PHP Pi constant.
 *
 * @var double
 */
	const PI180 = 0.017453293;

/**
 * Constant for converting kilometers into mt.
 *
 * @var double
 */
	const MT = 1000;

/**
 * getBox
 *
 * For the given arguments, return the bounds of the box they contain - throw exceptions
 * if arguments are missing.
 *
 * Accepts any combination of coordinates (NE, SW), (NW, SE), (SW, NE), (SE, NW)
 *
 * Returns coordinates equivalent to bottom-left, top-right in all cases to be consistent with
 * getBoundary
 *
 * @throws \InvalidArgumentException if arguments are missing or malformed
 * @param mixed $cornerCoordinate e.g. 55.5,12,2
 * @param mixed $otherCornerCoordinate e.g. 56.6,13.3
 * @return array
 */
	public static function getBox($cornerCoordinate, $otherCornerCoordinate) {
		if (!strpos($cornerCoordinate, ',') || !strpos($otherCornerCoordinate, ',')) {
			throw new \InvalidArgumentException("Required arguments missing or malformed");
		}

		list($north, $west) = explode(',', $cornerCoordinate);
		list($south, $east) = explode(',', $otherCornerCoordinate);

		if ($north < $south) {
			list($south, $north) = array($north, $south);
		}
		if ($east < $west) {
			list($west, $east) = array($east, $west);
		}

		$return = compact('north', 'east', 'south', 'west');
		$return += array(
			'lat1' => $south,
			'lat2' => $north,
			'lng1' => $west,
			'lng2' => $east
		);
		foreach ($return as &$val) {
			$val += 0;
		}
		return $return;
	}

/**
 * Calculate the bounding box from one geo point and $dist degrees out
 *
 * @param $dist Degress radius in the circle
 * @param $lat The latitude of the center point
 * @param $lng The longitude of the center point
 * @return array
 */
	public static function getBoundary($dist, $lat, $lng) {
		static::_findLatBoundary($dist, $lat, $south, $north);
		static::_findLonBoundary($dist, $lat, $lng, $south, $north, $west, $east);

		return array(
			'lat1' => $south,
			'lat2' => $north,
			'lng1' => $west,
			'lng2' => $east
		);
	}

/**
 * Calculate distance between two points of latitude and longitude.
 *
 * @param double $south The first point of latitude.
 * @param double $west The first point of longitude.
 * @param double $north The second point of latitude.
 * @param double $east The second point of longitude.
 * @param bool $kilometers Set to false to return in miles.
 * @return double The distance in kilometers or mt, whichever selected.
 */
	public static function getDistance($south, $west, $north, $east, $kilometers = true) {
		$south	*= self::PI180;
		$west	*= self::PI180;
		$north	*= self::PI180;
		$east	*= self::PI180;

		$dlat	= $north - $south;
		$dlong	= $east - $west;

		$a		= sin($dlat / 2) * sin($dlat / 2) + cos($south) * cos($north) * sin($dlong / 2) * sin($dlong / 2);
		$c		= 2 * atan2(sqrt($a), sqrt(1 - $a));

		$km		= self::EARTH_RADIUS * $c;

		if ($kilometers) {
			return $km;
		}

		return $km * self::MT;
	}

	protected static function _findLatBoundary($dist, $lat, &$south, &$north) {
		$d = ($dist / static::EARTH_RADIUS * 2 * M_PI) * 360;
		$south = $lat - $d;
		$north = $lat + $d;

		if ($south > $north) {
			list($south, $north) = array($north, $south);
		}
	}

	protected static function _findLonBoundary($dist, $lat, $lng, $south, $north, &$west, &$east) {
		$d = $lat - $south;

		$d1 = $d / cos(deg2rad($south));
		$d2 = $d / cos(deg2rad($north));

		$west = min($lng - $d1, $lng - $d2);
		$east = max($lng + $d1, $lng + $d2);

		if ($west > $east) {
			list($west, $east) = array($east, $west);
		}
	}
}
