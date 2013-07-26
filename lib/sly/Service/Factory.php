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
 * @deprecated  use sly_Container instead
 * @ingroup     service
 */
abstract class sly_Service_Factory {
	/**
	 * Return a instance of a service
	 *
	 * @deprecated use sly_Container->getService() instead
	 * @throws     sly_Exception if service could not be found
	 *
	 * @param  string $modelName  service name (like 'Category' or 'User')
	 * @return sly_Service_Base   an implementation of sly_Service_Base
	 */
	public static function getService($modelName) {
		return sly_Core::getContainer()->getService($modelName);
	}

	/**
	 * get Slice service
	 *
	 * @deprecated use sly_Container->getSliceService() instead
	 *
	 * @return sly_Service_Slice  The slice service instance
	 */
	public static function getSliceService() {
		return sly_Core::getContainer()->getSliceService();
	}

	/**
	 * @return sly_Service_Template  The template service instance
	 *
	 * @deprecated use sly_Container->getTemplateService() instead
	 *
	 * @return sly_Service_Template  The template service instance
	 */
	public static function getTemplateService() {
		return sly_Core::getContainer()->getTemplateService();
	}

	/**
	 * get Module service
	 *
	 * @deprecated use sly_Container->getModuleService() instead
	 *
	 * @return sly_Service_Module  The module service instance
	 */
	public static function getModuleService() {
		return sly_Core::getContainer()->getModuleService();
	}

	/**
	 * get VendorPackage service
	 *
	 * @deprecated use sly_Container->getVendorPackageService() instead
	 *
	 * @return sly_Service_Package  The package service instance initiliazed on SLY_VENDORFOLDER
	 */
	public static function getVendorPackageService() {
		return sly_Core::getContainer()->getVendorPackageService();
	}

	/**
	 * get AddOnPackage service
	 *
	 * @deprecated use sly_Container->getAddOnPackageService() instead
	 *
	 * @return sly_Service_Package  The package service instance initiliazed on SLY_ADDONFOLDER
	 */
	public static function getAddOnPackageService() {
		return sly_Core::getContainer()->getAddOnPackageService();
	}

	/**
	 * get AddOn service
	 *
	 * @deprecated use sly_Container->getAddOnService() instead
	 *
	 * @return sly_Service_AddOn  The addOn service instance
	 */
	public static function getAddOnService() {
		return sly_Core::getContainer()->getAddOnService();
	}

	/**
	 * get AddOnManager service
	 *
	 * @deprecated use sly_Container->getAddOnManagerService() instead
	 *
	 * @return sly_Service_AddOn_Manager  The addOn manager service instance
	 */
	public static function getAddOnManagerService() {
		return sly_Core::getContainer()->getAddOnManagerService();
	}

	/**
	 * get User service
	 *
	 * @deprecated use sly_Container->getUserService() instead
	 *
	 * @return sly_Service_User  The user service instance
	 */
	public static function getUserService() {
		return sly_Core::getContainer()->getUserService();
	}

	/**
	 * get ArticleType service
	 *
	 * @deprecated use sly_Container->getArticleTypeService() instead
	 *
	 * @return sly_Service_ArticleType  The user service instance
	 */
	public static function getArticleTypeService() {
		return sly_Core::getContainer()->getArticleTypeService();
	}

	/**
	 * get Category service
	 *
	 * @deprecated use sly_Container->getCategoryService() instead
	 *
	 * @return sly_Service_Category
	 */
	public static function getCategoryService() {
		return sly_Core::getContainer()->getCategoryService();
	}

	/**
	 * get Article service
	 *
	 * @deprecated use sly_Container->getArticleService() instead
	 *
	 * @return sly_Service_Article
	 */
	public static function getArticleService() {
		return sly_Core::getContainer()->getArticleService();
	}

	/**
	 * get Language service
	 *
	 * @deprecated use sly_Container->getLanguageService() instead
	 *
	 * @return sly_Service_Language
	 */
	public static function getLanguageService() {
		return sly_Core::getContainer()->getLanguageService();
	}

	/**
	 * get Asset service
	 *
	 * @deprecated use sly_Container->getAssetService() instead
	 *
	 * @return sly_Service_Asset  The asset service instance
	 */
	public static function getAssetService() {
		return sly_Core::getContainer()->getAssetService();
	}

	/**
	 * get Medium service
	 *
	 * @deprecated use sly_Container->getMediumService() instead
	 *
	 * @return sly_Service_Medium  The medium service instance
	 */
	public static function getMediumService() {
		return sly_Core::getContainer()->getMediumService();
	}

	/**
	 * get MediaCategory service
	 *
	 * @deprecated use sly_Container->getMediaCategoryService() instead
	 *
	 * @return sly_Service_MediaCategory  The media category service instance
	 */
	public static function getMediaCategoryService() {
		return sly_Core::getContainer()->getMediaCategoryService();
	}

	/**
	 * get ArticleSlice service
	 *
	 * @deprecated use sly_Container->getArticleSliceService() instead
	 *
	 * @return sly_Service_ArticleSlice  The articleslice service instance
	 */
	public static function getArticleSliceService() {
		return sly_Core::getContainer()->getArticleSliceService();
	}
}
