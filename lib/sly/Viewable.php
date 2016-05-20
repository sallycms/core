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
 * @ingroup layout
 */
abstract class sly_Viewable {
	/**
	 * @return string
	 */
	abstract public function render();

	/**
	 * @throws sly_Exception
	 * @param  string $file
	 * @return string
	 */
	abstract protected function getViewFile($file);

	/**
	 * @param  string $filename
	 * @param  array  $params
	 * @return string
	 */
	protected function renderView($filename, $params = array()) {
		// Die Parameternamen $params und $filename sind zu kurz, als dass
		// man sie zuverlässig nutzen könnte. Wenn $params durch extract()
		// während der Ausführung überschrieben wird kann das unvorhersehbare
		// Folgen haben. Darum werden $filename und $params in kryptische
		// Variablen verpackt und aus dem Kontext entfernt.
		$filenameHtuG50hNCdikAvf7CZ1F = $filename;
		$paramsHtuG50hNCdikAvf7CZ1F = $params;
		unset($filename);
		unset($params);
		extract($paramsHtuG50hNCdikAvf7CZ1F);

		ob_start();
		include $this->getViewFile($filenameHtuG50hNCdikAvf7CZ1F);
		return ob_get_clean();
	}
}
