<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * System Configuration Reader Interface
 *
 * @ingroup core
 */
interface sly_Configuration_Reader {

	/**
	 * @return array  Data from project config facility
	 */
	public function readProject();

	/**
	 * @return array  Data from local config facility
	 */
	public function readLocal();
}