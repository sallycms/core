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
 */
abstract class sly_Controller_Base {
	const PAGEPARAM    = 'page';    ///< string  the request param that contains the page
	const SUBPAGEPARAM = 'subpage'; ///< string  the request param that contains the subpage
	const ACTIONPARAM  = 'func';    ///< string  the request param that contains the action

	protected $content_type = null; ///< string  the content type
	protected $charset      = null; ///< string  the character set
	protected $action       = null; ///< string  the action (method) to be called

	/**
	 * Constructor
	 *
	 * Initializes the to be called action. By default, this is 'index'.
	 */
	protected function __construct() {
		$this->action = self::getActionParam('index');
	}

	/**
	 * Set the content type
	 *
	 * @param string $type  the new content type
	 */
	protected function setContentType($type) {
		$this->content_type = $type;
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
	 * Returns the full HTTP content type
	 *
	 * This method will combine content type and charset to the full type spec
	 * given in the Content-Type HTTP header.
	 *
	 * @return string  the content type or null it has not yet been set
	 */
	protected function computeContentType() {
		if (!empty($this->content_type)) {
			return $this->content_type.($this->charset ?  ('; charset='.$this->charset) : '');
		}

		return null;
	}

	/**
	 * Set the content type for the current request
	 *
	 * This method will used the combined content type to initialize the layout
	 * and send the HTTP header.
	 *
	 * @todo  This method should not force a specific Sally to be created
	 */
	protected function injectContentType() {
		$content_type = $this->computeContentType();

		if ($content_type) {
			header('Content-Type: '.$content_type);
			$layout = sly_Core::getLayout('Sally');
			$layout->addHttpMeta('Content-Type', $content_type);
		}
	}

	/**
	 * Get the page param
	 *
	 * Reads the page param from the $_REQUEST array and returns it.
	 *
	 * @param  string $default  default value if param is not present
	 * @return string           the page param
	 */
	public static function getPageParam($default = '') {
		return sly_request(self::PAGEPARAM, 'string', $default);
	}

	/**
	 * Get the subpage param
	 *
	 * Reads the subpage param from the $_REQUEST array and returns it.
	 *
	 * @param  string $default  default value if param is not present
	 * @return string           the subpage param
	 */
	public static function getSubpageParam($default = '') {
		return sly_request(self::SUBPAGEPARAM, 'string', $default);
	}

	/**
	 * Get the action param
	 *
	 * Reads the action param from the $_REQUEST array and returns it.
	 *
	 * @param  string $default  default value if param is not present
	 * @return string           the action param
	 */
	public static function getActionParam($default = '') {
		return sly_request(self::ACTIONPARAM, 'string', $default);
	}

	/**
	 * Get the currently active page
	 *
	 * The page determines the controller that will be used for dispatching. It
	 * will be put into $_REQUEST (so that third party code can access the
	 * correct value).
	 *
	 * When setup is true, requests to the setup controller will be redirected to
	 * the profile page (always accessible). Otherwise, this method will also
	 * check whether the current user has access to the found controller. If a
	 * forbidden controller is requested, the profile page is used.
	 *
	 * @return string  the currently active page
	 */
	public static function getPage() {
		$config = sly_Core::config();
		$page   = strtolower(self::getPageParam());

		// do not allow any access to setup controller when SETUP=false

		if ($config->get('SETUP') !== true && $page == 'setup') {
			$page = 'profile';
		}

		// Erst normale Startseite, dann User-Startseite, dann System-Startseite und
		// zuletzt auf die Profilseite zurückfallen.

		$nav = sly_Core::getNavigation();

		if (!$nav->hasPage($page) && !class_exists('sly_Controller_'.ucfirst($page))) {
			$page = sly_Service_Factory::getUserService()->getCurrentUser()->getStartpage();

			if (is_null($page) || !$nav->hasPage($page)) {
				$page = strtolower($config->get('START_PAGE'));

				if (!$nav->hasPage($page)) {
					$page = 'profile';
				}
			}
		}

		$_REQUEST[self::PAGEPARAM] = $page;
		return $page;
	}

	/**
	 * Creates the controller instance
	 *
	 * This method will construct the controller instance. It consists of the
	 * current page and subpage, whereas the subpage is only appended if it's not
	 * 'index'. The class name will look like 'sly_Controller_[Page]_[Subpage]',
	 * having the first character of both values in uppercase and the rest in
	 * lowercase.
	 *
	 * If the class could not be found, null is returned.
	 *
	 * @param  string $forcePage     use this if you want to get a specific controller, regardless of the request
	 * @param  string $forceSubpage  use this if you want to get a specific controller, regardless of the request
	 * @return sly_Controller_Base   the controller instance
	 */
	public static function factory($forcePage = null, $forceSubpage = null) {
		$config  = sly_Core::config();
		$page    = $forcePage === null    ? self::getPageParam($config->get('START_PAGE')) : $forcePage;
		$subpage = $forceSubpage === null ? strtolower(self::getSubpageParam()) : $forceSubpage;
		$name    = 'sly_Controller_'.ucfirst($page);

		if (!empty($subpage) && $subpage != 'index') {
			$name .= '_'.ucfirst($subpage);
		}

		if (class_exists($name)) {
			return new $name($name);
		}

		return null;
	}

	/**
	 * Dispatch the request
	 *
	 * This method will call the required method of the currently active
	 * controller, given by the ACTIONPARAM parameter. The method will be run in
	 * its own output buffer, preceded by a call to init() and pursued by a call
	 * to teardown(). After injecting the content type, the output is printed.
	 *
	 * @throws sly_Controller_Exception     if an unknown action is requested
	 * @throws sly_Authorisation_Exception  if no access is allowed
	 */
	public function dispatch() {
		if (!method_exists($this, $this->action)) {
			throw new sly_Controller_Exception(t('unknown_action', $this->action, get_class($this)), 404);
		}

		if ($this->checkPermission() !== true) {
			throw new sly_Authorisation_Exception(t('page_not_allowed', $this->action, get_class($this)));
		}

		$method = $this->action;

		ob_start();
		$this->init();
		$this->$method();
		$this->teardown();
		$output = ob_get_clean();

		$this->injectContentType();
		print $output;
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
		global $REX;

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
	 */
	protected function init() {
	}

	/**
	 * Teardown callback
	 *
	 * This method will be executed right after the real action method is
	 * executed. Use this to cleanup after your work is done.
	 */
	protected function teardown() {
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
