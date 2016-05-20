<?php
/*
 * Copyright (c) 2014, webvariants GmbH & Co. KG, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * @since  0.9
 * @author zozi@webvariants.de
 */
interface sly_Slice_Renderer {
	public function renderInput(sly_Model_ISlice $slice, $dataIndex);
	public function renderOutput(sly_Model_ISlice $slice);
}
