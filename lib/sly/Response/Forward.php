<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class sly_Response_Forward implements sly_Response_Action {
	protected $target;
	protected $action;

	public function __construct($targetController, $action) {
		if (!($targetController instanceof sly_Controller_Base) && !is_string($targetController)) {
			throw new sly_Exception(t('forward_target_must_be_controller_or_string', gettype($targetController)));
		}

		$this->target = $targetController;
		$this->action = $action;
	}

	public function execute(sly_Dispatcher $dispatcher) {
		return $dispatcher->dispatch($this->target, $this->action);
	}
}
