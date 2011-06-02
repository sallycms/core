<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

interface sly_Mail_Interface {
	public function addTo($mail, $name = null);
	public function clearTo();
	public function setFrom($mail, $name = null);
	public function setSubject($subject);
	public function setBody($body);
	public function setContentType($contentType);
	public function setCharset($charset);
	public function setHeader($field, $value);
	public function send();
}
