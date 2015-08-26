<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

interface sly_Router_Interface {
	public function match(sly_Request $request);
	public function appendRoute($route, array $values);
	public function prependRoute($route, array $values);
	public function getRoutes();
	public function clearRoutes();

	/**
	 * @deprecated  use the more explicit appendRoute() and prependRoute() methods
	 */
	public function addRoute($route, array $values);
}
