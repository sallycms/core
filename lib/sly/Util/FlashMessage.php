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
 * @ingroup util
 */
class sly_Util_FlashMessage {
	protected $name;      ///< string
	protected $autoStore; ///< boolean
	protected $messages;  ///< array

	const TYPE_INFO    = 'info';
	const TYPE_WARNING = 'warning';

	/**
	 * @param string $name
	 */
	public function __construct($name) {
		$this->name      = $name;
		$this->autoStore = false;

		$this->clear();
	}

	/**
	 * set auto store
	 *
	 * @param boolean $flag
	 */
	public function setAutoStore($flag) {
		$this->autoStore = (boolean) $flag;
	}

	/**
	 * read from session
	 *
	 * @param string $name
	 */
	public static function readFromSession($name) {
		$data = sly_Util_Session::get('flashmsg_'.$name, 'array', null);
		$msg  = new self($name);

		if (is_array($data)) {
			$msg->messages = $data;
		}

		return $msg;
	}

	/**
	 * store
	 */
	public function store() {
		sly_Util_Session::set('flashmsg_'.$this->name, $this->messages);
	}

	/**
	 * remove from session
	 */
	public function removeFromSession() {
		sly_Util_Session::reset('flashmsg_'.$this->name);
	}

	/**
	 * clear
	 *
	 * @param string $type  (optional)
	 */
	public function clear($type = null) {
		if ($type === null) {
			$this->messages = array();
		}
		elseif (isset($this->messages[$type])) {
			$this->messages[$type] = array();
		}

		if ($this->autoStore) $this->store();
	}

	/**
	 * add message
	 *
	 * @param string $type
	 * @param string $msg
	 */
	public function addMessage($type, $msg) {
		$this->messages[$type][] = $msg;
		if ($this->autoStore) $this->store();
	}

	/**
	 * get messages
	 *
	 * @param string $type  (optional)
	 */
	public function getMessages($type = null) {
		return $type === null ? $this->messages : (isset($this->messages[$type]) ? $this->messages[$type] : array());
	}

	/**
	 * append message
	 *
	 * @param string $type
	 * @param string $msg
	 */
	public function appendMessage($type, $msg) {
		if (!empty($this->messages[$type])) {
			$cur = end($this->messages[$type]);
			$idx = key($this->messages[$type]);

			// Store multiple lines here as an array and let the view decide
			// whether or not to implode them with "<br>" or "\n".
			if (!is_array($cur)) {
				$cur = array($cur);
			}

			$cur[] = $msg;
			$this->messages[$type][$idx] = $cur;
		}
		else {
			$this->addMessage($type, $msg);
		}

		if ($this->autoStore) $this->store();
	}

	/**
	 * prepend message
	 *
	 * @param string $type
	 * @param string $msg
	 * @param bool   $asFullMessage
	 */
	public function prependMessage($type, $msg, $asFullMessage) {
		if (!empty($this->messages[$type])) {
			if ($asFullMessage) {
				array_unshift($this->messages[$type], $msg);
			}
			else {
				$cur = reset($this->messages[$type]);

				// Store multiple lines here as an array and let the view decide
				// whether or not to implode them with "<br>" or "\n".
				if (!is_array($cur)) {
					$cur = array($cur);
				}

				array_unshift($cur, $msg);
				$this->messages[$type][0] = $cur;
			}
		}
		else {
			$this->addMessage($type, $msg);
		}

		if ($this->autoStore) $this->store();
	}

	/**
	 * add info
	 *
	 * @param string $msg
	 */
	public function addInfo($msg) {
		return $this->addMessage(self::TYPE_INFO, $msg);
	}

	/**
	 * append info
	 *
	 * @param string $msg
	 */
	public function appendInfo($msg) {
		return $this->appendMessage(self::TYPE_INFO, $msg);
	}

	/**
	 * prepend info
	 *
	 * @param string $msg
	 * @param bool   $asFullMessage
	 */
	public function prependInfo($msg, $asFullMessage) {
		return $this->prependMessage(self::TYPE_INFO, $msg, $asFullMessage);
	}

	/**
	 * add warning
	 *
	 * @param string $msg
	 */
	public function addWarning($msg) {
		return $this->addMessage(self::TYPE_WARNING, $msg);
	}

	/**
	 * append warning
	 *
	 * @param string $msg
	 */
	public function appendWarning($msg) {
		return $this->appendMessage(self::TYPE_WARNING, $msg);
	}

	/**
	 * prepend warning
	 *
	 * @param string $msg
	 * @param bool   $asFullMessage
	 */
	public function prependWarning($msg, $asFullMessage) {
		return $this->prependMessage(self::TYPE_WARNING, $msg, $asFullMessage);
	}
}
