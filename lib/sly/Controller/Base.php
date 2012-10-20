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
	protected $content_type = null; ///< string       the content type
	protected $charset      = null; ///< string       the character set
	protected $request      = null; ///< sly_Request  the current request

	/**
	 * Set request
	 *
	 * This method is called by the application before the action is executed.
	 *
	 * @param sly_Request $request  the request the controller should act upon
	 */
	public function setRequest(sly_Request $request) {
		$this->request = $request;
	}

	/**
	 * get request
	 *
	 * @return sly_Request
	 */
	public function getRequest() {
		return $this->request;
	}

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
	 * @param  string  $filename      the filename to include, relative to the view folder
	 * @param  array   $params        additional parameters (become variables)
	 * @param  boolean $returnOutput  set to false to not use an output buffer
	 * @return string                 the generated output if $returnOutput, else null
	 */
	protected function render($filename, array $params = array(), $returnOutput = true) {
		unset($filename, $returnOutput);

		if (!empty($params)) {
			unset($params);
			extract(func_get_arg(1));
		}
		else {
			unset($params);
		}

		if (func_get_arg(2)) ob_start();
		include $this->getViewFolder().func_get_arg(0);
		if (func_get_arg(2)) return ob_get_clean();
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
}
