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
 * @ingroup service
 */
abstract class sly_Service_Factory {
	private static $services = array(); ///< array

	/**
	 * Return a instance of a service
	 *
	 * @throws sly_Exception      if service could not be found
	 * @param  string $modelName  service name (like 'Category' or 'User')
	 * @return sly_Service_Base   an implementation of sly_Service_Base
	 */
	public static function getService($modelName) {
		if (!isset(self::$services[$modelName])) {
			$serviceName = 'sly_Service_'.$modelName;

			if ($modelName === 'Package_Vendor' || $modelName === 'Package_AddOn') {
				$serviceName = 'sly_Service_Package';
			}

			if (!class_exists($serviceName)) {
				throw new sly_Exception(t('service_not_found', $modelName));
			}

			switch ($modelName) {
				case 'Asset':
					$service = new $serviceName(sly_Core::config(), sly_Core::dispatcher());
					break;

				case 'ArticleType':
					$modules   = self::getService('Module');
					$templates = self::getService('Template');
					$service   = new $serviceName(sly_Core::config(), $modules, $templates);
					break;

				case 'AddOn_Manager':
					$cache      = sly_Core::cache();
					$config     = sly_Core::config();
					$dispatcher = sly_Core::dispatcher();
					$aService   = self::getService('AddOn');
					$service    = new $serviceName($config, $dispatcher, $cache, $aService);
					break;

				case 'AddOn':
					$cache      = sly_Core::cache();
					$config     = sly_Core::config();
					$pkgService = self::getService('Package_AddOn');
					$vndService = self::getService('Package_Vendor');
					$service    = new $serviceName($config, $cache, $pkgService, SLY_DYNFOLDER);

					$service->setVendorPackageService($vndService);
					break;

				case 'Package_Vendor':
					$cache   = sly_Core::cache();
					$service = new $serviceName(SLY_VENDORFOLDER, $cache);
					break;

				case 'Package_AddOn':
					$cache   = sly_Core::cache();
					$service = new $serviceName(SLY_ADDONFOLDER, $cache);
					break;

				case 'Module':
				case 'Template':
					$service = new $serviceName(sly_Core::config(), sly_Core::dispatcher());
					break;

				case 'ArticleSlice':
					$db         = sly_DB_Persistence::getInstance();
					$dispatcher = sly_Core::dispatcher();
					$sliceServ  = self::getService('Slice');
					$tplService = self::getService('Template');
					$service    = new $serviceName($db, $dispatcher, $sliceServ, $tplService);
					break;

				case 'Language':
					$db         = sly_DB_Persistence::getInstance();
					$cache      = sly_Core::cache();
					$dispatcher = sly_Core::dispatcher();
					$service    = new $serviceName($db, $cache, $dispatcher);
					break;

				case 'User':
					$db         = sly_DB_Persistence::getInstance();
					$cache      = sly_Core::cache();
					$dispatcher = sly_Core::dispatcher();
					$config     = sly_Core::config();
					$service    = new $serviceName($db, $cache, $dispatcher, $config);
					break;

				case 'Medium':
					$db         = sly_DB_Persistence::getInstance();
					$cache      = sly_Core::cache();
					$dispatcher = sly_Core::dispatcher();
					$catService = self::getService('MediaCategory');
					$service    = new $serviceName($db, $cache, $dispatcher, $catService);
					break;

				case 'MediaCategory':
					$db         = sly_DB_Persistence::getInstance();
					$cache      = sly_Core::cache();
					$dispatcher = sly_Core::dispatcher();
					$service    = new $serviceName($db, $cache, $dispatcher);

					// make sure the circular dependency does not make the app die with an endless loop
					self::$services[$modelName] = $service;

					// now we can set the medium service, which in turn needs the mediacat service
					$mediumService = self::getService('Medium');
					$service->setMediumService($mediumService);
					break;

				case 'Article':
					$db         = sly_DB_Persistence::getInstance();
					$cache      = sly_Core::cache();
					$dispatcher = sly_Core::dispatcher();
					$languages  = self::getService('Language');
					$slices     = self::getService('Slice');
					$articles   = self::getService('ArticleSlice');
					$templates  = self::getService('Template');
					$service    = new $serviceName($db, $cache, $dispatcher, $languages, $slices, $articles, $templates);

					// make sure the circular dependency does not make the app die with an endless loop
					self::$services[$modelName] = $service;

					$service->setArticleService($service);
					$service->setCategoryService(self::getService('Category'));
					break;

				case 'Category':
					$db         = sly_DB_Persistence::getInstance();
					$cache      = sly_Core::cache();
					$dispatcher = sly_Core::dispatcher();
					$languages  = self::getService('Language');
					$service    = new $serviceName($db, $cache, $dispatcher, $languages);

					// make sure the circular dependency does not make the app die with an endless loop
					self::$services[$modelName] = $service;

					$service->setArticleService(self::getService('Article'));
					$service->setCategoryService($service);
					break;

				case 'Slice':
					$service = new $serviceName(sly_DB_Persistence::getInstance());
					break;

				default:
					$service = new $serviceName();
			}

			self::$services[$modelName] = $service;
		}

		return self::$services[$modelName];
	}

	/**
	 * @return sly_Service_Slice  The slice service instance
	 */
	public static function getSliceService() {
		return self::getService('Slice');
	}

	/**
	 * @return sly_Service_Template  The template service instance
	 */
	public static function getTemplateService() {
		return self::getService('Template');
	}

	/**
	 * @return sly_Service_Module  The module service instance
	 */
	public static function getModuleService() {
		return self::getService('Module');
	}

	/**
	 * @return sly_Service_Package  The package service instance initiliazed on SLY_VENDORFOLDER
	 */
	public static function getVendorPackageService() {
		return self::getService('Package_Vendor');
	}

	/**
	 * @return sly_Service_Package  The package service instance initiliazed on SLY_ADDONFOLDER
	 */
	public static function getAddOnPackageService() {
		return self::getService('Package_AddOn');
	}

	/**
	 * @return sly_Service_AddOn  The addOn service instance
	 */
	public static function getAddOnService() {
		return self::getService('AddOn');
	}

	/**
	 * @return sly_Service_AddOn_Manager  The addOn manager service instance
	 */
	public static function getAddOnManagerService() {
		return self::getService('AddOn_Manager');
	}

	/**
	 * @return sly_Service_User  The user service instance
	 */
	public static function getUserService() {
		return self::getService('User');
	}

	/**
	 * @return sly_Service_ArticleType  The user service instance
	 */
	public static function getArticleTypeService() {
		return self::getService('ArticleType');
	}

	/**
	 * @return sly_Service_Category
	 */
	public static function getCategoryService() {
		return self::getService('Category');
	}

	/**
	 * @return sly_Service_Article
	 */
	public static function getArticleService() {
		return self::getService('Article');
	}

	/**
	 * @return sly_Service_Language
	 */
	public static function getLanguageService() {
		return self::getService('Language');
	}

	/**
	 * @return sly_Service_Asset  The asset service instance
	 */
	public static function getAssetService() {
		return self::getService('Asset');
	}

	/**
	 * @return sly_Service_Medium  The medium service instance
	 */
	public static function getMediumService() {
		return self::getService('Medium');
	}

	/**
	 * @return sly_Service_MediaCategory  The media category service instance
	 */
	public static function getMediaCategoryService() {
		return self::getService('MediaCategory');
	}

	/**
	 * @return sly_Service_ArticleSlice  The articleslice service instance
	 */
	public static function getArticleSliceService() {
		return self::getService('ArticleSlice');
	}
}
