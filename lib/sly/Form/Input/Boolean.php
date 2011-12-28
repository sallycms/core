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
 * Base class for checkbox and radio box
 *
 * @ingroup form
 * @author  Christoph
 */
abstract class sly_Form_Input_Boolean extends sly_Form_Input_Base {
	protected $description; ///< string  text behind the checkbox
	protected $checks;      ///< array   list of check stati (for multilingual elements)

	/**
	 * Constructor
	 *
	 * @param string $name         element name
	 * @param string $label        the label
	 * @param array  $value        the current text
	 * @param string $description  text to show right next to the checkbox
	 * @param string $id           optional ID (if not given, the name is used)
	 */
	public function __construct($name, $label, $value, $description = 'ja', $id = null) {
		parent::__construct($name, $label, $value, $id);
		$this->description = $description;
		$this->checks      = array();
	}

	/**
	 * Renders the element
	 *
	 * This method renders the element.
	 *
	 * @return string  the XHTML code
	 */
	public function render() {
		$this->attributes['checked'] = $this->getDisplayValue() ? 'checked' : '';
		$attributeString = $this->getAttributeString();

		return
			'<input '.$attributeString.' /> '.
			'<label class="sly-inline" for="'.$this->attributes['id'].'">'.sly_html($this->description).'</label>';
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @return boolean  true if checked, else false
	 */
	public function getDisplayValue() {
		$name = $this->getAttribute('name');
		return isset($_POST[$name]) ? $_POST[$name] : $this->getAttribute('checked') === 'checked';
	}

	/**
	 * Sets the element status
	 *
	 * Toggles the checkbox. Use this only on monolingual elements.
	 *
	 * @param boolean $checked  true if the element is checked, else false
	 */
	public function setChecked($checked = true) {
		if ($checked) $this->setAttribute('checked', 'checked');
		else $this->removeAttribute('checked');
	}

	/**
	 * Sets the element status (multilingual)
	 *
	 * Toggles the checkbox for each language. Use this when the element is used
	 * in a multilingual form.
	 *
	 * @param array $checks  list of stati ({clang: checked, clang2: checked})
	 */
	public function setChecks($checks) {
		$this->checks = sly_makeArray($checks);
	}

	/**
	 * Gets the element status (multilingual)
	 *
	 * @return array  list of stati ({clang: checked, clang2: checked})
	 */
	public function getChecks() {
		return $this->checks;
	}
}
