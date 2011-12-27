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
 * Base controller
 *
 * This is the base class for all controllers. It will determine the to-run
 * method (action), check permissions and instantiate the actual controller
 * object.
 *
 * All application controllers should inherit this one. Application controllers
 * are the ones for backend and frontend, not the actual "working" controllers
 * for addOns and backend/frontend pages.
 *
 * @ingroup controller
 * @author  Zozi
 * @since   0.1
 */
abstract class sly_Controller_Base {
	protected $content_type = null; ///< string  the content type
	protected $charset      = null; ///< string  the character set

	/**
	 * Set the content type
	 *
	 * @param string $type  the new content type
	 */
	protected function setContentType($type) {
		$this->content_type = $type;

		// as long as we have kind of a 'transparent' response object,
		// let's assume that the response to-be-sent is known in sly_Core.
		sly_Core::getResponse()->setContentType($type);
	}

	/**
	 * Get the content type
	 *
	 * @return string  the content type (null if not set yet)
	 */
	protected function getContentType() {
		return $this->content_type;
	}

	/**
	 * Set the charset
	 *
	 * @param string $charset  the new charset
	 */
	protected function setCharset($charset) {
		$this->charset = $charset;

		// as long as we have kind of a 'transparent' response object,
		// let's assume that the response to-be-sent is known in sly_Core.
		sly_Core::getResponse()->setCharset($charset);
	}

	/**
	 * Get the charset
	 *
	 * @return string  the charset (null if not set yet)
	 */
	protected function getCharset() {
		return $this->charset;
	}

	/**
	 * Render a view
	 *
	 * This method renders a view, making all keys in $params available as
	 * variables.
	 *
	 * @param  string $filename  the filename to include, relative to the view folder
	 * @param  array  $params    additional parameters (become variables)
	 * @return string            the generated output
	 */
	protected function render($filename, array $params = array()) {
		// make sure keys in $params won't overwrite $filename and $params
		$filenameHtuG50hNCdikAvf7CZ1F = $filename;
		$paramsHtuG50hNCdikAvf7CZ1F   = $params;
		unset($filename);
		unset($params);
		extract($paramsHtuG50hNCdikAvf7CZ1F);

		ob_start();
		include $this->getViewFolder().$filenameHtuG50hNCdikAvf7CZ1F;
		return ob_get_clean();
	}

	/**
	 * Init callback
	 *
	 * This method will be executed right before the real action method is
	 * executed. Use this to setup your controller, like init the layout head
	 * other stuff every action should perform.
	 *
	 * @todo  Make this init($action) once we decide to make all controllers
	 *        aware of this param
	 */
	public function init() {
	}

	/**
	 * Teardown callback
	 *
	 * This method will be executed right after the real action method is
	 * executed. Use this to cleanup after your work is done.
	 */
	public function teardown() {
	}

	/**
	 * Get view folder
	 *
	 * Controllers must implement this method to specify where its view files
	 * are located. In most cases, since you will actually inherit the backend
	 * controller, this is already done. If you need to include many, many views,
	 * you might want to override this method to keep your view filenames short.
	 *
	 * @return string  the path to the view files
	 */
	abstract protected function getViewFolder();

	/**
	 * Default controller action
	 *
	 * Implement this method to allow access to your controller. It will be
	 * called when no distinct action parameter has been set, so in most cases
	 * this is the entry point to your controller (from a user perspective).
	 */
	abstract protected function index();

	/**
	 * Check access
	 *
	 * This method should check whether the current user (if any) has access to
	 * the requested action. In many cases, you will just make sure someone is
	 * logged in at all, but you can also decide this on a by-action basis.
	 *
	 * @return boolean  true if access is granted, else false
	 */
	abstract protected function checkPermission();
}
