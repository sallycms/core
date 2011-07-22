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
 * Base class for elements
 *
 * This class wraps some common functionality for all form elements.
 *
 * @ingroup form
 * @author  Christoph
 */
abstract class sly_Form_ElementBase extends sly_Viewable {
	protected $label;         ///< string
	protected $attributes;    ///< array
	protected $helpText;      ///< string
	protected $outerClass;    ///< string
	protected $formRowClass;  ///< string
	protected $multilingual;  ///< boolean

	/**
	 * Constructor
	 *
	 * @param string  $name   the element's name
	 * @param string  $label  the label
	 * @param mixed   $value  the value
	 * @param string  $id     optional ID (if it should differ from $name)
	 */
	public function __construct($name, $label, $value, $id = null) {
		$this->attributes   = array();
		$this->label        = $label;
		$this->outerClass   = '';
		$this->formRowClass = '';
		$this->multilingual = false;

		$this->setAttribute('name',  $name);
		$this->setAttribute('value', $value);
		$this->setAttribute('id',    $id === null ? $name : $id);
	}

	public function getID()    { return $this->getAttribute('id', '');    }  ///< @return string
	public function getName()  { return $this->getAttribute('name', '');  }  ///< @return string
	public function getValue() { return $this->getAttribute('value', ''); }  ///< @return string

	/**
	 * Returns the label
	 *
	 * @return string  the label
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * Returns an attribute
	 *
	 * @param  string $name     the attribute's name
	 * @param  mixed  $default  the default value
	 * @return mixed            the value or the default value
	 */
	public function getAttribute($name, $default = null) {
		return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
	}

	/**
	 * Sets an attribute
	 *
	 * @param  string $name   the attribute's name
	 * @param  mixed  $value  the new value
	 */
	public function setAttribute($name, $value) {
		$this->attributes[$name] = $value;
	}

	/**
	 * Removes an attribute
	 *
	 * @param string $name  the attribute's name
	 */
	public function removeAttribute($name) {
		unset($this->attributes[$name]);
	}

	/**
	 * Adds a new CSS class
	 *
	 * This method will add a new CSS class to the element. Classes are
	 * automatically made unique.
	 *
	 * @param string $className  the CSS class
	 */
	public function addClass($className) {
		$class   = strval($this->getAttribute('class'));
		$classes = empty($class) ? array() : explode(' ', $class);

		if (!in_array($className, $classes)) {
			$classes[] = $className;
		}

		$this->setAttribute('class', implode(' ', array_unique($classes)));
	}

	/**
	 * Adds a new CSS style
	 *
	 * This method will add a new CSS style to the element. Styles are
	 * automatically made unique.
	 *
	 * @param string $style  the CSS style
	 */
	public function addStyle($style) {
		$styles = strval($this->getAttribute('style'));
		$styles = empty($styles) ? array() : explode(';', $styles);

		if (!in_array($style, $styles)) {
			$styles[] = $style;
		}

		$this->setAttribute('style', implode(' ', array_unique($styles)));
	}

	/**
	 * Returns the attributes as a HTML string
	 *
	 * @param  array $exclude  list of attribute names to exclude
	 * @return string          string like 'foo="bar" name="sly"'
	 */
	protected function getAttributeString($exclude = array()) {
		$exclude    = sly_makeArray($exclude);
		$attributes = array();

		foreach ($this->attributes as $name => &$value) {
			if (!is_array($value) && strlen($value) > 0 && !in_array($name, $exclude)) {
				$attributes[] = $name.'="'.sly_html($value).'"';
			}
		}

		return implode(' ', $attributes);
	}

	/**
	 * Enables or disables the element
	 *
	 * This method is just a wrapper for setting/removing the disabled attribute.
	 *
	 * @param boolean $disabled  true to disabled the element, else false
	 */
	public function setDisabled($disabled = true) {
		if ($disabled) $this->setAttribute('disabled', 'disabled');
		else $this->removeAttribute('disabled');
	}

	/**
	 * Sets the help text
	 *
	 * The help text will be displayed below the element in a smaller font. HTML
	 * is not allowed.
	 *
	 * @param string $helpText  the new help text
	 */
	public function setHelpText($helpText) {
		$this->helpText = $helpText;
	}

	/**
	 * Sets the label
	 *
	 * The label will be displayed left to the element. HTML is not allowed. Use
	 * Spaces to indent the label (leading spaces will be converted to &nbsp;).
	 *
	 * @param string $label  the new label
	 */
	public function setLabel($label) {
		$this->label = $label;
	}

	/**
	 * Returns the help text
	 *
	 * @return string  the help text
	 */
	public function getHelpText() {
		return $this->helpText;
	}

	/**
	 * Returns the outer row class
	 *
	 * @return string  the outer class
	 */
	public function getOuterClass() {
		return $this->outerClass;
	}

	/**
	 * Returns the form row class
	 *
	 * @return string  the form row class
	 */
	public function getFormRowClass() {
		return $this->formRowClass;
	}

	/**
	 * Container check
	 *
	 * This method checks whether an element is rendering a complete form row
	 * (including the label part, if needed) or if it's just the raw element
	 * (in this case, the form instance will render the label).
	 *
	 * @return boolean  always false
	 */
	public function isContainer() {
		return false;
	}

	/**
	 * Return language status
	 *
	 * @return boolean  true if the element is multilingual, else false
	 */
	public function isMultilingual() {
		return $this->multilingual;
	}

	/**
	 * Sets the elements multilingual status
	 *
	 * Set the element to multilingual if you want Sally to automatically create
	 * X versions of the element for each language in your project. If so, you
	 * have to give the value of this element in form of an array (clang =>
	 * value).
	 *
	 * @param  boolean $multilingual  the new status
	 * @return boolean                the new status
	 */
	public function setMultilingual($multilingual = true) {
		$this->multilingual = (boolean) $multilingual;
		return $this->multilingual;
	}

	/**
	 * Returns the value to be displayed
	 *
	 * This method will return the values that shall be displayed in the form.
	 * This is mostly useful when a form is submitted and the POST data will be
	 * shown instead of those that were given when the form elements are
	 * initialized.
	 *
	 * @param  string  $type     the param to use in sly_post()
	 * @param  boolean $asArray  true to get an array, or else false
	 * @return mixed             the value(s) to display
	 */
	public function getDisplayValueHelper($type = 'string', $asArray = false) {
		// Prüfen, ob das Formular bereits abgeschickt und noch einmal angezeigt
		// werden soll. Falls ja, übernehmen wir den Wert aus den POST-Daten.
		
		$name = $this->attributes['name'];

		if (isset($_POST[$name]) && !$asArray) {
			return sly_post($name, $type);
		}

		if (isset($_POST[$name]) && $asArray) {
			return sly_postArray($name, $type);
		}

		return $this->attributes['value'];
	}

	/**
	 * Get the form element name
	 *
	 * @return string  the element name
	 */
	public function getDisplayName() {
		return $this->getAttribute('name');
	}

	/**
	 * Renders a form template
	 *
	 * @param  string $filename  the file to render, relative to include/views/_form
	 * @return string            the HTML code
	 */
	protected function renderFilename($filename) {
		return $this->renderView($filename);
	}

	/**
	 * Get the full path for a view
	 *
	 * This methods prepends the filename of a specific view with its path. If
	 * the view is not found inside the core, an exception is thrown.
	 *
	 * @throws sly_Form_Exception  if the view could not be found
	 * @param  string $file        the relative filename
	 * @return string              the full path to the view file
	 */
	protected function getViewFile($file) {
		$full = SLY_COREFOLDER.'/views/form/'.$file;
		if (file_exists($full)) return $full;

		throw new sly_Form_Exception('View '.$file.' could not be found.');
	}

	/**
	 * Adds a new outer row class
	 *
	 * This method will add a new CSS class to the element. Classes are
	 * automatically made unique.
	 *
	 * @param string $className  the CSS class
	 */
	public function addOuterClass($className) {
		$class   = strval($this->outerClass);
		$classes = empty($class) ? array() : explode(' ', $class);

		if (!in_array($className, $classes)) {
			$classes[] = $className;
		}

		$this->outerClass = implode(' ', array_unique($classes));
	}

	/**
	 * Adds a new form row class
	 *
	 * This method will add a new CSS class to the element. Classes are
	 * automatically made unique.
	 *
	 * @param string $className  the CSS class
	 */
	public function addFormRowClass($className) {
		$class   = strval($this->formRowClass);
		$classes = empty($class) ? array() : explode(' ', $class);

		if (!in_array($className, $classes)) {
			$classes[] = $className;
		}

		$this->formRowClass = implode(' ', array_unique($classes));
	}
}
