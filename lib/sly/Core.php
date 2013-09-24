<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
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
	private static $instance;  ///< sly_Core
	private $container;        ///< sly_Container

	// Use the following constants when you don't have access to the real
	// config values (i.e. when in setup mode). They should map the values
	// in sallyStatic.yml.

	const DEFAULT_FILEPERM = 0664; ///< int
	const DEFAULT_DIRPERM  = 0777; ///< int

	private function __construct() {
		// do nothing
	}

	/**
	 * Boot-up the Sally core system
	 *
	 * This will set the global Sally constants (like SLY_COREFOLDER) and init
	 * the system configuration. Be sure to not call this method twice with the
	 * same configuration. And if you call it more than once, make sure you know
	 * what you're doing.
	 *
	 * @param  mixed         $classLoader  ClassLoader instance from Composer
	 * @param  string        $environment  system environment, use null to use the locally configured value
	 * @param  string        $appName      application name, e.g. 'frontend'
	 * @param  string        $appBaseUrl   application base URL, e.g. 'backend' or '' for the frontend
	 * @param  sly_Container $container    DI container to use (if none given, an empty one is created)
	 * @return sly_Container               the used container
	 */
	public static function boot($classLoader, $environment, $appName, $appBaseUrl, sly_Container $container = null) {
		$startTime = microtime(true);

		if (!$container) {
			$container = new sly_Container();
		}

		// we're using UTF-8 everywhere
		if (!function_exists('mb_internal_encoding')) {
			print 'SallyCMS requires the mbstring extension to work.';
			exit(1);
		}

		mb_internal_encoding('UTF-8');

		// define that the path to the core is here
		if (!defined('SLY_COREFOLDER'))    define('SLY_COREFOLDER',    dirname(dirname(dirname(__FILE__))));

		// define constants for system wide important paths if they are not set already
		if (!defined('SLY_BASE'))          define('SLY_BASE',          realpath(SLY_COREFOLDER.'/../../'));
		if (!defined('SLY_SALLYFOLDER'))   define('SLY_SALLYFOLDER',   SLY_BASE.DIRECTORY_SEPARATOR.'sally');
		if (!defined('SLY_DEVELOPFOLDER')) define('SLY_DEVELOPFOLDER', SLY_BASE.DIRECTORY_SEPARATOR.'develop');
		if (!defined('SLY_VENDORFOLDER'))  define('SLY_VENDORFOLDER',  SLY_SALLYFOLDER.DIRECTORY_SEPARATOR.'vendor');
		if (!defined('SLY_DATAFOLDER'))    define('SLY_DATAFOLDER',    SLY_BASE.DIRECTORY_SEPARATOR.'data');
		if (!defined('SLY_DYNFOLDER'))     define('SLY_DYNFOLDER',     SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'dyn');
		if (!defined('SLY_MEDIAFOLDER'))   define('SLY_MEDIAFOLDER',   SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'mediapool');
		if (!defined('SLY_CONFIGFOLDER'))  define('SLY_CONFIGFOLDER',  SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'config');
		if (!defined('SLY_ADDONFOLDER'))   define('SLY_ADDONFOLDER',   SLY_SALLYFOLDER.DIRECTORY_SEPARATOR.'addons');

		// define these PHP 5.3 constants here so that they can be used in YAML files
		// (if someone really decides to put PHP code in their config files).
		if (!defined('E_DEPRECATED'))      define('E_DEPRECATED',      8192);
		if (!defined('E_USER_DEPRECATED')) define('E_USER_DEPRECATED', 16384);

		// init container
		$container->setConfigDir(SLY_CONFIGFOLDER);
		$container->set('sly-classloader', $classLoader);
		$container->set('sly-start-time', $startTime);
		$container->setApplicationInfo($appName, $appBaseUrl);

		self::setContainer($container);

		// load core config (be extra careful because this is the first attempt to write
		// to the filesystem on new installations)
		try {
			$config = $container->getConfig();
			$config->loadStatic(SLY_COREFOLDER.'/config/sallyStatic.yml');
			$config->loadLocalConfig();
			$config->loadProjectConfig();
			$config->loadDevelopConfig();
		}
		catch (sly_Util_DirectoryException $e) {
			$dir = sly_html($e->getDirectory());

			header('Content-Type: text/html; charset=UTF-8');
			die(
				'Could not create data directory in <strong>'.$dir.'</strong>.<br />'.
				'Please check your filesystem permissions and ensure that PHP is allowed<br />'.
				'to write in <strong>'.SLY_DATAFOLDER.'</strong>. In most cases this can<br />'.
				'be fixed by creating the directory via FTP and chmodding it to <strong>0777</strong>.'
			);
		}
		catch (Exception $e) {
			header('Content-Type: text/plain; charset=UTF-8');
			die('Could not load core configuration: '.$e->getMessage());
		}

		// get and inject the current system environment
		if ($environment === null) {
			$environment = $config->get('environment', 'dev');
		}

		$container->set('sly-environment', $environment);

		// now that we now about the environment, we can toggle the config caching
		$config->setCachingEnabled($environment === 'prod');

		return $container;
	}

	/**
	 * Get the single core instance
	 *
	 * @return sly_Core  the singleton
	 */
	public static function getInstance() {
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Get the DI container
	 *
	 * @return sly_Container  the DI container instance
	 */
	public static function getContainer() {
		$instance = self::getInstance();

		if (!$instance->container) {
			throw new LogicException('The DI container has not yet been set.');
		}

		return $instance->container;
	}

	/**
	 * Set the DI container
	 *
	 * @param sly_Container $container  the new DI container instance
	 */
	public static function setContainer(sly_Container $container) {
		self::getInstance()->container = $container;
	}

	/**
	 * Get the global caching instance
	 *
	 * @return BabelCache_Interface  caching instance
	 */
	public static function cache() {
		return self::getContainer()->getCache();
	}

	/**
	 * @param sly_App_Interface $app  the current system app
	 */
	public static function setCurrentApp(sly_App_Interface $app) {
		self::getContainer()->set('sly-app', $app);
	}

	/**
	 * @return sly_App_Interface
	 */
	public static function getCurrentApp() {
		return self::getContainer()->getApplication();
	}

	/**
	 * @param int $clangId  the new clang or null to reset
	 */
	public static function setCurrentClang($clangId) {
		$clangId = $clangId === null ? null : (int) $clangId;
		self::getContainer()->set('sly-current-lang-id', $clangId);
	}

	/**
	 * @param int $articleId  the new article ID or null to reset
	 */
	public static function setCurrentArticleId($articleId) {
		$articleId = $articleId === null ? null : (int) $articleId;
		self::getContainer()->set('sly-current-article-id', $articleId);
	}

	/**
	 * Returns the current language ID
	 *
	 * Checks the request param 'clang' and returns a validated value.
	 *
	 * @return int  the current clang
	 */
	public static function getCurrentClang() {
		return self::getContainer()->getCurrentLanguageID();
	}

	/**
	 * Returns the current language
	 *
	 * @return sly_Model_Language  the current language
	 */
	public static function getCurrentLanguage() {
		$clang = self::getCurrentClang();
		return $clang > 0 ? self::getContainer()->getLanguageService()->findById($clang) : null;
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
		return self::getContainer()->getCurrentArticleID();
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
	 * @return sly_Configuration  the system configuration
	 */
	public static function config() {
		return self::getContainer()->getConfig();
	}

	/**
	 * @return sly_Event_IDispatcher  the event dispatcher
	 */
	public static function dispatcher() {
		return self::getContainer()->getDispatcher();
	}

	/**
	 * @param  sly_Event_IDispatcher $dispatcher  the new dispatcher
	 * @return sly_Event_IDispatcher              the previous dispatcher
	 */
	public static function setDispatcher(sly_Event_IDispatcher $dispatcher) {
		$container = self::getContainer();
		$previous  = $container->getDispatcher();

		$container->set('sly-dispatcher', $dispatcher);

		return $previous;
	}

	/**
	 * Get the current layout instance
	 *
	 * @return sly_Layout  the layout instance
	 */
	public static function getLayout() {
		try {
			return self::getContainer()->getLayout();
		}
		catch (Exception $e) {
			throw new sly_Exception(t('layout_has_not_been_set'));
		}
	}

	/**
	 * Set the current layout instance
	 *
	 * @param sly_Layout $layout  the layout instance
	 */
	public static function setLayout(sly_Layout $layout) {
		self::getContainer()->set('sly-layout', $layout);
	}

	/**
	 * @return boolean  true if backend, else false
	 */
	public static function isBackend() {
		return self::getCurrentApp()->isBackend();
	}

	/**
	 * @return boolean  true if developer mode, else false
	 */
	public static function isDeveloperMode() {
		return self::getContainer()->getEnvironment() !== 'prod';
	}

	/**
	 * @return string  the project name
	 */
	public static function getProjectName() {
		return self::config()->get('PROJECTNAME');
	}

	/**
	 * @return int  the project homepage ID (start article)
	 */
	public static function getSiteStartArticleId() {
		return (int) self::config()->get('START_ARTICLE_ID');
	}

	/**
	 * @return int  the not-found article's ID
	 */
	public static function getNotFoundArticleId() {
		return (int) self::config()->get('NOTFOUND_ARTICLE_ID');
	}

	/**
	 * @return int  the default clang ID
	 */
	public static function getDefaultClangId() {
		return (int) self::config()->get('DEFAULT_CLANG_ID');
	}

	/**
	 * @return string  the default (backend) locale
	 */
	public static function getDefaultLocale() {
		return self::config()->get('DEFAULT_LOCALE');
	}

	/**
	 * @return string  the default article type
	 */
	public static function getDefaultArticleType() {
		return self::config()->get('DEFAULT_ARTICLE_TYPE');
	}

	/**
	 * @return string  the class name of the global caching strategy
	 */
	public static function getCachingStrategy() {
		return self::config()->get('CACHING_STRATEGY');
	}

	/**
	 * @return string  the timezone's name
	 */
	public static function getTimezone() {
		return self::config()->get('TIMEZONE');
	}

	/**
	 * @return int  permissions for files
	 */
	public static function getFilePerm($default = self::DEFAULT_FILEPERM) {
		return (int) self::config()->get('FILEPERM', $default);
	}

	/**
	 * @return int  permissions for directory
	 */
	public static function getDirPerm($default = self::DEFAULT_DIRPERM) {
		return (int) self::config()->get('DIRPERM', $default);
	}

	/**
	 * @return sring  the database table prefix
	 */
	public static function getTablePrefix() {
		return self::config()->get('DATABASE/TABLE_PREFIX');
	}

	/**
	 * @return sly_I18N  the global i18n instance
	 */
	public static function getI18N() {
		return self::getContainer()->getI18N();
	}

	/**
	 * @param sly_I18N $i18n  the new translation object
	 */
	public static function setI18N(sly_I18N $i18n) {
		self::getContainer()->set('sly-i18n', $i18n);
	}

	/**
	 * Get persistent registry instance
	 *
	 * @return sly_Registry_Persistent  the registry singleton
	 */
	public static function getPersistentRegistry() {
		return self::getContainer()->getPersistentRegistry();
	}

	/**
	 * Get temporary registry instance
	 *
	 * @return sly_Registry_Temp  the registry singleton
	 */
	public static function getTempRegistry() {
		return self::getContainer()->getTempRegistry();
	}

	/**
	 * @param  string $pattern  the pattern (X = major version, Y = minor version, Z = minor version)
	 * @return string           the pattern with replaced version numbers
	 */
	public static function getVersion($pattern = 'X.Y.Z') {
		static $version = null;

		if ($version === null) {
			$config  = self::config();
			$version = $config->get('VERSION');
		}

		$pattern = str_replace('s', 'sly', $pattern);
		$pattern = str_replace('S', 'sally', $pattern);
		$pattern = str_replace('X', $version['MAJOR'], $pattern);
		$pattern = str_replace('Y', $version['MINOR'], $pattern);
		$pattern = str_replace('Z', $version['BUGFIX'], $pattern);

		return $pattern;
	}

	/**
	 * loads all known addOns into Sally
	 */
	public static function loadAddOns() {
		$container = self::getContainer();

		$container->getAddOnManagerService()->loadAddOns($container);
		$container->getDispatcher()->notify('SLY_ADDONS_LOADED', $container);
	}

	public static function registerListeners() {
		$listeners  = self::config()->get('LISTENERS', array());
		$dispatcher = self::dispatcher();

		foreach ($listeners as $event => $callbacks) {
			foreach ($callbacks as $callback) {
				$dispatcher->register($event, $callback);
			}
		}

		$dispatcher->notify('SLY_LISTENERS_REGISTERED');
	}

	/**
	 * @param sly_ErrorHandler $errorHandler  the new error handler instance
	 */
	public static function setErrorHandler(sly_ErrorHandler $errorHandler) {
		self::getContainer()->set('sly-error-handler', $errorHandler);
	}

	/**
	 * @return sly_ErrorHandler  the current error handler
	 */
	public static function getErrorHandler() {
		return self::getContainer()->getErrorHandler();
	}

	/**
	 * @param sly_Response $response  the new response instance
	 */
	public static function setResponse(sly_Response $response) {
		self::getContainer()->set('sly-response', $response);
	}

	/**
	 * @return sly_Response  the current response
	 */
	public static function getResponse() {
		return self::getContainer()->getResponse();
	}

	/**
	 * @param sly_Request $request  the new request instance
	 */
	public static function setRequest(sly_Request $request) {
		self::getContainer()->set('sly-request', $request);
	}

	/**
	 * @return sly_Request  the current request
	 */
	public static function getRequest() {
		return self::getContainer()->getRequest();
	}

	/**
	 * @param sly_Session $session  the new session instance
	 */
	public static function setSession(sly_Session $session) {
		self::getContainer()->set('sly-session', $session);
	}

	/**
	 * @return sly_Session  the current session
	 */
	public static function getSession() {
		return self::getContainer()->getSession();
	}

	/**
	 * Returns the flash message
	 *
	 * An existing message is removed upon first call from session, so the
	 * message will not be available on the next HTTP request. Manipulating the
	 * message object will re-store it again, however.
	 *
	 * @return sly_Util_FlashMessage  the flash message
	 */
	public static function getFlashMessage() {
		return self::getContainer()->getFlashMessage();
	}

	/**
	 * Returns the current controller
	 *
	 * @return sly_Controller_Interface  the current controller
	 */
	public static function getCurrentController() {
		return self::getCurrentApp()->getCurrentController();
	}

	/**
	 * Returns the current controller name
	 *
	 * Code using the controller name should never assume that it maps directly
	 * to the controller class. Currently, it does, but this may change in a
	 * future relase.
	 *
	 * @return string  current controller name
	 */
	public static function getCurrentControllerName() {
		return self::getCurrentApp()->getCurrentControllerName();
	}

	/**
	 * Clears the complete system cache
	 *
	 * @return string  the info messages (collected from all listeners)
	 */
	public static function clearCache() {
		clearstatcache();

		// clear loader cache
		sly_Loader::clearCache();

		$container = self::getContainer();

		// clear our own data caches
		$container->getCache()->flush('sly', true);

		// sync develop files
		$container->getTemplateService()->refresh();
		$container->getModuleService()->refresh();

		// clear asset cache
		$container->getAssetService()->clearCache();

		// refresh addOns
		$container->getAddOnManagerService()->refresh();

		// clear config cache
		$container->getConfig()->clearCache();

		self::dispatcher()->notify('SLY_CACHE_CLEARED');
	}

	public static function isSetup() {
		return self::config()->get('SETUP', true) === true;
	}
}
