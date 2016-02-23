<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * System Configuration Writer Interface
 *
 * @ingroup core
 */
interface sly_Configuration_Writer {
	public function writeProject(array $data);
	public function writeLocal(array $data);
}

