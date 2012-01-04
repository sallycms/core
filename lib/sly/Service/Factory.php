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
		if (!isset(self::$services[$modelName])){
			$serviceName = 'sly_Service_'.$modelName;

			if (!class_exists($serviceName)) {
				throw new sly_Exception(t('service_not_found', $modelName));
			}

			$service = new $serviceName();

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
	 * @return sly_Service_SliceValue  The slice value service instance
	 */
	public static function getSliceValueService() {
		return self::getService('SliceValue');
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
	 * @return sly_Service_AddOn  The addon service instance
	 */
	public static function getAddOnService() {
		return self::getService('AddOn');
	}

	/**
	 * @return sly_Service_Plugin  The plugin service instance
	 */
	public static function getPluginService() {
		return self::getService('Plugin');
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
