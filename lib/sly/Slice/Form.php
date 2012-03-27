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
 * @since  0.6
 * @author zozi@webvariants.de
 */
class sly_Slice_Form extends sly_Form {
	/**
	 * constructor
	 *
	 * @param string $action
	 * @param string $method
	 * @param string $title
	 * @param string $name
	 * @param string $id
	 */
	public function __construct($action = '', $method = '', $title = '', $name = '', $id = '') {
		parent::__construct($action, $method, $title, $name, $id);
	}

	/**
	 * add data index
	 *
	 * @param string $dataIndex
	 */
	protected function addDataIndex($dataIndex) {
		foreach ($this->fieldsets as $fieldset) {
			foreach ($fieldset->getRows() as $row) {
				foreach ($row as $element) {
					if ($element instanceof sly_Form_ElementBase) {
						$name = $dataIndex.'['.$element->getName().']';
						$element->setAttribute('name', $name);
					}
				}
			}
		}
	}

	/**
	 * render
	 *
	 * @param  string $dataIndex
	 * @return string
	 */
	public function render($dataIndex = null) {
		if (is_string($dataIndex)) {
			$this->addDataIndex($dataIndex);
		}

		return parent::render(true);
	}

	/**
	 * add input field
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  string  $value
	 * @param  string  $type
	 * @param  boolean $required
	 * @param  string  $placeholder
	 * @return sly_Form_Input_Base
	 */
	public function addInput($name, $label, $value = '', $type = 'text', $required = false, $placeholder = '') {
		$type = strtolower($type);

		if (!in_array($type, array('email', 'file', 'number', 'password', 'range', 'text', 'url'))) {
			throw new sly_Exception('Unexpected input type "'.$type.'" given.');
		}

		$className = 'sly_Form_Input_'.($type === 'url' ? 'URL' : ucfirst($type));
		$instance  = new $className($name, $label, $value);

		if ($required) {
			$instance->setRequired();
		}

		if ($placeholder) {
			$instance->setPlaceholder($placeholder);
		}

		$this->add($instance);
		return $instance;
	}

	/**
	 * add checkbox
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  string  $description
	 * @param  boolean $checked
	 * @return sly_Form_Input_Checkbox
	 */
	public function addCheckbox($name, $label, $description = '', $checked = false) {
		$instance = new sly_Form_Input_Checkbox($name, $label, 1, $description);

		if ($checked) {
			$instance->setChecked();
		}

		$this->add($instance);
		return $instance;
	}

	/**
	 * add textarea
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  string  $value
	 * @param  boolean $required
	 * @param  string  $placeholder
	 * @return sly_Form_Textarea
	 */
	public function addTextarea($name, $label, $value = '', $required = false, $placeholder = '') {
		$instance = new sly_Form_Textarea($name, $label, $value);

		if ($required) {
			$instance->setRequired();
		}

		if ($placeholder) {
			$instance->setPlaceholder($placeholder);
		}

		$this->add($instance);
		return $instance;
	}

	/**
	 * add text
	 *
	 * @param  string $label
	 * @param  string $text
	 * @return sly_Form_Text
	 */
	public function addText($label, $text) {
		$instance = new sly_Form_Text($label, $text);
		$this->add($instance);
		return $instance;
	}

	/**
	 * add select
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  array   $selected
	 * @param  array   $values
	 * @param  string  $type
	 * @param  boolean $multiple
	 * @param  int     $size
	 * @return sly_Form_Select_Base
	 */
	public function addSelect($name, $label, $selected, $values, $type = 'dropdown', $multiple = false, $size = null) {
		$type = strtolower($type);

		if (!in_array($type, array('dropdown', 'checkbox', 'radio'))) {
			throw new sly_Exception('Unexpected select type "'.$type.'" given.');
		}

		$className = 'sly_Form_Select_'.($type === 'dropdown' ? 'DropDown' : ucfirst($type));
		$instance  = new $className($name, $label, $selected, $values);

		if ($type === 'dropdown') {
			if ($multiple) {
				$instance->setMultiple(true);
			}

			if ($size) {
				$instance->setSize($size);
			}
		}

		$this->add($instance);
		return $instance;
	}

	/**
	 * add link
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  int     $value
	 * @param  boolean $required
	 * @param  array   $articleTypes
	 * @param  array   $categories
	 * @param  boolean $recursive
	 * @return sly_Form_Widget_Link
	 */
	public function addLink($name, $label, $value = null, $required = false, $articleTypes = null, $categories = null, $recursive = false) {
		$instance = new sly_Form_Widget_Link($name, $label, $value);
		return $this->addLinkWidget($instance, $required, $articleTypes, $categories, $recursive);
	}

	/**
	 * add link list
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  int     $value
	 * @param  boolean $required
	 * @param  array   $articleTypes
	 * @param  array   $categories
	 * @param  boolean $recursive
	 * @return sly_Form_Widget_LinkList
	 */
	public function addLinkList($name, $label, $value = null, $required = false, $articleTypes = null, $categories = null, $recursive = false) {
		$instance = new sly_Form_Widget_LinkList($name, $label, $value);
		return $this->addLinkWidget($instance, $required, $articleTypes, $categories, $recursive);
	}

	/**
	 * add link widget
	 *
	 * @param  sly_Form_Widget_LinkBase $widget
	 * @param  boolean                  $required
	 * @param  array                    $fileTypes
	 * @param  array                    $categories
	 * @param  boolean                  $recursive
	 * @return sly_Form_Widget_LinkBase
	 */
	protected function addLinkWidget(sly_Form_Widget_LinkBase $widget, $required = false, $articleTypes = null, $categories = null, $recursive = false) {
		if ($required) {
			$widget->setRequired();
		}

		if ($articleTypes !== null) {
			$widget->filterByArticleTypes($articleTypes);
		}

		if ($categories) {
			$widget->filterByCategories($categories, $recursive);
		}

		$this->add($widget);
		return $widget;
	}

	/**
	 * add media
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  int     $value
	 * @param  boolean $required
	 * @param  array   $fileTypes
	 * @param  array   $categories
	 * @param  boolean $recursive
	 * @return sly_Form_Widget_Media
	 */
	public function addMedia($name, $label, $value = null, $required = false, $fileTypes = null, $categories = null, $recursive = false) {
		$instance = new sly_Form_Widget_Media($name, $label, $value);
		return $this->addMediaWidget($instance, $required, $fileTypes, $categories, $recursive);
	}

	/**
	 * add media list
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  int     $value
	 * @param  boolean $required
	 * @param  array   $fileTypes
	 * @param  array   $categories
	 * @param  boolean $recursive
	 * @return sly_Form_Widget_MediaList
	 */
	public function addMediaList($name, $label, $value = null, $required = false, $fileTypes = null, $categories = null, $recursive = false) {
		$instance = new sly_Form_Widget_MediaList($name, $label, $value);
		return $this->addMediaWidget($instance, $required, $fileTypes, $categories, $recursive);
	}

	/**
	 * add media widget
	 *
	 * @param  sly_Form_Widget_MediaBase $widget
	 * @param  boolean                   $required
	 * @param  array                     $fileTypes
	 * @param  array                     $categories
	 * @param  boolean                   $recursive
	 * @return sly_Form_Widget_MediaBase
	 */
	protected function addMediaWidget(sly_Form_Widget_MediaBase $widget, $required = false, $fileTypes = null, $categories = null, $recursive = false) {
		if ($required) {
			$widget->setRequired();
		}

		if ($fileTypes !== null) {
			$widget->filterByFiletypes($fileTypes);
		}

		if ($categories) {
			$widget->filterByCategories($categories, $recursive);
		}

		$this->add($widget);
		return $widget;
	}

	/**
	 * @throws sly_Exception
	 * @param  string $method
	 * @param  array  $arguments
	 * @return mixed
	 */
	public function __call($method, $arguments) {
		$event      = strtoupper('SLY_SLICEFORM_'.$method);
		$dispatcher = sly_Core::dispatcher();

		if (!$dispatcher->hasListeners($event)) {
			throw new sly_Exception('Call to undefined method '.get_class($this).'::'.$method.'()');
		}

		return $dispatcher->filter($event, null, array('method' => $method, 'arguments' => $arguments, 'object' => $this));
	}
}
