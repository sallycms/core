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
 * Interface for event dispatchers
 *
 * @author  Christoph
 * @since   0.7.2
 * @ingroup event
 */
interface sly_Event_IDispatcher {
	/**
	 * Registers a listener
	 *
	 * Registers a callback for a given event, remembering it for later
	 * execution. A listener can get a list of special parameters that will be
	 * handed to it when it's called.
	 *
	 * @param string  $event     the event name (case sensitive, use upper case by convention)
	 * @param mixed   $listener  the callback (anything PHP regards as callable)
	 * @param array   $array     additional params for the listener
	 * @param boolean $first     if true, the listener will be put in front of existing listeners
	 */
	public function register($event, $listener, $params = array(), $first = false);

	/**
	 * Return all listeners for one event
	 *
	 * @param  string $event  the event name
	 * @return boolean        true if the event exists, else false
	 */
	public function clear($event);

	/**
	 * Return a list of all known events
	 *
	 * This goes through all registered listeners and returns the list of all
	 * events having a listener attachted to them.
	 *
	 * @return array  list of events (unsorted)
	 */
	public function getEvents();

	/**
	 * Check for listeners
	 *
	 * @param  string $event  the event name
	 * @return boolean        true if the event has listeners, else false
	 */
	public function hasListeners($event);

	/**
	 * Return all listeners
	 *
	 * @param  string $event  the event name
	 * @return array          list of listeners (unsorted)
	 */
	public function getListeners($event);

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
	public function notify($event, $subject = null, $params = array());

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
	public function notifyUntil($event, $subject = null, $params = array());

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
	public function filter($event, $subject = null, $params = array());
}
