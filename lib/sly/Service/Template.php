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
 * Service-Klasse für Templates
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Template extends sly_Service_DevelopBase {
	const DEFAULT_TYPE = 'default'; ///< string

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
			'class'    => isset($data['class']) ? $data['class'] : null,
			'active'   => isset($data['active']) ? $data['active'] : false,
			'slots'    => sly_makeArray(isset($data['slots']) ? $data['slots'] : self::$defaultSlots),
			'modules'  => isset($data['modules']) ? $data['modules'] : array(),
			'mtime'    => $mtime
		);

		unset($data['name'], $data['title'], $data['class'], $data['active'], $data['slots'], $data['modules']);
		$result['params'] = $data;

		return $result;
	}

	/**
	 * @param  string $name
	 * @param  string $filename
	 * @return boolean
	 */
	protected function flush($name = null, $filename = null) {
		if ($name === null && $filename === null) {
			$dir   = new sly_Util_Directory($this->getCacheFolder());
			$files = $dir->listPlain(true, false, false, true, '');
		}
		elseif ($this->exists($name)) {
			$files = array($this->getCacheFile($name, $filename));
		}
		else {
			return false;
		}

		array_map('unlink', array_filter($files, 'file_exists'));
		return true;
	}

	/**
	 * Get available templates from this service
	 *
	 * Templates may be filtered by a class parameter. If class is set, only
	 * the from this class will be returned. If class is null, all templates
	 * will be returned.
	 *
	 * @param  string $class  The class to filter. (default: null - no filtering)
	 * @return array          List of templates of the form: array('NAME' => 'TITLE', ...)
	 */
	public function getTemplates($class = null) {
		$result = array();

		foreach ($this->getData() as $name => $types) {
			if (empty($class) || $this->getClass($name) == $class) {
				$result[$name] = $this->getTitle($name);
			}
		}

		return $result;
	}

	/**
	 * Get the cache folder where template cache-files are stored
	 *
	 * @throws sly_Exception  When no cache folder is available and could not be created
	 * @return string         the cache directory
	 */
	public function getCacheFolder() {
		$dir = sly_Util_Directory::join(SLY_DYNFOLDER, 'internal/sally', $this->getClassIdentifier());

		if (!is_dir($dir) && !@mkdir($dir, sly_Core::getDirPerm(), true)) {
			throw new sly_Exception('Konnte Cache-Verzeichnis '.$dir.' nicht erstellen.');
		}

		return $dir;
	}

	/**
	 * Generate the template cache-file for a template
	 *
	 * @throws sly_Exception  When the given template does ot exist
	 * @param  string $name   Unique template name
	 * @return string         the template file name
	 */
	public function getGenerated($name) {
		if (!$this->exists($name)) {
			throw new sly_Exception("Template '$name' does not exist.");
		}

		$filename     = $this->filterByCondition($name, $this->getFileType());
		$templateFile = $this->getCacheFile($name, $filename);

		if (!file_exists($templateFile)) {
			$content = $this->getContent($filename);

			foreach (sly_Core::getVarTypes() as $var) {
				$content = $var->getTemplate($content);
			}

			if (!file_put_contents($templateFile, $content) > 0) {
				return false;
			}
		}

		return $templateFile;
	}

	/**
	 * @param  string $name
	 * @param  string $filename
	 * @return string
	 */
	public function getCacheFile($name, $filename) {
		return sly_Util_Directory::join($this->getCacheFolder(), $name.'-'.md5($filename).'.php');
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
	 * Get the valid modules for the given template and slot
	 *
	 * The modules are filtered by the constraints that are made in the
	 * template configuration AND the module configuration.
	 *
	 * @param  string $name  Template name
	 * @param  string $slot  Slot identifier
	 * @return array         Array of module names
	 */
	public function getModules($name, $slot = null) {
		$moduleService = sly_Service_Factory::getModuleService();
		$modules       = sly_makeArray($this->get($name, 'modules'));
		$slots         = $this->getSlots($name);
		$result        = array();

		// check if slot is valid
		if (isset($slot) || self::hasSlot($name, $slot)) {
			$allModules = array_keys($moduleService->getModules());

			// find modules for this template
			if (empty($modules)) {
				$modules = $allModules;
			}
			elseif ($this->isModulesDefComplex($modules)) {
				if (!array_key_exists($slot, $modules)) {
					$modules = $allModules;
				}
				else {
					$tmp = array();

					foreach ($modules as $key => $value) {
						if ($slot === null || $slot === $key || ($key === '_ALL_' && !self::hasSlot($name, '_ALL_'))) {
							$value = sly_makeArray($value);
							$tmp   = array_merge($tmp, array_values($value));
						}
					}

					$modules = $tmp;
				}
			}

			// filter modules by their constraints
			foreach ($modules as $module) {
				if ($moduleService->exists($module) && $moduleService->hasTemplate($module, $name)) {
					$result[$module] = $moduleService->getTitle($module);
				}
			}
		}

		return $result;
	}

	/**
	 * Checks, if the modules definitions are complex in the template
	 *
	 * complex: {slot1: wymeditor, slot2: [module1, module2]}
	 * simple:  [wymeditor, module1]
	 *
	 * @param  array $modules  Array of modules parsed from the template
	 * @return boolean         true, when the definition is complex
	 */
	private function isModulesDefComplex($modules) {
		return sly_Util_Array::isAssoc($modules) || sly_Util_Array::isMultiDim($modules);
	}

	/**
	 * Checks, if the given template is active
	 *
	 * @param  string $name  Unique template name
	 * @return boolean       true, if the template is active
	 */
	public function isActive($name) {
		return (boolean) $this->get($name, 'active', false);
	}

	/**
	 * Get the filename of the template
	 *
	 * @param  string $name  Unique template name
	 * @return string        The templates filename
	 */
	public function getFilename($name) {
		return $this->get($name, 'filename');
	}

	/**
	 * Checks, if the template has a specific module
	 *
	 * @param  string $name    Unique template name
	 * @param  string $module  Module name to check
	 * @param  string $slot    The template slot to check
	 * @return boolean         true, when the module is allowed in the given template and slot
	 */
	public function hasModule($name, $module, $slot = null) {
		if (!$this->exists($name)) return false;

		$modules = $this->getModules($name, $slot);
		return array_key_exists($module, $modules);
	}

	/**
	 * Performs an include for the given template
	 *
	 * A given params array will be extracted to variables and is available
	 * in the template code.
	 *
	 * @param string $name    Unique template name
	 * @param array  $params  Array of params to be available in the template
	 */
	public function includeFile($name_C3476zz3g21ug327ur623, $params = array()) {
		$templateFile_C3476zz3g21ug327ur623 = $this->getGenerated($name_C3476zz3g21ug327ur623);
		if (!empty($params)) extract($params);
		include $templateFile_C3476zz3g21ug327ur623;
	}
}
