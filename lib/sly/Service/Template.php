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
 * Service class for managing templates
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Template extends sly_Service_DevelopBase {
	const DEFAULT_TYPE = 'default'; ///< string

	protected $wraps = array();

	private static $defaultSlots = array('default' => ''); ///< array

	/**
	 * @param  string $filename
	 * @return boolean
	 */
	protected function isFileValid($filename) {
		return preg_match('#\.php$#i', $filename);
	}

	/**
	 * @return string
	 */
	protected function getClassIdentifier() {
		return 'templates';
	}

	/**
	 * @param  string $filename
	 * @return string
	 */
	protected function getFileType($filename = '') {
		return self::DEFAULT_TYPE;
	}

	/**
	 * @return array
	 */
	public function getFileTypes() {
		return array(self::DEFAULT_TYPE);
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
			'slots'    => isset($data['slots']) ? sly_makeArray($data['slots']) : self::$defaultSlots,
			'mtime'    => $mtime
		);

		unset($data['name'], $data['title'], $data['slots']);
		$result['params'] = $data;

		return $result;
	}

	/**
	 * Get available templates from this service
	 *
	 * Templates may be filtered by a class parameter. If class is set, only
	 * the from this class will be returned. If class is null, all templates
	 * will be returned.
	 *
	 * @param  string $class  The class to filter (default: null - no filtering)
	 * @return array          List of templates of the form: array('NAME' => 'TITLE', ...)
	 */
	public function getTemplates($class = null) {
		$result = array();

		foreach ($this->getData() as $name => $types) {
			$cls = $this->getClass($name);

			if (empty($class) || (is_string($cls) && $cls === $class) || (is_array($cls) && in_array($class, $cls))) {
				$result[$name] = $this->getTitle($name);
			}
		}

		return $result;
	}

	/**
	 * Return the title of the template
	 *
	 * @param  string $name  Unique template name
	 * @return string        The Template title
	 */
	public function getTitle($name) {
		return $this->get($name, 'title');
	}

	/**
	 * Returns the class of a template
	 *
	 * The class may be used for classification and filtering
	 *
	 * @param  string $name     Unique template name
	 * @param  string $default  Default return value
	 * @return string           The templates class
	 */
	public function getClass($name, $default = '') {
		return $this->get($name, 'class', $default);
	}

	/**
	 * Gets a list of the slot names for a template
	 *
	 * The slots will be returned as an array. Only the names of the slots
	 * will be contained. To get the title for a slot, use
	 * sly_Service_Template::getSlotTitle($slotName);
	 *
	 * @param  string $name  Template name
	 * @return array         Array of slots
	 */
	public function getSlots($name) {
		return array_keys($this->get($name, 'slots', self::$defaultSlots));
	}

	/**
	 * Gets the title for a slot
	 *
	 * This title may be used for visualization
	 *
	 * @param  string $name      Unique template name
	 * @param  string $slotName  The slot name
	 * @return string            The slot title or an empty string
	 */
	public function getSlotTitle($name, $slotName) {
		$slots = $this->get($name, 'slots', self::$defaultSlots);
		return empty($slots[$slotName]) ? '' : $slots[$slotName];
	}

	/**
	 * Gets the first slot from the template
	 *
	 * @param  string $name  Unique template name
	 * @return string        The first slot (name) or null if the template has no slots
	 */
	public function getFirstSlot($name) {
		$slots = $this->getSlots($name); // gets the keys!
		return empty($slots) ? null : $slots[0];
	}

	/**
	 * Checks, if the given template has a given slot
	 *
	 * @param  string $name  Template name
	 * @param  string $slot  Slot name
	 * @return boolean       true, when the template has this slot
	 */
	public function hasSlot($name, $slot) {
		$slots = array_map('strval', $this->getSlots($name));
		return in_array($slot, $slots);
	}

	/**
	 * Get the filename of the template
	 *
	 * @param  string $name  Unique template name
	 * @return string        The templates filename
	 */
	public function getFilename($name) {
		if (!$this->exists($name)) {
			throw new sly_Exception("Template '$name' does not exist.");
		}

		return $this->filterByCondition($name, $this->getFileType());
	}

	/**
	 * Performs an include for the given template
	 *
	 * A given params array will be extracted to variables and is available
	 * in the template code.
	 *
	 * @param string $name    template name
	 * @param array  $params  array of params to be available in the template
	 */
	public function includeFile($name, array $params = array()) {
		array_push($this->wraps, null);

		$file = SLY_DEVELOPFOLDER.'/templates/'.$this->getFilename($name);
		sly_Util_Template::renderFile($file, $params, false);

		$wrap = array_pop($this->wraps);

		if ($wrap) {
			$content = ob_get_contents();
			ob_end_clean();

			$params = $wrap[1];
			$params[$wrap[2]] = $content;

			$this->includeFile($wrap[0], $params);
		}
	}

	/**
	 * Can be called inside of a template and wraps the given outer template ($name, $params)
	 * around the inner template. The outer template gets the content of the inner template
	 * by the paramater named by $contentName.
	 * This must be called before the first output of the inner template.
	 *
	 * @param string $name         template name
	 * @param array  $params       array of params to be available in the template
	 * @param string $contentName  name of the content variable
	 */
	public function wrapFile($name, array $params = array(), $contentName = 'content') {
		ob_start();
		array_pop($this->wraps);
		array_push($this->wraps, array($name, $params, $contentName));
 	}
}
