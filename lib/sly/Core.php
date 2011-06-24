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
 * @ingroup core
 */
class sly_Core {
	private static $instance;
	private $cache;
	private $curClang;
	private $curArticleId;
	private $varTypes;
	private $layout;
	private $navigation;
	private $i18n;

	private function __construct() {
		$this->cache = sly_Cache::factory();
	}

	/**
	 * Gibt die Instanz des Core Objekts als Singleton zurück
	 *
	 * @return sly_Core  Die singleton Core Instanz
	 */
	public static function getInstance() {
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Gibt die angemeldete Cache-Instanz zurück.
	 *
	 * @return BabelCache_Interface  Cache-Instanz
	 */
	public static function cache() {
		return self::getInstance()->cache;
	}

	public static function setCurrentClang($clangId) {
		self::getInstance()->curClang = (int) $clangId;
	}

	/**
	 * Returns the current language ID
	 *
	 * Checks the request param 'clang' and returns a validated value.
	 *
	 * @return int  the current clang
	 */
	public static function getCurrentClang() {
		$instance = self::getInstance();

		if (!isset($instance->curClang)) {
			$instance->curClang = sly_request('clang', 'rex-clang-id', self::config()->get('START_CLANG_ID'));
		}

		return $instance->curClang;
	}

	/**
	 * Returns the current language
	 *
	 * @return sly_Model_Language  the current language
	 */
	public static function getCurrentLanguage() {
		$clang = sly_Core::getCurrentClang();
		return sly_Service_Factory::getLanguageService()->findById($clang);
	}

	public static function setCurrentArticleId($articleId) {
		self::getInstance()->curArticleId = (int) $articleId;
	}

	/**
	 * Returns the current article ID
	 *
	 * Checks the request param 'article_id' and returns a validated value. If
	 * the article was not found, the ID of the Not Found article is returned.
	 *
	 * @return int  the current article ID
	 */
	public static function getCurrentArticleId() {
		$conf     = self::config();
		$instance = self::getInstance();

		if (!isset($instance->curArticleId)) {
			$instance->curArticleId = sly_request('article_id', 'int', $conf->get('START_ARTICLE_ID'));

			if (!sly_Util_Article::exists($instance->curArticleId)) {
				$instance->curArticleId = $conf->get('NOTFOUND_ARTICLE_ID');
			}

			if (!sly_Util_Article::exists($instance->curArticleId)) {
				$instance->curArticleId = -1;
			}
		}

		return $instance->curArticleId;
	}

	/**
	 * Returns the current article
	 *
	 * @param  int $clang         null for the current clang, or else a specific clang
	 * @return sly_Model_Article  the current article
	 */
	public static function getCurrentArticle($clang = null) {
		$articleID = self::getCurrentArticleId();
		$clang     = $clang === null ? self::getCurrentClang() : (int) $clang;

		return sly_Util_Article::findById($articleID, $clang);
	}

	/**
	 * API Methode um Variabletypen zu setzen.
	 *
	 * @param string $varType  Klassenname des Variablentyps
	 */
	public static function registerVarType($varType) {
		self::getInstance()->varTypes[] = $varType;
	}

	/**
	 * Gibt immer eine Liste von Instanzen der Variablentypen zurück
	 *
	 * @return array
	 */
	public static function getVarTypes() {
		$instance = self::getInstance();

		if (!isset($instance->varTypes)) $instance->varTypes = array();

		foreach ($instance->varTypes as $idx => $obj) {
			if (is_string($obj)) { // Es hat noch kein Autoloading für diese Klasse stattgefunden
				$obj = new $obj();
				if (!($obj instanceof rex_var)) throw new sly_Exception('VarType '.$instance->varTypes[$idx].' is no inheriting class of rex_var.');
				$instance->varTypes[$idx] = $obj;
			}
		}

		return $instance->varTypes;
	}

	/**
	 * @return sly_Configuration
	 */
	public static function config() {
		return sly_Configuration::getInstance();
	}

	/**
	 * @return sly_Event_Dispatcher
	 */
	public static function dispatcher() {
		return sly_Event_Dispatcher::getInstance();
	}

	/**
	 * gibt ein sly_Layout Instanz zurück
	 *
	 * @param  string $type
	 * @return sly_Layout
	 */
	public static function getLayout($type = 'XHTML') {
		$instance = self::getInstance();

		//FIXME: layout type kann bloss einmal pro request angegeben werden,
		// reicht eigentlich auch
		// eventuell könnte man das in der config oder in index.php angeben
		if (!isset($instance->layout)) {
			$className = 'sly_Layout_'.$type;
			$instance->layout = new $className();
		}

		return $instance->layout;
	}

	public static function isBackend() {
		return defined('IS_SALLY_BACKEND') && IS_SALLY_BACKEND == true;
	}

	public static function isDeveloperMode() {
		static $var = null;
		if ($var === null) $var = (boolean) sly_Core::config()->get('DEVELOPER_MODE');
		return $var;
	}

	public static function getI18N() {
		return self::getInstance()->i18n;
	}

	public static function setI18N(sly_I18N $i18n) {
		self::getInstance()->i18n = $i18n;
	}

	/**
	 * Get persistent registry instance
	 *
	 * @return sly_Registry_Persistent
	 */
	public static function getPersistentRegistry() {
		return sly_Registry_Persistent::getInstance();
	}

	/**
	 * Get temporary registry instance
	 *
	 * @return sly_Registry_Temp
	 */
	public static function getTempRegistry() {
		return sly_Registry_Temp::getInstance();
	}

	public static function getVersion($pattern = 'X.Y.Z') {
		$config  = self::config();
		$pattern = str_replace('s', 'sly', $pattern);
		$pattern = str_replace('S', 'sally', $pattern);
		$pattern = str_replace('X', $config->get('VERSION'), $pattern);
		$pattern = str_replace('Y', $config->get('SUBVERSION'), $pattern);
		$pattern = str_replace('Z', $config->get('MINORVERSION'), $pattern);
		return $pattern;
	}

	/**
	 * Returns the backend navigation
	 *
	 * @return sly_Layout_Navigation_Sally  the navigation object used for the backend menu
	 */
	public static function getNavigation() {
		$instance = self::getInstance();

		if (!isset($instance->navigation)) {
			$instance->navigation = new sly_Layout_Navigation_Sally();
		}

		return $instance->navigation;
	}

	/**
	 * loads all known addons into Sally
	 */
	public static function loadAddons() {
		$addonService  = sly_Service_Factory::getAddOnService();
		$pluginService = sly_Service_Factory::getPluginService();

		foreach ($addonService->getRegisteredAddons() as $addonName) {
			$addonService->loadAddon($addonName);

			foreach ($pluginService->getRegisteredPlugins($addonName) as $pluginName) {
				$pluginService->loadPlugin(array($addonName, $pluginName));
			}
		}

		self::dispatcher()->notify('ADDONS_INCLUDED');
	}

	public static function registerCoreVarTypes() {
		self::registerVarType('rex_var_globals');
		self::registerVarType('rex_var_article');
		self::registerVarType('rex_var_category');
		self::registerVarType('rex_var_template');
		self::registerVarType('rex_var_value');
		self::registerVarType('rex_var_link');
		self::registerVarType('rex_var_media');
	}
}
