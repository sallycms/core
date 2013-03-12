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
 * greetings to the symfony project
 *
 * @ingroup core
 */
interface sly_ContainerAwareInterface {

    public function setContainer(sly_Container $container = null);

}