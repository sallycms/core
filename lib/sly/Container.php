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
			$yamlService = $container['sly-service-yaml'];

			// check if the persistence may be already available
			$persistence = $container->raw('sly-persistence');

			if (!($persistence instanceof sly_DB_Persistence)) {
				$persistence = null;
			}

			return new sly_Configuration_DatabaseImpl(SLY_CONFIGFOLDER, $yamlService, $persistence);
		});

		$this['sly-config-writer'] = function($container) {
			return $container['sly-config-reader'];
		};

		$this['sly-dispatcher'] = $this->share(function($container) {
			return new sly_Event_Dispatcher($container);
		});

		$this['sly-error-handler'] = $this->share(function($container) {
			$devMode = $container['sly-environment'] !== 'prod';

			return $devMode ? new sly_ErrorHandler_Development() : new sly_ErrorHandler_Production();
		});

		$this['sly-registry-temp'] = $this->share(function($container) {
			return new sly_Registry_Temp();
		});

		$this['sly-registry-persistent'] = $this->share(function($container) {
			return new sly_Registry_Persistent($container['sly-persistence']);
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

		$this['sly-pdo-driver'] = $this->inject(function($container) {
			$config = $container['sly-config']->get('database');
			$driver = $config['driver'];

			if (!class_exists('sly_DB_PDO_Driver_'.strtoupper($driver))) {
				throw new sly_DB_PDO_Exception('Unknown Database Driver: '.$driver);
			}

			$driverClass = 'sly_DB_PDO_Driver_'.strtoupper($driver);

			return new $driverClass($config['host'], $config['login'], $config['password'], $config['name']);
		});

		$this['sly-pdo-connection'] = $this->share(function($container) {
			$config = $container['sly-config']->get('database');
			$driver = $container['sly-pdo-driver'];
			$pdo    = new PDO($driver->getDSN(), $config['login'], $config['password'], $driver->getPDOOptions());

			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			foreach ($driver->getPDOAttributes() as $key => $value) {
				$pdo->setAttribute($key, $value);
			}

			return new sly_DB_PDO_Connection($driver, $pdo);
		});

		$this['sly-persistence'] = $this->inject(function($container) {
			$config     = $container['sly-config']->get('database');
			$connection = $container['sly-pdo-connection'];

			// TODO: to support the iterator inside the persistence, we need to create
			// a fresh instance for every access. We should refactor the database access
			// to allow for a single persistence instance.
			return new sly_DB_PDO_Persistence($config['driver'], $connection, $config['table_prefix']);
		});

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
			$dynFs      = $container['sly-filesystem-dyn'];
			$service    = new sly_Service_AddOn($config, $cache, $adnService, $dynFs);

			$service->setVendorPackageService($vndService);

			return $service;
		});

		$this['sly-service-addon-manager'] = $this->share(function($container) {
			$config     = $container['sly-config'];
			$dispatcher = $container['sly-dispatcher'];
			$cache      = $container['sly-cache'];
			$service    = $container['sly-service-addon'];

			return new sly_Service_AddOn_Manager($config, $dispatcher, $cache, $service);
		});

		$this['sly-service-article'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];
			$slices      = $container['sly-service-slice'];
			$articles    = $container['sly-service-articleslice'];
			$templates   = $container['sly-service-template'];

			return new sly_Service_Article($persistence, $slices, $articles, $templates);
		});

		$this['sly-service-deletedarticle'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];

			return new sly_Service_DeletedArticle($persistence);
		});

		$this['sly-service-articleslice'] = $this->share(function($container) {
			return new sly_Service_ArticleSlice();
		});

		$this['sly-service-articletype'] = $this->share(function($container) {
			$config    = $container['sly-config'];
			$modules   = $container['sly-service-module'];
			$templates = $container['sly-service-template'];

			return new sly_Service_ArticleType($config, $modules, $templates);
		});

		$this['sly-service-asset'] = $this->share(function($container) {
			$service      = new sly_Asset_Service($container['sly-dispatcher']);
			$lessCompiler = $container['sly-service-asset-lessphp'];
			$filePerm     = $container['sly-config']->get('fileperm');
			$dirPerm      = $container['sly-config']->get('dirperm');

			$service->addProcessListener(function($lessFile) use ($lessCompiler, $filePerm, $dirPerm) {
				if (!sly_Util_String::endsWith($lessFile, '.less') || !file_exists(SLY_BASE.'/'.$lessFile)) {
					return $lessFile;
				}

				$css     = $lessCompiler->process($lessFile);
				$dir     = SLY_TEMPFOLDER.'/sally/less-cache';
				$tmpFile = $dir.'/'.md5($lessFile).'.css';

				sly_Util_Directory::create($dir, $dirPerm, true);

				file_put_contents($tmpFile, $css);
				chmod($tmpFile, $filePerm);

				return $tmpFile;
			});

			return $service;
		});

		$this['sly-service-asset-lessphp'] = $this->share(function($container) {
			$lessc    = new lessc();
			$compiler = new sly_Asset_Compiler_Lessphp($lessc);
			$config   = $container['sly-config'];

			$lessc->setFormatter('compressed');
			$lessc->registerFunction('asset', array($compiler, 'lessAssetFunction'));

			foreach ($config->get('less_import_dirs') as $includeDir) {
				$compiler->addImportDir(SLY_BASE.DIRECTORY_SEPARATOR.trim($includeDir, DIRECTORY_SEPARATOR));
			}

			return $compiler;
		});

		$this['sly-service-category'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];

			return new sly_Service_Category($persistence);
		});

		$this['sly-service-filesystem'] = $this->share(function($container) {
			return new sly_Service_Filesystem(SLY_TEMPFOLDER);
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

			return new sly_Service_MediaCategory($persistence, $cache, $dispatcher);
		});

		$this['sly-service-medium'] = $this->share(function($container) {
			$persistence = $container['sly-persistence'];
			$cache       = $container['sly-cache'];
			$dispatcher  = $container['sly-dispatcher'];
			$filesystem  = $container['sly-filesystem-media'];
			$blocked     = $container['sly-config']->get('blocked_extensions');
			$fsBaseUri   = 'sly://media/';

			return new sly_Service_Medium($persistence, $cache, $dispatcher, $filesystem, $blocked, $fsBaseUri);
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

		$this['sly-service-json'] = $this->share(function($container) {
			$fileperm = $container['sly-config']->get('fileperm', sly_Core::DEFAULT_FILEPERM);

			return new sly_Service_File_JSON($fileperm);
		});

		$this['sly-service-yaml'] = $this->share(function($container) {
			$fileperm = $container['sly-config']->get('fileperm', sly_Core::DEFAULT_FILEPERM);

			return new sly_Service_File_YAML($fileperm);
		});

		//////////////////////////////////////////////////////////////////////////
		// filesystems

		$this['sly-filesystem-media'] = $this->share(function($container) {
			return $this->getLocalFilesystem(SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'mediapool', false);
		});

		$this['sly-filesystem-dyn'] = $this->share(function($container) {
			return $this->getLocalFilesystem(SLY_DATAFOLDER.DIRECTORY_SEPARATOR.'dyn', true);
		});

		$this['sly-filesystem-map'] = $this->share(function($container) {
			$map = new Gaufrette\FilesystemMap();

			$map->set('dyn', $container['sly-filesystem-dyn']);
			$map->set('media', $container['sly-filesystem-media']);

			return $map;
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
	 * Returns a closure that tests for *Aware interfaces and injects common
	 * dependencies via setter methods.
	 *
	 * See here why this method is static but in most cases not called
	 * statically: https://github.com/fabpot/Pimple/pull/63
	 *
	 * @param  Closure $callable  a closure to wrap
	 * @return Closure            the wrapped closure
	 */
	public static function inject(Closure $callable) {
		return function ($c) use ($callable) {
			$object = $callable($c);

			if ($object instanceof sly_ContainerAwareInterface) {
				$object->setContainer($c);
			}

			return $object;
		};
	}

	/**
	 * Returns a closure that stores the result of the given closure for
	 * uniqueness in the scope of this instance of Pimple.
	 *
	 * @param  Closure $callable            a closure to wrap for uniqueness
	 * @param  bool    $injectOptionalDeps  whether to automatically wrap in inject() as well
	 * @return Closure                      the wrapped closure
	 */
	public static function share(Closure $callable, $injectOptionalDeps = true) {
		if ($injectOptionalDeps) {
			$callable = self::inject($callable);
		}

		return function ($c) use ($callable) {
			static $object;

			if (null === $object) {
				$object = $callable($c);
			}

			return $object;
		};
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
	 * @return Composer\Autoload\ClassLoader
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

	/**
	 * @return Gaufrette\Filesystem
	 */
	public function getMediaFilesystem() {
		return $this->get('sly-filesystem-media');
	}

	/**
	 * @return Gaufrette\Filesystem
	 */
	public function getDynFilesystem() {
		return $this->get('sly-filesystem-dyn');
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
	 * @param  string $dir    the sally config dir
	 * @return sly_Container  reference to self
	 */
	public function setConfigDir($dir) {
		return $this->set('sly-config-dir', $dir);
	}

	protected function getLocalFilesystem($dir, $isPrivate) {
		// make sure the mediapool directory exists, as Gaufrette is not going to create it for us
		if ($isPrivate) {
			$dir = sly_Util_Directory::createHttpProtected($dir, true);
		}
		else {
			$dir = sly_Util_Directory::create($dir, null, true);
		}

		$adapter = new Gaufrette\Adapter\Local($dir);
		$fs      = new Gaufrette\Filesystem($adapter);

		return $fs;
	}
}
