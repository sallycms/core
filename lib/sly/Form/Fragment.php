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
 * A form fragment
 *
 * A fragment can be used as a last resort when you really, really need to
 * inject custom HTML between to form elements. You can put any HTML code you
 * like in a fragment, but be sure you don't screw up the form. And make sure
 * you have a very good reason to do so.
 *
 * A common usecase for this is grouping multiple elements in a special <div>
 * container, so that you can hide/show them all at once.
 *
 * @ingroup form
 * @author  Christoph
 */
class sly_Form_Fragment extends sly_Form_Container {
	/**
	 * Constructor
	 *
	 * @param string $content  the fragment HTML code
	 */
	public function __construct($content = '') {
		parent::__construct(null, '', '');
		$this->setContent($content);
	}

	/**
	 * Renders the element
	 *
	 * @return string  the XHTML code (the content)
	 */
	public function render() {
		return $this->content;
	}
}
