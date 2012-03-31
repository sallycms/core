<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Interface for Slices
 *
 * @author  zozi@webvariants.de
 * @ingroup model
 */
interface sly_Model_ISlice {

	/**
	 * get modulename
	 *
	 * @return string
	 */
	public function getModule();

	/**
	 * set modulename
	 *
	 * @param string $module
	 */
	public function setModule($module);

	/**
	 * add a SliceValue
	 *
	 * @param  string $finder
	 * @param  string $value
	 */
	public function setValue($finder, $value = null);

	/**
	 * return a SliceValue
	 *
	 * @param  string $finder
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function getValue($finder, $default = null);

	/**
	 * clear all current SliceValues and sert the new values
	 *
	 * @param  array an assoc array($finder => $value, ...)
	 * @return boolean
	 */
	public function setValues($values = array());

	/**
	 * return all SliceValues
	 *
	 * @return array an assoc array($finder => $value, ...)
	 */
	public function getValues();

	/**
	 * get the rendered output
	 *
	 * @return string
	 */
	public function getOutput();

}
