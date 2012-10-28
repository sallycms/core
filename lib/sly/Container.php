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
 * @ingroup core
 */
class sly_Container implements ArrayAccess, Countable {
	private $values;

	/**
	 * Constructor
	 *
	 * @param array $values  initial values
	 */
	public function __construct(array $values = array()) {
		$this->values = array_merge(array(
			'sly-current-article-id' => null,
			'sly-current-lang-id'    => null,

			// core objects
			'sly-config'              => array($this, 'buildConfig'),
			'sly-dispatcher'          => array($this, 'buildDispatcher'),
			'sly-error-handler'       => array($this, 'buildErrorHandler'),
			'sly-registry-temp'       => array($this, 'buildTempRegistry'),
			'sly-registry-persistent' => array($this, 'buildPersistentRegistry'),
			'sly-request'             => array($this, 'buildRequest'),
			'sly-response'            => array($this, 'buildResponse'),
			'sly-session'             => array($this, 'buildSession'),
			'sly-persistence'         => array($this, 'buildPersistence'),
			'sly-cache'               => array($this, 'buildCache'),
			'sly-flash-message'       => array($this, 'buildFlashMessage'),

			// services
			'sly-service-addon'          => array($this, 'buildAddOnService'),
			'sly-service-addon-manager'  => array($this, 'buildAddOnManagerService'),
			'sly-service-article'        => array($this, 'buildArticleService'),
			'sly-service-articleslice'   => array($this, 'buildArticleSliceService'),
			'sly-service-articletype'    => array($this, 'buildArticleTypeService'),
			'sly-service-asset'          => array($this, 'buildAssetService'),
			'sly-service-category'       => array($this, 'buildCategoryService'),
			'sly-service-language'       => array($this, 'buildLanguageService'),
			'sly-service-mediacategory'  => array($this, 'buildMediaCategoryService'),
			'sly-service-medium'         => array($this, 'buildMediumService'),
			'sly-service-module'         => array($this, 'buildModuleService'),
			'sly-service-package-addon'  => array($this, 'buildAddOnPackageService'),
			'sly-service-package-vendor' => array($this, 'buildVendorPackageService'),
			'sly-service-slice'          => array($this, 'buildSliceService'),
			'sly-service-template'       => array($this, 'buildTemplateService'),
			'sly-service-user'           => array($this, 'buildUserService')
		), $values);
	}

	/**
	 * Returns the number of elements
	 *
	 * @return int
	 */
	public function count() {
		return count($this->values);
	}

	/**
	 * @param  string $id
	 * @param  mixed  $value
	 * @return sly_Container  reference to self
	 */
	public function set($id, $value) {
		$this->values[$id] = $value;
		return $this;
	}

	/**
	 * @param  string $id
	 * @return boolean
	 */
	public function has($id) {
		return array_key_exists($id, $this->values);
	}

	/**
	 * @param  string $id
	 * @return sly_Container  reference to self
	 */
	public function remove($id) {
		unset($this->values[$id]);
		return $this;
	}

	/**
	 * @throws InvalidArgumentException if the identifier is not defined
	 * @param  string $id
	 * @return mixed
	 */
	public function get($id) {
		if (!array_key_exists($id, $this->values)) {
			throw new InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
		}

		$closures = class_exists('Closure', false);
		$value    = $this->values[$id];

		// PHP 5.3+
		if ($closures && $value instanceof Closure) {
			return $value($this);
		}

		if (is_callable($value)) {
			return call_user_func_array($value, array($this));
		}

		return $value;
	}

	/**
	 * @return int|null
	 */
	public function getCurrentArticleID() {
		return $this->get('sly-current-article-id');
	}

	/**
	 * @return int|null
	 */
	public function getCurrentLanguageID() {
		return $this->get('sly-current-lang-id');
	}

	/**
	 * @return sly_Configuration
	 */
	public function getConfig() {
		return $this->get('sly-config');
	}

	/**
	 * @return sly_Event_IDispatcher
	 */
	public function getDispatcher() {
		return $this->get('sly-dispatcher');
	}

	/**
	 * @return sly_Layout
	 */
	public function getLayout() {
		return $this->get('sly-layout');
	}

	/**
	 * @return sly_I18N
	 */
	public function getI18N() {
		return $this->get('sly-i18n');
	}

	/**
	 * @return sly_Registry_Temp
	 */
	public function getTempRegistry() {
		return $this->get('sly-registry-temp');
	}

	/**
	 * @return sly_Registry_Persistent
	 */
	public function getPersistentRegistry() {
		return $this->get('sly-registry-persistent');
	}

	/**
	 * @return sly_ErrorHandler_Interface
	 */
	public function getErrorHandler() {
		return $this->get('sly-error-handler');
	}

	/**
	 * @return sly_Request
	 */
	public function getRequest() {
		return $this->get('sly-request');
	}

	/**
	 * @return sly_Response
	 */
	public function getResponse() {
		return $this->get('sly-response');
	}

	/**
	 * @return sly_Session
	 */
	public function getSession() {
		return $this->get('sly-session');
	}

	/**
	 * @return sly_DB_PDO_Persistence
	 */
	public function getPersistence() {
		return $this->get('sly-persistence');
	}

	/**
	 * @return BabelCache_Interface
	 */
	public function getCache() {
		return $this->get('sly-cache');
	}

	/**
	 * @return sly_App_Interface
	 */
	public function getApplication() {
		return $this->get('sly-app');
	}

	/**
	 * @return string
	 */
	public function getApplicationName() {
		return $this->get('sly-app-name');
	}

	/**
	 * get addOn service
	 *
	 * @return sly_Service_AddOn
	 */
	public function getAddOnService() {
		return $this->get('sly-service-addon');
	}

	/**
	 * get addOn manager service
	 *
	 * @return sly_Service_AddOnManager
	 */
	public function getAddOnManagerService() {
		return $this->get('sly-service-addon-manager');
	}

	/**
	 * get article service
	 *
	 * @return sly_Service_Article
	 */
	public function getArticleService() {
		return $this->get('sly-service-article');
	}

	/**
	 * get article slice service
	 *
	 * @return sly_Service_ArticleSlice
	 */
	public function getArticleSliceService() {
		return $this->get('sly-service-articleslice');
	}

	/**
	 * get article type service
	 *
	 * @return sly_Service_ArticleType
	 */
	public function getArticleTypeService() {
		return $this->get('sly-service-articletype');
	}

	/**
	 * get asset service
	 *
	 * @return sly_Service_Asset
	 */
	public function getAssetService() {
		return $this->get('sly-service-asset');
	}

	/**
	 * get category service
	 *
	 * @return sly_Service_Category
	 */
	public function getCategoryService() {
		return $this->get('sly-service-category');
	}

	/**
	 * get language service
	 *
	 * @return sly_Service_Language
	 */
	public function getLanguageService() {
		return $this->get('sly-service-language');
	}

	/**
	 * get media category service
	 *
	 * @return sly_Service_MediaCategory
	 */
	public function getMediaCategoryService() {
		return $this->get('sly-service-mediacategory');
	}

	/**
	 * get medium service
	 *
	 * @return sly_Service_Medium
	 */
	public function getMediumService() {
		return $this->get('sly-service-medium');
	}

	/**
	 * get module service
	 *
	 * @return sly_Service_Module
	 */
	public function getModuleService() {
		return $this->get('sly-service-module');
	}

	/**
	 * get addOn package service
	 *
	 * @return sly_Service_AddOnPackage
	 */
	public function getAddOnPackageService() {
		return $this->get('sly-service-package-addon');
	}

	/**
	 * get vendor package service
	 *
	 * @return sly_Service_VendorPackage
	 */
	public function getVendorPackageService() {
		return $this->get('sly-service-package-vendor');
	}

	/**
	 * get slice service
	 *
	 * @return sly_Service_Slice
	 */
	public function getSliceService() {
		return $this->get('sly-service-slice');
	}

	/**
	 * get template service
	 *
	 * @return sly_Service_Template
	 */
	public function getTemplateService() {
		return $this->get('sly-service-template');
	}

	/**
	 * get user service
	 *
	 * @return sly_Service_User
	 */
	public function getUserService() {
		return $this->get('sly-service-user');
	}

	/**
	 * @return sly_Util_FlashMessage
	 */
	public function getFlashMessage() {
		return $this->get('sly-flash-message');
	}

	/**
	 * get generic model service
	 *
	 * @return sly_Service_Base
	 */
	public function getService($modelName) {
		$id = 'sly-service-model-'.$modelName;

		if (!$this->has($id)) {
			$className = 'sly_Service_'.$modelName;

			if (!class_exists($className)) {
				throw new sly_Exception(t('service_not_found', $modelName));
			}

			$service = new $className();
			$this->set($id, $service);
		}

		return $this->get($id);
	}

	/*          setters for objects that are commonly set          */

	/**
	 * @param  int $articleID  the new current article
	 * @return sly_Container   reference to self
	 */
	public function setCurrentArticleId($articleID) {
		return $this->set('sly-current-article-id', (int) $articleID);
	}

	/**
	 * @param  int $langID    the new current language
	 * @return sly_Container  reference to self
	 */
	public function setCurrentLanguageId($langID) {
		return $this->set('sly-current-lang-id', (int) $langID);
	}

	/**
	 * @param  sly_ErrorHandler_Interface $handler  the new error handler
	 * @return sly_Container                        reference to self
	 */
	public function setErrorHandler(sly_ErrorHandler_Interface $handler) {
		return $this->set('sly-error-handler', $handler);
	}

	/**
	 * @param  sly_I18N $i18n  the new translation service
	 * @return sly_Container   reference to self
	 */
	public function setI18N(sly_I18N $i18n) {
		return $this->set('sly-i18n', $i18n);
	}

	/**
	 * @param  sly_Layout $layout  the new Layout
	 * @return sly_Container       reference to self
	 */
	public function setLayout(sly_Layout $layout) {
		return $this->set('sly-layout', $layout);
	}

	/**
	 * @param  sly_Response $response  the new response
	 * @return sly_Container           reference to self
	 */
	public function setResponse(sly_Response $response) {
		return $this->set('sly-response', $response);
	}

	/*          arrayaccess interface          */

	/**
	 * @param string $id
	 * @param mixed  $value
	 */
	public function offsetSet($id, $value) {
		return $this->set($id, $value);
	}

	/**
	 * @param  string $id
	 * @return boolean
	 */
	public function offsetExists($id) {
		return $this->has($id);
	}

	/**
	 * @param string $id
	 */
	public function offsetUnset($id) {
		return $this->remove($id);
	}

	/**
	 * @throws InvalidArgumentException if the identifier is not defined
	 * @param  string $id
	 * @return mixed
	 */
	public function offsetGet($id) {
		return $this->get($id);
	}

	/*          factory methods          */

	/**
	 * @return sly_Configuration
	 */
	protected function buildConfig() {
		return $this['sly-config'] = new sly_Configuration($this->getService('File_YAML'));
	}

	/**
	 * @return sly_Event_IDispatcher
	 */
	protected function buildDispatcher() {
		return $this['sly-dispatcher'] = new sly_Event_Dispatcher();
	}

	/**
	 * @return sly_Registry_Temp
	 */
	protected function buildTempRegistry() {
		return $this['sly-registry-temp'] = sly_Registry_Temp::getInstance();
	}

	/**
	 * @return sly_Registry_Persistent
	 */
	protected function buildPersistentRegistry() {
		return $this['sly-registry-persistent'] = sly_Registry_Persistent::getInstance();
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_ErrorHandler_Interface
	 */
	protected function buildErrorHandler(sly_Container $container) {
		$config  = $container['sly-config'];
		$devMode = $config->get('DEVELOPER_MODE', false);

		return $this['sly-error-handler'] = $devMode ? new sly_ErrorHandler_Development() : new sly_ErrorHandler_Production();
	}

	/**
	 * @return sly_Request
	 */
	protected function buildRequest() {
		return $this->values['sly-request'] = sly_Request::createFromGlobals();
	}

	/**
	 * @return sly_Response
	 */
	protected function buildResponse() {
		$response = new sly_Response('', 200);
		$response->setContentType('text/html', 'UTF-8');

		return $this->values['sly-response'] = $response;
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Session
	 */
	protected function buildSession(sly_Container $container) {
		return $this['sly-session'] = new sly_Session($container->get('sly-config')->get('INSTNAME'));
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_DB_PDO_Persistence
	 */
	protected function buildPersistence(sly_Container $container) {
		$config = $container['sly-config']->get('DATABASE');

		// TODO: to support the iterator inside the persistence, we need to create
		// a fresh instance for every access. We should refactor the database access
		// to allow for a single persistence instance.
		return new sly_DB_PDO_Persistence($config['DRIVER'], $config['HOST'], $config['LOGIN'], $config['PASSWORD'], $config['NAME'], $config['TABLE_PREFIX']);
	}

	/**
	 * @return BabelCache_Interface
	 */
	protected function buildCache() {
		return $this['sly-cache'] = sly_Cache::factory();
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Util_FlashMessage
	 */
	protected function buildFlashMessage(sly_Container $container) {
		sly_Util_Session::start();

		$session = $container->get('sly-session');
		$msg     = sly_Util_FlashMessage::readFromSession('sally', $session);

		$msg->removeFromSession($session);
		$msg->setAutoStore(true);

		return $this->values['sly-flash-message'] = $msg;
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_AddOn
	 */
	protected function buildAddOnService(sly_Container $container) {
		$cache      = $container['sly-cache'];
		$config     = $container['sly-config'];
		$adnService = $container['sly-service-package-addon'];
		$vndService = $container['sly-service-package-vendor'];
		$service    = new sly_Service_AddOn($config, $cache, $adnService, SLY_DYNFOLDER);

		$service->setVendorPackageService($vndService);

		return $this->values['sly-service-addon'] = $service;
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_AddOn_Manager
	 */
	protected function buildAddOnManagerService(sly_Container $container) {
		$config     = $container['sly-config'];
		$dispatcher = $container['sly-dispatcher'];
		$cache      = $container['sly-cache'];
		$service    = $container['sly-service-addon'];

		return $this->values['sly-service-addon-manager'] = new sly_Service_AddOn_Manager($config, $dispatcher, $cache, $service);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_Article
	 */
	protected function buildArticleService(sly_Container $container) {
		$persistence = $container['sly-persistence'];
		$cache       = $container['sly-cache'];
		$dispatcher  = $container['sly-dispatcher'];
		$languages   = $container['sly-service-language'];
		$slices      = $container['sly-service-slice'];
		$articles    = $container['sly-service-articleslice'];
		$templates   = $container['sly-service-template'];
		$service     = new sly_Service_Article($persistence, $cache, $dispatcher, $languages, $slices, $articles, $templates);

		// make sure the circular dependency does not make the app die with an endless loop
		$this->values['sly-service-article'] = $service;

		$service->setArticleService($service);
		$service->setCategoryService($container['sly-service-category']);

		return $service;
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_ArticleSlice
	 */
	protected function buildArticleSliceService(sly_Container $container) {
		$persistence = $container['sly-persistence'];
		$dispatcher  = $container['sly-dispatcher'];
		$slices      = $container['sly-service-slice'];
		$templates   = $container['sly-service-template'];

		return $this->values['sly-service-articleslice'] = new sly_Service_ArticleSlice($persistence, $dispatcher, $slices, $templates);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_ArticleType
	 */
	protected function buildArticleTypeService(sly_Container $container) {
		$config    = $container['sly-config'];
		$modules   = $container['sly-service-module'];
		$templates = $container['sly-service-template'];

		return $this->values['sly-service-articletype'] = new sly_Service_ArticleType($config, $modules, $templates);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_Asset
	 */
	protected function buildAssetService(sly_Container $container) {
		return $this->values['sly-service-asset'] = new sly_Service_Asset($container['sly-config'], $container['sly-dispatcher']);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_Category
	 */
	protected function buildCategoryService(sly_Container $container) {
		$persistence = $container['sly-persistence'];
		$cache       = $container['sly-cache'];
		$dispatcher  = $container['sly-dispatcher'];
		$languages   = $container['sly-service-language'];
		$service     = new sly_Service_Category($persistence, $cache, $dispatcher, $languages);

		// make sure the circular dependency does not make the app die with an endless loop
		$this->values['sly-service-category'] = $service;

		$service->setArticleService($container['sly-service-article']);
		$service->setCategoryService($service);

		return $service;
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_Language
	 */
	protected function buildLanguageService(sly_Container $container) {
		$persistence = $container['sly-persistence'];
		$cache       = $container['sly-cache'];
		$dispatcher  = $container['sly-dispatcher'];

		return $this->values['sly-service-language'] = new sly_Service_Language($persistence, $cache, $dispatcher);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_MediaCategory
	 */
	protected function buildMediaCategoryService(sly_Container $container) {
		$persistence = $container['sly-persistence'];
		$cache       = $container['sly-cache'];
		$dispatcher  = $container['sly-dispatcher'];
		$service     = new sly_Service_MediaCategory($persistence, $cache, $dispatcher);

		// make sure the circular dependency does not make the app die with an endless loop
		$this->values['sly-service-mediacategory'] = $service;
		$service->setMediumService($container['sly-service-medium']);

		return $service;
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_Medium
	 */
	protected function buildMediumService(sly_Container $container) {
		$persistence = $container['sly-persistence'];
		$cache       = $container['sly-cache'];
		$dispatcher  = $container['sly-dispatcher'];
		$categories  = $container['sly-service-mediacategory'];

		return $this->values['sly-service-medium'] = new sly_Service_Medium($persistence, $cache, $dispatcher, $categories);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_Module
	 */
	protected function buildModuleService(sly_Container $container) {
		return $this->values['sly-service-module'] = new sly_Service_Module($container['sly-config'], $container['sly-dispatcher']);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_Package_AddOn
	 */
	protected function buildAddOnPackageService(sly_Container $container) {
		return $this->values['sly-service-package-addon'] = new sly_Service_Package(SLY_ADDONFOLDER, $container['sly-cache']);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_Package_Vendor
	 */
	protected function buildVendorPackageService(sly_Container $container) {
		return $this->values['sly-service-package-vendor'] = new sly_Service_Package(SLY_VENDORFOLDER, $container['sly-cache']);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_Slice
	 */
	protected function buildSliceService(sly_Container $container) {
		return $this->values['sly-service-slice'] = new sly_Service_Slice($container['sly-persistence']);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_Template
	 */
	protected function buildTemplateService(sly_Container $container) {
		return $this->values['sly-service-template'] = new sly_Service_Template($container['sly-config'], $container['sly-dispatcher']);
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Service_User
	 */
	protected function buildUserService(sly_Container $container) {
		$cache       = $container['sly-cache'];
		$config      = $container['sly-config'];
		$dispatcher  = $container['sly-dispatcher'];
		$persistence = $container['sly-persistence'];

		return $this->values['sly-service-user'] = new sly_Service_User($persistence, $cache, $dispatcher, $config);
	}
}
