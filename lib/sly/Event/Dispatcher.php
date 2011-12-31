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
 * Event system
 *
 * This class is reponsible for holding the list of known event listeners and
 * performing the actual dispatching of events. It supports three ways to call
 * and combine the listeners: 'notify' just sends all listeners the same data
 * and does not combining, 'notifyUntil' fires all listeners until the first one
 * returns true and 'filter' calls all listeners in succession and pipes the
 * results through them.
 *
 * @author  Christoph
 * @since   0.2
 * @ingroup event
 */
class sly_Event_Dispatcher {
	private $listeners;         ///< array                 list of listeners
	private static $instance;   ///< sly_Event_Dispatcher  the current dispatcher instance

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->listeners = array();
	}

	/**
	 * Singleton access
	 *
	 * @return sly_Event_Dispatcher  the instance
	 */
	public static function getInstance() {
		if (empty(self::$instance)) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Registers a listener
	 *
	 * Registers a callback for a given event, remembering it for later
	 * execution. A listener can get a list of special parameters that will be
	 * handed to it when it's called.
	 *
	 * @param string $event     the event name (case sensitive, use upper case by convention)
	 * @param mixed  $listener  the callback (anything PHP regards as callable)
	 * @param array  $array     additional params for the listener
	 */
	public function register($event, $listener, $params = array()) {
		$params = sly_makeArray($params);
		$this->listeners[$event][] = array('listener' => $listener, 'params' => $params);
	}

	/**
	 * Return all listeners for one event
	 *
	 * @param  string $event  the event name
	 * @return boolean        true if the event exists, else false
	 */
	public function clear($event) {
		if (isset($this->listeners[$event])) {
			$this->listeners[$event] = array();
			return true;
		}

		return false;
	}

	/**
	 * Return a list of all known events
	 *
	 * This goes through all registered listeners and returns the list of all
	 * events having a listener attachted to them.
	 *
	 * @return array  list of events (unsorted)
	 */
	public function getEvents() {
		return array_keys($this->listeners);
	}

	/**
	 * Check for listeners
	 *
	 * @param  string $event  the event name
	 * @return boolean        true if the event has listeners, else false
	 */
	public function hasListeners($event) {
		return !empty($this->listeners[$event]);
	}

	/**
	 * Return all listeners
	 *
	 * @param  string $event  the event name
	 * @return array          list of listeners (unsorted)
	 */
	public function getListeners($event) {
		return $this->hasListeners($event) ? $this->listeners[$event] : array();
	}

	/**
	 * Notify all listeners
	 *
	 * This method will call all listeners but not evaluate their return values.
	 * It's like "fire and forget" and useful if you're not interested in what
	 * listeners have to say.
	 *
	 * @param  string $event    the event to be triggered
	 * @param  mixed  $subject  an optional value for the listeners to work with
	 * @param  array  $params   additional parameters (if necessary)
	 * @return int              the number of listeners that have been executed
	 */
	public function notify($event, $subject = null, $params = array()) {
		$result = $this->iterate($event, $subject, $params, 'forget');
		return $result['called'];
	}

	/**
	 * Notify all listeners until one stops
	 *
	 * This method will call all listeners and stop when the first one returns
	 * true. A listener therefore can decide whether further listeners will be
	 * called or not.
	 *
	 * Be careful: If a listener returns false/null, you cannot distinguish this
	 * from an error or empty event.
	 *
	 * @param  string $event    the event to be triggered
	 * @param  mixed  $subject  an optional value for the listeners to work with
	 * @param  array  $params   additional parameters (if necessary)
	 * @return mixed            null if no listeners are set, false if no
	 *                          listener stops the evaluation or else true
	 */
	public function notifyUntil($event, $subject = null, $params = array()) {
		$result = $this->iterate($event, $subject, $params, 'stop');

		switch ($result['state']) {
			case 'empty':   return null;
			case 'stopped': return true;
			default:        return false;
		}
	}

	/**
	 * Filter a value
	 *
	 * This method will call all listeners and give each one the return value of
	 * it's predecessor. The first listener get's the unaltered $subject. The
	 * result of this method is the return value of the last listener.
	 *
	 * Listeners cannot stop the evaluation (in contrast to notifyUntil()).
	 *
	 * @param  string $event    the event to be triggered
	 * @param  mixed  $subject  an optional value for the listeners to work with
	 * @param  array  $params   additional parameters (if necessary)
	 * @return mixed            the return value of the last listener or the
	 *                          original subject if no listeners have been set
	 */
	public function filter($event, $subject = null, $params = array()) {
		$result = $this->iterate($event, $subject, $params, 'filter');
		return $result['result'];
	}

	/**
	 * Iteration algorithm
	 *
	 * This method implements the actual iteration over all listeners, allowing
	 * the three combination methods to configure how the results from one
	 * listener should be combined with the next one.
	 *
	 * @param  string $event         the event to be triggered
	 * @param  mixed  $subject       an optional value for the listeners to work with
	 * @param  array  $params        additional parameters (if necessary)
	 * @param  string $foldStrategy  'filter', 'forget' or 'stop'
	 * @return array                 an array consisting of 'state' 'called' and 'result'
	 */
	protected function iterate($event, $subject, $params, $foldStrategy) {
		if (!$this->hasListeners($event)) return array('state' => 'empty', 'called' => 0, 'result' => $subject);

		$params    = sly_makeArray($params);
		$listeners = $this->getListeners($event);
		$called    = 0;

		$params['event']   = $event;
		$params['subject'] = $subject;

		foreach ($listeners as $listener) {
			$callee = $listener['listener'];
			$args   = array_merge($params, $listener['params']);
			$retval = call_user_func($callee, $args);

			++$called;

			switch ($foldStrategy) {
				case 'filter':
					// The return value of this listener shall be the subject of the next one.
					$params['subject'] = $retval;
					break;

				case 'stop':
					// If one listener returns true, break the loop.
					if ($retval === true) return array('state' => 'stopped', 'called' => $called);
			}
		}

		return array('state' => 'done', 'result' => $params['subject'], 'called' => $called);
	}
}
