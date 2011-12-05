<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Business Model for Slices
 *
 * @author  zozi@webvariants.de
 * @ingroup model
 */
class sly_Model_Slice extends sly_Model_Base_Id {
	protected $module; ///< string

	protected $_attributes = array('module' => 'string'); ///< array
	protected $_hasMany    = array('SliceValue' => array('delete_cascade' => true, 'foreign_key' => array('slice_id' => 'id'))); ///< array

	/**
	 * @return string
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * @param string $module
	 */
	public function setModule($module) {
		$this->module = $module;
	}

	/**
	 * @param  string $type
	 * @param  string $finder
	 * @param  string $value
	 * @return sly_Model_SliceValue
	 */
	public function addValue($type, $finder, $value = null) {
		$service = sly_Service_Factory::getSliceValueService();
		return $service->create(array('slice_id' => $this->getId(), 'type' => $type, 'finder' => $finder, 'value' => $value));
	}

	/**
	 * @param  string $type
	 * @param  string $finder
	 * @return sly_Model_SliceValue
	 */
	public function getValue($type, $finder) {
		$service    = sly_Service_Factory::getSliceValueService();
		$sliceValue = $service->findBySliceTypeFinder(array('slice_id' => $this->getId(), 'type' => $type, 'finder' => $finder));

		return $sliceValue;
	}

	/**
	 * @return int
	 */
	public function flushValues() {
		$service = sly_Service_Factory::getSliceValueService();
		return $service->delete(array('slice_id' => $this->getId()));
	}

	/**
	 * @return string
	 */
	public function getOutput() {
		$service  = sly_Service_Factory::getModuleService();
		$filename = $service->getOutputFilename($this->getModule());
		$output   = $service->getContent($filename);

		foreach (sly_Core::getVarTypes() as $idx => $var) {
			$data   = $var->getDatabaseValues($this->getId());
			$output = $var->getOutput($data, $output);
		}

		$output = $this->replaceLinks($output);
		$output = $this->replacePseudoConstants($output);

		return $output;
	}

	/**
	 * returns the input form for this slice
	 *
	 * @return string
	 */
	public function getInput() {
		$service  = sly_Service_Factory::getModuleService();
		$filename = $service->getInputFilename($this->getModule());
		$output   = $service->getContent($filename);
		$output   = $this->replacePseudoConstants($output);

		return $output;
	}

	/**
	 * Replaces sally://ARTICLEID and sally://ARTICLEID-CLANGID in
	 * the slice content by article http URLs.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function replaceLinks($content) {
		preg_match_all('#(?:redaxo|sally)://([0-9]+)(?:-([0-9]+))?/?#', $content, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

		$skew = 0;

		foreach ($matches as $match) {
			$complete = $match[0];
			$length   = strlen($complete[0]);
			$offset   = $complete[1];
			$id       = (int) $match[1][0];
			$clang    = isset($match[2]) ? (int) $match[2][0] : null;
			$repl     = sly_Util_Article::getUrl($id, $clang);

			// replace the match
			$content = substr_replace($content, $repl, $offset + $skew, $length);

			// ensure the next replacements get the correct offset
			$skew += strlen($repl) - $length;
		}

		return $content;
	}

	/**
	 * replace some pseude constants that can be used in slices
	 *
	 * @staticvar array  $search
	 * @param     string $content
	 * @return    string the content with replaces strings
	 */
	private function replacePseudoConstants($content) {
		static $search = array(
			'MODULE_NAME',
			'SLICE_ID'
		);

		$replace = array(
			$this->getModule(),
			$this->getId()
		);

		return str_replace($search, $replace, $content);
	}
}
