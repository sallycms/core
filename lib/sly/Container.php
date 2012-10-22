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
			'sly-current-article-id'  => null,
			'sly-current-lang-id'     => null,
			'sly-config'              => array($this, 'buildConfig'),
			'sly-dispatcher'          => array($this, 'buildDispatcher'),
			'sly-registry-temp'       => array($this, 'buildTempRegistry'),
			'sly-registry-persistent' => array($this, 'buildPersistentRegistry'),
			'sly-response'            => array($this, 'buildResponse'),
			'sly-session'             => array($this, 'buildSession'),
			'sly-persistence'         => array($this, 'buildPersistence'),
			'sly-cache'               => array($this, 'buildCache'),
			'sly-flash-message'       => array($this, 'buildFlashMessage')
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
		return $this['sly-current-article-id'];
	}

	/**
	 * @return int|null
	 */
	public function getCurrentLanguageID() {
		return $this['sly-current-lang-id'];
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
	 * @return sly_Util_FlashMessage
	 */
	public function getFlashMessage() {
		return $this['sly-flash-message'];
	}

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

	/*     factory methods     */

	/**
	 * @return sly_Configuration
	 */
	protected function buildConfig() {
		if (!isset($this->values['sly-config'])) {
			$this['sly-config'] = new sly_Configuration();
		}

		return $this['sly-config'];
	}

	/**
	 * @return sly_Event_IDispatcher
	 */
	protected function buildDispatcher() {
		if (!isset($this->values['sly-dispatcher'])) {
			$this['sly-dispatcher'] = new sly_Event_Dispatcher();
		}

		return $this['sly-dispatcher'];
	}

	/**
	 * @return sly_Registry_Temp
	 */
	protected function buildTempRegistry() {
		if (!isset($this->values['sly-registry-temp'])) {
			$this['sly-registry-temp'] = sly_Registry_Temp::getInstance();
		}

		return $this['sly-registry-temp'];
	}

	/**
	 * @return sly_Registry_Persistent
	 */
	protected function buildPersistentRegistry() {
		if (!isset($this->values['sly-registry-persistent'])) {
			$this['sly-registry-persistent'] = sly_Registry_Persistent::getInstance();
		}

		return $this['sly-registry-persistent'];
	}

	/**
	 * @return sly_Response
	 */
	protected function buildResponse() {
		if (!isset($this->values['sly-response'])) {
			$response = new sly_Response('', 200);
			$response->setContentType('text/html', 'UTF-8');

			$this->values['sly-response'] = $response;
		}

		return $this['sly-response'];
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Session
	 */
	protected function buildSession(sly_Container $container) {
		if (!isset($this->values['sly-session'])) {
			$this['sly-session'] = new sly_Session($container['config']->get('INSTNAME'));
		}

		return $this['sly-session'];
	}

	/**
	 * @return sly_DB_PDO_Persistence
	 */
	protected function buildPersistence() {
		if (!isset($this->values['sly-persistence'])) {
			$this['sly-persistence'] = sly_DB_Persistence::getInstance();
		}

		return $this['sly-persistence'];
	}

	/**
	 * @return BabelCache_Interface
	 */
	protected function buildCache() {
		if (!isset($this->values['sly-cache'])) {
			$this['sly-cache'] = sly_Cache::factory();
		}

		return $this['sly-cache'];
	}

	/**
	 * @param  sly_Container $container
	 * @return sly_Util_FlashMessage
	 */
	protected function buildFlashMessage(sly_Container $container) {
		if (!isset($this->values['sly-flash-message'])) {
			sly_Util_Session::start();

			$session = $container['session'];
			$msg     = sly_Util_FlashMessage::readFromSession('sally', $session);

			$msg->removeFromSession($session);
			$msg->setAutoStore(true);

			$this->values['sly-flash-message'] = $msg;
		}

		return $this['sly-flash-message'];
	}
}
