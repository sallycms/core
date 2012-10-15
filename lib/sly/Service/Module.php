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
 * Service-Klasse für Module
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Module extends sly_Service_DevelopBase {
	/**
	 * @param  string $filename
	 * @return boolean
	 */
	protected function isFileValid($filename) {
		return preg_match('#\.(input|output)\.php$#i', $filename);
	}

	/**
	 * @return string
	 */
	protected function getClassIdentifier() {
		return 'modules';
	}

	/**
	 * @param  string $filename
	 * @return string
	 */
	protected function getFileType($filename = '') {
		return substr($filename, -10) == '.input.php' ? 'input' : 'output';
	}

	/**
	 * @return array
	 */
	public function getFileTypes() {
		return array('input', 'output');
	}

	/**
	 * @param  string $filename
	 * @param  int    $mtime
	 * @param  array  $data
	 * @return array
	 */
	protected function buildData($filename, $mtime, $data) {
		$result = array(
			'filename' => $filename,
			'title'    => isset($data['title']) ? $data['title'] : $data['name'],
			'mtime'    => $mtime
		);

		unset($data['name'], $data['title']);
		$result['params'] = $data;

		return $result;
	}

	/**
	 * Get available modules from the service
	 *
	 * @return array  array of modules
	 */
	public function getModules() {
		$result = array();

		foreach ($this->getData() as $name => $params) {
			$result[$name] = isset($params['input']) ? $this->get($name, 'title', '', 'input') : $this->get($name, 'title', '', 'output');
		}

		return $result;
	}

	/**
	 * Get the title of a module
	 *
	 * @param  string $name  unique module name
	 * @return string        title of the module
	 */
	public function getTitle($name) {
		return $this->get($name, 'title', '');
	}

	/**
	 * Get the filename of the modules input file
	 *
	 * @param  string $name  unique module name
	 * @return string        the filename of the input file
	 */
	public function getInputFilename($name) {
		return $this->get($name, 'filename', null, 'input');
	}

	/**
	 * Get the filename of the modules output file
	 *
	 * @param  string $name  unique module name
	 * @return string        the filename of the output file
	 */
	public function getOutputFilename($name) {
		return $this->filterByCondition($name, 'output');
	}
}
