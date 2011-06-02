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
 * @ingroup controller
 */
abstract class sly_Controller_Base {
	const PAGEPARAM    = 'page';
	const SUBPAGEPARAM = 'subpage';
	const ACTIONPARAM  = 'func';

	protected $content_type = null;
	protected $charset      = null;
	protected $action;

	protected function __construct() {
		$this->action = self::getActionParam('index');
	}

	protected function setContentType($type) {
		$this->content_type = $type;
	}

	protected function getContentType() {
		return $this->content_type;
	}

	protected function setCharset($charset) {
		$this->charset = $charset;
	}

	protected function getCharset() {
		return $this->charset;
	}

	protected function computeContentType() {
		if (!empty ($this->content_type)) {
			return $this->content_type.($this->charset ?  ('; charset='.$this->charset) : '');
		}

		return null;
	}

	protected function injectContentType() {
		$content_type = $this->computeContentType();

		if ($content_type) {
			header('Content-Type: '.$content_type);
			$layout = sly_Core::getLayout('Sally');
			$layout->addHttpMeta('Content-Type', $content_type);
		}
	}

	public static function getPageParam($default = '')    { return sly_request(self::PAGEPARAM, 'string', $default);    }
	public static function getSubpageParam($default = '') { return sly_request(self::SUBPAGEPARAM, 'string', $default); }
	public static function getActionParam($default = '')  { return sly_request(self::ACTIONPARAM, 'string', $default);  }

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

	public function dispatch() {
		if (!method_exists($this, $this->action)) {
			throw new sly_Controller_Exception('HTTP 404: Methode '. $this->action .' in '. get_class($this) .' nicht gefunden!');
		}

		if ($this->checkPermission() !== true){
			throw new sly_Authorisation_Exception('HTTP 403: Zugriff auf '. $this->action .' in '. get_class($this) .' nicht gestattet!');
		}

		$method = $this->action;

		ob_start();
		$this->init();
		$retval = $this->$method();
		$this->teardown();
		$output = ob_get_clean();

		$this->injectContentType();
		print $output;
	}

	abstract protected function getViewFolder();

	protected function render($filename, $params = array()) {
		global $REX, $I18N;

		// Die Parameternamen $params und $filename sind zu kurz, als dass
		// man sie zuverlässig nutzen könnte. Wenn $params durch extract()
		// während der Ausführung überschrieben wird kann das unvorhersehbare
		// Folgen haben. Darum wird $filename und $params in kryptische
		// Variablen verpackt und aus dem Kontext entfernt.
		$filenameHtuG50hNCdikAvf7CZ1F = $filename;
		$paramsHtuG50hNCdikAvf7CZ1F = $params;
		unset($filename);
		unset($params);
		extract($paramsHtuG50hNCdikAvf7CZ1F);

		ob_start();
		include $this->getViewFolder().$filenameHtuG50hNCdikAvf7CZ1F;
		return ob_get_clean();
	}

	protected function init() {
	}

	protected function teardown() {
	}

	protected abstract function index();
	protected abstract function checkPermission();
}
