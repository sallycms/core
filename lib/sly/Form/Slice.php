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
 * Form slice
 *
 * A slice contains a list of form elements and hidden values and serves as a
 * "light form". Its main purpose is to act as a collection for form elements,
 * so that API calls can return not only one, but many form elements to the
 * caller (but need no access to the final form). The caller can then decide to
 * integrate a slice into its form or to throw it away.
 *
 * Slices cannot be rendered, as they should only *contain* elements. The
 * render() method therefore throws an exception if you try to call it.
 *
 * Slices are used by the developer utils datatypes.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Slice extends sly_Form_Base {
	protected $rows; ///< array list of form elements

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->hiddenValues = array();
		$this->rows         = array();
	}

	/**
	 * Adds a new row with elements to the form
	 *
	 * This method can be used to add a row to a slice.
	 *
	 * @param  array $row  list of form elements (sly_Form_IElement elements)
	 * @return boolean     always true
	 */
	public function addRow(array $row) {
		$this->rows[] = $row;
		return true;
	}

	/**
	 * Integrates the slice into a real form
	 *
	 * This method will add all rows of this slice to the given $form. It will
	 * also move all hidden values to the $form.
	 *
	 * By default, the elements will be added to the current (that's the last
	 * one in most cases) fieldset. You can give a special fieldset to override
	 * this behaviour.
	 *
	 * The slice can be surrounded by a special <div> with user defined
	 * attributes. Use this if you need to identify the slice content after the
	 * integration.
	 *
	 * @param sly_Form          $form            the target form
	 * @param array             $containerAttrs  list of attributes (if null, no container is created)
	 * @param sly_Form_Fieldset $fieldset        fieldset to append the elements to
	 */
	public function integrate(sly_Form $form, $containerAttrs = null, sly_Form_Fieldset $fieldset = null) {
		$fieldset = $fieldset instanceof sly_Form_Fieldset ? $fieldset : $form->getCurrentFieldset();

		if ($containerAttrs !== null) {
			$attrs = sly_makeArray($containerAttrs);

			$attrs['class'] = isset($attrs['class']) ? $attrs['class'].' sly-slice' : 'sly-slice';

			$fragment = new sly_Form_Fragment('<div '.sly_Util_HTML::buildAttributeString($attrs).'>');
			$fieldset->addRow($fragment);
		}

		foreach ($this->hiddenValues as $key => $value) {
			$form->addHiddenValue($key, $value['value'], $value['id']);
		}

		foreach ($this->rows as $row) {
			$fieldset->addRow($row);
		}

		if ($containerAttrs !== null) {
			$fieldset->addRow(new sly_Form_Fragment('</div>'));
		}
	}

	/**
	 * Removes all rows
	 */
	public function clear() {
		$this->rows = array();
	}

	/**
	 * Renders nothing, just here because of the interface
	 *
	 * Do not call me. I will throw up.
	 *
	 * @throws sly_Form_Exception  always
	 */
	public function render() {
		throw new sly_Form_Exception(t('cannot_render_slices'));
	}
}
