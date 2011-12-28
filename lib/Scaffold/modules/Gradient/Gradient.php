<?php

/**
 * Gradient class
 *
 * @author Paul Clark
 * @version 1.0
 * @dependencies gradientgd.class.php
 */
class Gradient
{
	/**
	 * List of created gradients and their locations
	 *
	 * @var array
	 */
	public static $gradients = array();

	public static function create_gradient($direction, $size, $from, $to, $stops = false )
	{
		if (!class_exists('GradientGD'))
			include(dirname(__FILE__).'/libraries/gradientgd.php');
		
		$file = "{$direction}_{$size}_".str_replace('#','',$from)."_".str_replace('#','',$to).".png";

		if($direction == 'horizontal')
		{
			$height = 50;
			$width = $size;
			$repeat = 'y';
		}
		else
		{
			$height = $size;
			$width = 50;
			$repeat = 'x';
		}

		if(!Scaffold_Cache::exists('gradients/'.$file)) 
		{
			Scaffold_Cache::create('gradients');
			$file = Scaffold_Cache::find('gradients') . '/' . $file;
			$gradient = new GradientGD($width,$height,$direction,$from,$to,$stops);
			$gradient->save($file);
		}
		
		$file = Scaffold_Cache::find('gradients') . '/' . $file;

		
		self::$gradients[] = array
		(
			$direction,
			$size,
			$from,
			$to,
			$file
		);

		$properties = "
			background-position: top left;
		    background-repeat: repeat-$repeat;
		    background-image: url(".Scaffold::url_path($file).");
		";
		
		return $properties;

	}
	
	/**
	 * get css property for Webkit browser (Safari < 5, Chrome < 10)
	 *
	 * @param string $startPos
	 * @param string $endPos
	 * @param array $coloursArray
	 * @return string css code
	 */
	private static function getWebkitLinear($startPos, $endPos, $coloursArray) {
		
		$css = null;

		/* webkit */
		$colours = array();
		foreach ($coloursArray as $idx => $colour) {
			if (isset($colour['colour'])) {
				if ($idx === 0) {
					$colours[] = 'from('.$colour['colour'].')';
				}
				elseif ($idx === count($colours)-1) {
					$colours[] = 'to('.$colour['colour'].')';
				}
				else {
					$offset = null;
					if (isset($colour['offset'])) {
						$offset = $colour['offset'];
					}
					else {
						$offset = round($idx/(count($coloursArray)-1), 1);
						$offset = number_format($offset, 1, '.', '');
					}
					$colours[] = 'colour-stop('.$offset.','.$colour['colour'].')';
				}
			}
		}
		$colours = implode(', ', $colours);

		$css .= 'background-image: -webkit-gradient(linear, '.$startPos.', '.$endPos.', '.$colours.');';
		
		return $css;
	}
	
	/**
	 * get css property for Opera
	 *
	 * @param string $startPos
	 * @param string $endPos
	 * @param array $coloursArray
	 * @return string css code
	 */
	private static function getOperaLinear($startPos, $endPos, $coloursArray) {
		
		$css = null;

		$angle = null;
		/*
		 * TODO: calculate angle
		 */
		if ($angle) $startPos = null;

		$colours = array();
		foreach ($coloursArray as $colour) {
			if (isset($colour['colour'])) {
				if (isset($colour['offset'])) {
					$colours[] = $colour['colour'].' '.round($colour['offset']*100).'%';
				}
				else {
					$colours[] = $colour['colour'];
				}
			}
		}
		$colours = implode(', ', $colours);

		$css .= 'background-image: -o-linear-gradient('.$startPos.$angle.', '.$colours.');';
		
		return $css;
	}

	/**
	 * get css property for Safari >= 5, Chrome >= 10 and Firefox
	 *
	 * @param string $startPos
	 * @param string $endPos
	 * @param array $coloursArray
	 * @return string css code
	 */
	private static function getWkMozLinear($startPos, $endPos, $coloursArray) {

		$css = null;

		$angle = null;
		/*
		 * TODO: calculate angle
		 */
		$colours = array();
		foreach ($coloursArray as $colour) {
			if (isset($colour['colour'])) {
				if (isset($colour['offset'])) {
					$colours[] = $colour['colour'].' '.round($colour['offset']*100).'%';
				}
				elseif (isset($colour['colour'])) {
					$colours[] = $colour['colour'];
				}
			}
		}
		$colours = implode(', ', $colours);

		$css .= 'background-image: -webkit-linear-gradient('.$startPos.$angle.', '.$colours.');';
		$css .= 'background-image: -moz-linear-gradient('.$startPos.$angle.', '.$colours.');';

		return $css;
	}

	/**
	 * get css property for Internet Explorer > 8, which only can display an
	 * even gradient between two colours
	 *
	 * @param array $coloursArray
	 * @return string css code
	 */
	private static function getIELinear($coloursArray) {

		if (!is_array($coloursArray) || empty($coloursArray)) return false;

		$css = null;

		$startColour = null;
		$sC = array_shift($coloursArray);
		if (isset($sC['colour'])) $startColour = $sC['colour'];

		$endColour = null;
		$eC = array_shift($coloursArray);
		if (isset($eC['colour'])) $endColour = $eC['colour'];

		$css .= '-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColourstr='.$startColour.', endColourstr='.$endColour.')";';

		return $css;
	}

	/**
	 * get css linear-gradient properties for all common browsers
	 *
	 * @param string $startPos
	 * @param string $endPos
	 * @param array $coloursArray
	 * @return string css code
	 */
	public static function getLinear($startPos, $endPos, $coloursArray) {

		$css = null;
		
		$css .= self::getIELinear($coloursArray);
		$css .= self::getOperaLinear($startPos, $endPos, $coloursArray);
		$css .= self::getWebkitLinear($startPos, $endPos, $coloursArray);
		$css .= self::getWkMozLinear($startPos, $endPos, $coloursArray);

		return $css;
	}
}