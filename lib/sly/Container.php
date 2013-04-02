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
class sly_Container extends Pimple implements Countable {
	/**
	 * Constructor
	 *
	 * @param array $values  initial values
	 */
	public function __construct(array $values = array()) {
		$this['sly-current-article-id'] = null;
		$this['sly-current-lang-id']    = null;

		//////////////////////////////////////////////////////////////////////////
		// needed variables

		$this['sly-classloader'] = function() {
			throw new sly_Exception('You have to set the value for "sly-classloader" first!');
		};

		$this['sly-environment'] = function() {
			throw new sly_Exception('You have to set the value for "sly-environment" first!');
		};

		//////////////////////////////////////////////////////////////////////////
		// core objects

		$this['sly-config'] = $this->share(function($container) {
			return new sly_Configuration();
		});

		$this['sly-config-reader'] = $this->share(function($container) {
			return new sly_Configuration_DatabaseImpl();
		});

		$this['sly-config-writer'] = $this->share(function($container) {
			return $container['sly-config-reader'];
		});

		$this['sly-dispatcher'] = $this->share(function($container) {
			return new sly_Event_Dispatcher();
		});

		$this['sly-error-handler'] = $this->share(function($container) {
			$devMode = $container['sly-environment'] !== 'prod';

			return $devMode ? new sly_ErrorHandler_Development() : new sly_ErrorHandler_Production();
		});

		$this['sly-registry-temp'] = $this->share(function($container) {
			return sly_Registry_Temp::getInstance();
		});

		$this['sly-registry-persistent'] = $this->share(function($container) {
			return sly_Registry_Persistent::getInstance();
		});

		$this['sly-request'] = $this->share(function($container) {
			return sly_Request::createFromGlobals();
		});

		$this['sly-response'] = $this->share(function($container) {
			$response = new sly_Response('', 200);
			$response->setContentType('text/html', 'UTF-8');

			return $response;
		});

		$this['sly-session'] = $this->share(function($container) {
			return new sly_Session($container['sly-config']->get('instname'));
		});

		$this['sly-persistence'] = function($container) {
			$config = $container['sly-config']->get('database');

			// TODO: to support the iterator inside the persistence, we need to create
			// a fresh instance for every access. We should refactor the database access
			// to allow for a single persistence instance.
			return new sly_DB_PDO_Persistence($config['driver'], $config['host'], $config['login'], $config['password'], $config['name'], $config['table_prefix']);
		};

		$this['sly-cache'] = $this->share(function($container) {
			$config   = $container['sly-config'];
			$strategy = $config->get('caching_strategy');
			$fallback = $config->get('fallback_caching_strategy', 'BabelCache_Blackhole');

			return sly_Cache::factory($strategy, $fallback);
		});

		$this['sly-flash-message'] = $this->share(function($container) {
			sly_Util_Session::start();

			$session = $container['sly-session'];
			$msg     = sly_Util_FlashMessage::readFromSession('sally', $session);

			$msg->removeFromSession($session);
			$msg->setAutoStore(true);

			return $msg;
		});

		//////////////////////////////////////////////////////////////////////////
		// services

		$this['sly-service-addon'] = $this->share(function($container) {
			$cache      = $container['sly-cache'];
			$config     = $container['sly-config'];
			$adnService = $container['sly-service-package-addon'];
			$vndService = $container['sly-service-package-vendor'];
			$service    = new sly_Service_AddOn($config, $cache, $adnService, SLY_DYNFOLDER);

			$service->setVendorPackageService($vndService);

			return $service;
		});

		$this['sly-service-addon-manager'] = $this->share(function($container) {
			$config     = $container['sly-config'];
			$dispatcher = $container['sly-dispatcher'];
			$cache      = $container['sly-cache'];
			$service    = $container['sly-service-addon'];

			return $this[$id] = new sly_Service_AddOn_Manager($config, $dispatcher, $cache, $service);
		});

		$this['sly-service-article'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];
			$slices      = $container['sly-service-slice'];
			$articles    = $container['sly-service-articleslice'];
			$templates   = $container['sly-service-template'];
			$service     = new sly_Service_Article($persistence, $slices, $articles, $templates);

			// make sure the circular dependency does not make the app die with an endless loop
			$this['sly-service-article'] = $service;

			return $service;
		});

		$this['sly-service-deletedarticle'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];
			$service     = new sly_Service_DeletedArticle($persistence);

			$this['sly-service-deletedarticle'] = $service;

			return $service;
		});

		$this['sly-service-articleslice'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];
			$dispatcher  = $container['sly-dispatcher'];
			$slices      = $container['sly-service-slice'];
			$templates   = $container['sly-service-template'];

			$service = new sly_Service_ArticleSlice($persistence, $dispatcher, $slices, $templates);

			$this['sly-service-articleslice'] = $service;
			$service->setArticleService($container['sly-service-article']);

			return $service;
		});

		$this['sly-service-articletype'] = $this->share(function($container) {
			$config    = $container['sly-config'];
			$modules   = $container['sly-service-module'];
			$templates = $container['sly-service-template'];

			return new sly_Service_ArticleType($config, $modules, $templates);
		});

		$this['sly-service-asset'] = $this->share(function($container) {
			return new sly_Service_Asset($container['sly-config'], $container['sly-dispatcher']);
		});

		$this['sly-service-category'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];
			$service     = new sly_Service_Category($persistence);

			$this['sly-service-category'] = $service;

			return $service;
		});

		$this['sly-service-language'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];
			$cache       = $container['sly-cache'];
			$dispatcher  = $container['sly-dispatcher'];

			return new sly_Service_Language($persistence, $cache, $dispatcher);
		});

		$this['sly-service-mediacategory'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];
			$cache       = $container['sly-cache'];
			$dispatcher  = $container['sly-dispatcher'];
			$service     = new sly_Service_MediaCategory($persistence, $cache, $dispatcher);

			// make sure the circular dependency does not make the app die with an endless loop
			$this['sly-service-mediacategory'] = $service;
			$service->setMediumService($container['sly-service-medium']);

			return $service;
		});

		$this['sly-service-medium'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];
			$cache       = $container['sly-cache'];
			$dispatcher  = $container['sly-dispatcher'];
			$categories  = $container['sly-service-mediacategory'];

			return new sly_Service_Medium($persistence, $cache, $dispatcher, $categories);
		});

		$this['sly-service-module'] = $this->share(function($container) {
			return new sly_Service_Module($container['sly-config'], $container['sly-dispatcher']);
		});

		$this['sly-service-package-addon'] = $this->share(function($container) {
			return new sly_Service_Package(SLY_ADDONFOLDER, $container['sly-cache']);
		});

		$this['sly-service-package-vendor'] = $this->share(function($container) {
			return new sly_Service_Package(SLY_VENDORFOLDER, $container['sly-cache']);
		});

		$this['sly-service-slice'] = $this->share(function($container) {
			return new sly_Service_Slice($container['sly-persistence']);
		});

		$this['sly-service-template'] = $this->share(function($container) {
			return new sly_Service_Template($container['sly-config'], $container['sly-dispatcher']);
		});

		$this['sly-service-user'] = $this->share(function($container) {
			$cache       = $container['sly-cache'];
			$config      = $container['sly-config'];
			$dispatcher  = $container['sly-dispatcher'];
			$persistence = $container['sly-persistence'];

			return new sly_Service_User($persistence, $cache, $dispatcher, $config);
		});

		//////////////////////////////////////////////////////////////////////////
		// helpers

		$this['sly-slice-renderer'] = $this->share(function($container) {
			return new sly_Slice_RendererImpl($container['sly-service-module']);
		});

		//////////////////////////////////////////////////////////////////////////
		// allow to overwrite default recipes

		// $this->values is private, so we have to do it this way
		foreach ($values as $key => $value) {
			$this[$key] = $value;
		}
	}

	/**
	 * Returns the number of elements
	 *
	 * @return int
	 */
	public function count() {
		return count($this->keys());
	}

	/**
	 * @param  string $id
	 * @param  mixed  $value
	 * @return sly_Container  reference to self
	 */
	public function set($id, $value) {
		$this->offsetSet($id, $value);

		return $this;
	}

	public function offsetSet($id, $value) {
		if (is_object($value) && $value instanceof sly_ContainerAwareInterface) {
			$value->setContainer($this);
		}

		parent::offsetSet($id, $value);

		return $this;
	}

	/**
	 * @param  string $id
	 * @return boolean
	 */
	public function has($id) {
		return $this->offsetExists($id);
	}

	/**
	 * @param  string $id
	 * @return sly_Container  reference to self
	 */
	public function remove($id) {
		$this->offsetUnset($id);

		return $this;
	}

	/**
	 * @throws InvalidArgumentException if the identifier is not defined
	 * @param  string $id
	 * @return mixed
	 */
	public function get($id) {
		return $this->offsetGet($id);
	}

	/**
	 * @return int|null
	 */
	public function getCurrentArticleID() {
		return $this['sly-current-article-id'];
	}

	/**
	 * @return int|null
	 */
	public function getCurrentLanguageID() {
		return $this['sly-current-lang-id'];
	}

	/**
	 * @return string
	 */
	public function getEnvironment() {
		return $this['sly-environment'];
	}

	/**
	 * @return sly_Configuration
	 */
	public function getConfig() {
		return $this['sly-config'];
	}

	/**
	 * @return sly_Event_IDispatcher
	 */
	public function getDispatcher() {
		return $this['sly-dispatcher'];
	}

	/**
	 * @return sly_Layout
	 */
	public function getLayout() {
		return $this['sly-layout'];
	}

	/**
	 * @return sly_I18N
	 */
	public function getI18N() {
		return $this['sly-i18n'];
	}

	/**
	 * @return sly_Registry_Temp
	 */
	public function getTempRegistry() {
		return $this['sly-registry-temp'];
	}

	/**
	 * @return sly_Registry_Persistent
	 */
	public function getPersistentRegistry() {
		return $this['sly-registry-persistent'];
	}

	/**
	 * @return sly_ErrorHandler_Interface
	 */
	public function getErrorHandler() {
		return $this['sly-error-handler'];
	}

	/**
	 * @return sly_Request
	 */
	public function getRequest() {
		return $this['sly-request'];
	}

	/**
	 * @return sly_Response
	 */
	public function getResponse() {
		return $this['sly-response'];
	}

	/**
	 * @return sly_Session
	 */
	public function getSession() {
		return $this['sly-session'];
	}

	/**
	 * @return sly_DB_PDO_Persistence
	 */
	public function getPersistence() {
		return $this['sly-persistence'];
	}

	/**
	 * @return BabelCache_Interface
	 */
	public function getCache() {
		return $this['sly-cache'];
	}

	/**
	 * @return sly_App_Interface
	 */
	public function getApplication() {
		return $this['sly-app'];
	}

	/**
	 * @return string
	 */
	public function getApplicationName() {
		return $this['sly-app-name'];
	}

	/**
	 * @return string
	 */
	public function getApplicationBaseUrl() {
		return $this['sly-app-baseurl'];
	}

	/**
	 * get addOn service
	 *
	 * @return sly_Service_AddOn
	 */
	public function getAddOnService() {
		return $this['sly-service-addon'];
	}

	/**
	 * get addOn manager service
	 *
	 * @return sly_Service_AddOnManager
	 */
	public function getAddOnManagerService() {
		return $this['sly-service-addon-manager'];
	}

	/**
	 * get article service
	 *
	 * @return sly_Service_Article
	 */
	public function getArticleService() {
		return $this['sly-service-article'];
	}

	/**
	 * get article service for deleted articles
	 *
	 * @return sly_Service_DeletedArticle
	 */
	public function getDeletedArticleService() {
		return $this['sly-service-deletedarticle'];
	}

	/**
	 * get article slice service
	 *
	 * @return sly_Service_ArticleSlice
	 */
	public function getArticleSliceService() {
		return $this['sly-service-articleslice'];
	}

	/**
	 * get article type service
	 *
	 * @return sly_Service_ArticleType
	 */
	public function getArticleTypeService() {
		return $this['sly-service-articletype'];
	}

	/**
	 * get asset service
	 *
	 * @return sly_Service_Asset
	 */
	public function getAssetService() {
		return $this['sly-service-asset'];
	}

	/**
	 * get category service
	 *
	 * @return sly_Service_Category
	 */
	public function getCategoryService() {
		return $this['sly-service-category'];
	}

	/**
	 * get language service
	 *
	 * @return sly_Service_Language
	 */
	public function getLanguageService() {
		return $this['sly-service-language'];
	}

	/**
	 * get media category service
	 *
	 * @return sly_Service_MediaCategory
	 */
	public function getMediaCategoryService() {
		return $this['sly-service-mediacategory'];
	}

	/**
	 * get medium service
	 *
	 * @return sly_Service_Medium
	 */
	public function getMediumService() {
		return $this['sly-service-medium'];
	}

	/**
	 * get module service
	 *
	 * @return sly_Service_Module
	 */
	public function getModuleService() {
		return $this['sly-service-module'];
	}

	/**
	 * get addOn package service
	 *
	 * @return sly_Service_AddOnPackage
	 */
	public function getAddOnPackageService() {
		return $this['sly-service-package-addon'];
	}

	/**
	 * get vendor package service
	 *
	 * @return sly_Service_VendorPackage
	 */
	public function getVendorPackageService() {
		return $this['sly-service-package-vendor'];
	}

	/**
	 * get slice service
	 *
	 * @return sly_Service_Slice
	 */
	public function getSliceService() {
		return $this['sly-service-slice'];
	}

	/**
	 * get template service
	 *
	 * @return sly_Service_Template
	 */
	public function getTemplateService() {
		return $this['sly-service-template'];
	}

	/**
	 * get user service
	 *
	 * @return sly_Service_User
	 */
	public function getUserService() {
		return $this['sly-service-user'];
	}

	/**
	 * @return sly_Util_FlashMessage
	 */
	public function getFlashMessage() {
		return $this['sly-flash-message'];
	}

	/**
	 * @return xrstf_Composer52_ClassLoader
	 */
	public function getClassLoader() {
		return $this['sly-classloader'];
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

			$this[$id] = new $className();
		}

		return $this[$id];
	}

	/*          setters for objects that are commonly set          */

	/**
	 * @param  string $env    the new environment, e.g. 'dev' or 'prod'
	 * @return sly_Container  reference to self
	 */
	public function setEnvironment($env) {
		return $this->set('sly-environment', $env);
	}

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
	 * @param  sly_ErrorHandler $handler  the new error handler
	 * @return sly_Container              reference to self
	 */
	public function setErrorHandler(sly_ErrorHandler $handler) {
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

	/**
	 * @param  string $name      the new application name
	 * @param  string $baseUrl   the new base URL (will be normalized to '/base')
	 * @return sly_Container     reference to self
	 */
	public function setApplicationInfo($name, $baseUrl) {
		$baseUrl = trim($baseUrl, '/');

		if (strlen($baseUrl) > 0) {
			$baseUrl = '/'.$baseUrl;
		}

		return $this->set('sly-app-name', $name)->set('sly-app-baseurl', $baseUrl);
	}

	/**
	 * @param  string $dir       the sally config dir
	 * @return sly_Container     reference to self
	 */
	public function setConfigDir($dir) {
		return $this->set('sly-config-dir', $dir);
	}
}
