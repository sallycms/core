<?php

class sly_Test_Configuration_Reader implements sly_Configuration_Reader {
	public function readLocal() {
		return sly_Util_YAML::load(SLY_CONFIGFOLDER.DIRECTORY_SEPARATOR.'sly_local.yml');
	}

	public function readProject() {
		return sly_Util_YAML::load(SLY_CONFIGFOLDER.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'sly_project.yml');
	}

	public function setPersistence($persistence) {
		// ignore
	}
}
