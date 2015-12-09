<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

interface sly_Mail_Interface {
	/**
	 * @param  string $mail        the address
	 * @param  string $name        an optional name
	 * @return sly_Mail_Interface  self
	 */
	public function addTo($mail, $name = null);

	/**
	 * @return sly_Mail_Interface  self
	 */
	public function clearTo();

	/**
	 * @param  string $mail        the address
	 * @param  string $name        an optional name
	 * @return sly_Mail_Interface  self
	 */
	public function setFrom($mail, $name = null);

	/**
	 * @param  string $subject     the new subject
	 * @return sly_Mail_Interface  self
	 */
	public function setSubject($subject);

	/**
	 * @param  string $body        the new body
	 * @return sly_Mail_Interface  self
	 */
	public function setBody($body);

	/**
	 * @param  string $contentType  the new content type
	 * @return sly_Mail_Interface   self
	 */
	public function setContentType($contentType);

	/**
	 * @param  string $charset     the new charset
	 * @return sly_Mail_Interface  self
	 */
	public function setCharset($charset);

	/**
	 * @param  string $field       the header field (like 'x-foo')
	 * @param  string $value       the header value (when empty, the corresponding header will be removed)
	 * @return sly_Mail_Interface  self
	 */
	public function setHeader($field, $value);

	/**
	 * @throws sly_Mail_Exception  when something is wrong
	 * @return boolean             always true
	 */
	public function send();
}
