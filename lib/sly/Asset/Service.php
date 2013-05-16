<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Asset_Service {
	protected $dispatcher;
	protected $accessCache;

	const EVENT_PROCESS_ASSET      = 'SLY_ASSET_PROCESS';
	const EVENT_IS_PROTECTED_ASSET = 'SLY_ASSET_IS_PROTECTED';

	/**
	 * Constructor
	 *
	 * @param sly_Event_IDispatcher $dispatcher
	 */
	public function __construct(sly_Event_IDispatcher $dispatcher) {
		$this->dispatcher  = $dispatcher;
		$this->accessCache = array();
	}

	public function addProcessListener($processor, array $params = array()) {
		$this->dispatcher->addListener(self::EVENT_PROCESS_ASSET, $processor, $params);
	}

	public function addProtectListener($protector, array $params = array()) {
		$this->dispatcher->addListener(self::EVENT_IS_PROTECTED_ASSET, $protector, $params);
	}

	public function compile($file) {
		return $this->dispatcher->filter(self::EVENT_PROCESS_ASSET, $file, array(
			'original' => $file
		));
	}

	public function isProtected($file) {
		if (!isset($this->accessCache[$file])) {
			$this->accessCache[$file] = $this->dispatcher->filter(self::EVENT_IS_PROTECTED_ASSET, false, compact('file'));
		}

		return $this->accessCache[$file];
	}

	public function clearAccessCache($file = null) {
		if ($file === null) {
			$this->accessCache = array();
		}
		elseif ($file && isset($this->accessCache[$file])) {
			unset($this->accessCache[$file]);
		}
	}
}
