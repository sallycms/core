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
 * @deprecated  use sly_Container instead
 * @ingroup     service
 */
abstract class sly_Service_Factory {
	/**
	 * Return a instance of a service
	 *
	 * @throws sly_Exception      if service could not be found
	 * @param  string $modelName  service name (like 'Category' or 'User')
	 * @return sly_Service_Base   an implementation of sly_Service_Base
	 */
	public static function getService($modelName) {
		return sly_Core::getContainer()->getService($modelName);
	}

	/**
	 * @return sly_Service_Slice  The slice service instance
	 */
	public static function getSliceService() {
		return sly_Core::getContainer()->getSliceService();
	}

	/**
	 * @return sly_Service_SliceValue  The slice value service instance
	 */
	public static function getSliceValueService() {
		return sly_Core::getContainer()->getSliceValueService();
	}

	/**
	 * @return sly_Service_Template  The template service instance
	 */
	public static function getTemplateService() {
		return sly_Core::getContainer()->getTemplateService();
	}

	/**
	 * @return sly_Service_Module  The module service instance
	 */
	public static function getModuleService() {
		return sly_Core::getContainer()->getModuleService();
	}

	/**
	 * @return sly_Service_Package  The package service instance initiliazed on SLY_VENDORFOLDER
	 */
	public static function getVendorPackageService() {
		return sly_Core::getContainer()->getVendorPackageService();
	}

	/**
	 * @return sly_Service_Package  The package service instance initiliazed on SLY_ADDONFOLDER
	 */
	public static function getAddOnPackageService() {
		return sly_Core::getContainer()->getAddOnPackageService();
	}

	/**
	 * @return sly_Service_AddOn  The addOn service instance
	 */
	public static function getAddOnService() {
		return sly_Core::getContainer()->getAddOnService();
	}

	/**
	 * @return sly_Service_AddOn_Manager  The addOn manager service instance
	 */
	public static function getAddOnManagerService() {
		return sly_Core::getContainer()->getAddOnManagerService();
	}

	/**
	 * @return sly_Service_User  The user service instance
	 */
	public static function getUserService() {
		return sly_Core::getContainer()->getUserService();
	}

	/**
	 * @return sly_Service_ArticleType  The user service instance
	 */
	public static function getArticleTypeService() {
		return sly_Core::getContainer()->getArticleTypeService();
	}

	/**
	 * @return sly_Service_Category
	 */
	public static function getCategoryService() {
		return sly_Core::getContainer()->getCategoryService();
	}

	/**
	 * @return sly_Service_Article
	 */
	public static function getArticleService() {
		return sly_Core::getContainer()->getArticleService();
	}

	/**
	 * @return sly_Service_Language
	 */
	public static function getLanguageService() {
		return sly_Core::getContainer()->getLanguageService();
	}

	/**
	 * @return sly_Service_Asset  The asset service instance
	 */
	public static function getAssetService() {
		return sly_Core::getContainer()->getAssetService();
	}

	/**
	 * @return sly_Service_Medium  The medium service instance
	 */
	public static function getMediumService() {
		return sly_Core::getContainer()->getMediumService();
	}

	/**
	 * @return sly_Service_MediaCategory  The media category service instance
	 */
	public static function getMediaCategoryService() {
		return sly_Core::getContainer()->getMediaCategoryService();
	}

	/**
	 * @return sly_Service_ArticleSlice  The articleslice service instance
	 */
	public static function getArticleSliceService() {
		return sly_Core::getContainer()->getArticleSliceService();
	}
}
