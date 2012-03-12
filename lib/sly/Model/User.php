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
 * Business Model Klasse für Benutzer
 *
 * @author  christoph@webvariants.de
 * @ingroup model
 */
class sly_Model_User extends sly_Model_Base_Id {
	protected $name;          ///< string
	protected $description;   ///< string
	protected $login;         ///< string
	protected $psw;           ///< string
	protected $status;        ///< int
	protected $rights;        ///< string
	protected $createuser;    ///< string
	protected $updateuser;    ///< string
	protected $createdate;    ///< int
	protected $updatedate;    ///< int
	protected $lasttrydate;   ///< int
	protected $timezone;      ///< string
	protected $revision;      ///< int

	protected $startpage;     ///< string
	protected $backendLocale; ///< string
	protected $isAdmin;       ///< boolean

	protected $_attributes = array(
		'name' => 'string', 'description' => 'string', 'login' => 'string', 'psw' => 'string',
		'status' => 'int', 'rights' => 'string', 'updateuser' => 'string',
		'updatedate' => 'int', 'createuser' => 'string', 'createdate' => 'int', 'lasttrydate' => 'int',
		'timezone' => 'string', 'revision' => 'int'
	); ///< array

	/**
	 * @param array $params
	 */
	public function __construct($params = array()) {
		parent::__construct($params);
		$this->evalRights();
	}

	protected function evalRights() {
		$config = sly_Core::config();

		$rightsArray   = array_filter(explode('#', $this->getRights()));
		$this->startpage     = $config->get('START_PAGE');
		$this->backendLocale = sly_Core::getDefaultLocale();
		$this->isAdmin       = false;

		foreach ($rightsArray as $right) {
			if ($right == 'admin[]') {
				$this->isAdmin = true;
			}
			elseif (substr($right, 0, 10) == 'startpage[') {
				$this->startpage = substr($right, 10, -1);
			}
			elseif (substr($right, 0, 8) == 'be_lang[') {
				$this->backendLocale = substr($right, 8, -1);
			}
		}
	}

	public function setName($name)               { $this->name        = $name;        } ///< @param string $name
	public function setDescription($description) { $this->description = $description; } ///< @param string $description
	public function setLogin($login)             { $this->login       = $login;       } ///< @param string $login

	/**
	 * Sets a password into the user model.
	 *
	 * This method is doing the hashing. Mage sure the createdate is set before.
	 *
	 * @param string $password  The password (plain)
	 */
	public function setPassword($password) {
		$this->setHashedPassword(sly_Util_User::getPasswordHash($this, $password));
	}

	/**
	 * Sets a password into the user model, where hashing is already done
	 *
	 * @param string $psw  The hashed password
	 */
	public function setHashedPassword($psw) {
		$this->psw = $psw;
	}

	public function setStatus($status)           { $this->status      = (int) $status;      } ///< @param int    $status
	public function setCreateDate($createdate)   { $this->createdate  = (int) $createdate;  } ///< @param int    $createdate
	public function setUpdateDate($updatedate)   { $this->updatedate  = (int) $updatedate;  } ///< @param int    $updatedate
	public function setCreateUser($createuser)   { $this->createuser  = $createuser;        } ///< @param string $createuser
	public function setUpdateUser($updateuser)   { $this->updateuser  = $updateuser;        } ///< @param string $updateuser
	public function setLastTryDate($lasttrydate) { $this->lasttrydate = (int) $lasttrydate; } ///< @param int    $lasttrydate
	public function setTimeZone($timezone)       { $this->timezone    = $timezone;          } ///< @param string $timezone
	public function setRevision($revision)       { $this->revision    = (int) $revision;    } ///< @param int    $revision

	public function getName()        { return $this->name;        } ///< @return string
	public function getDescription() { return $this->description; } ///< @return string
	public function getLogin()       { return $this->login;       } ///< @return string
	public function getPassword()    { return $this->psw;         } ///< @return string
	public function getStatus()      { return $this->status;      } ///< @return int
	public function getRights()      { return $this->rights;      } ///< @return string
	public function getCreateDate()  { return $this->createdate;  } ///< @return int
	public function getUpdateDate()  { return $this->updatedate;  } ///< @return int
	public function getCreateUser()  { return $this->createuser;  } ///< @return string
	public function getUpdateUser()  { return $this->updateuser;  } ///< @return string
	public function getLastTryDate() { return $this->lasttrydate; } ///< @return int
	public function getTimeZone()    { return $this->timezone;    } ///< @return string
	public function getRevision()    { return $this->revision;    } ///< @return int

	// Wenn Rechte gesetzt werden, müssen wir etwas mehr arbeiten.

	/**
	 * @param string $rights
	 */
	public function setRights($rights) {
		$this->rights = '#'.trim($rights, '#').'#';
		$this->evalRights();
	}

	// Hilfsfunktionen für abgeleitete Attribute

	public function getStartPage()     { return $this->startpage;     } ///< @return string
	public function getBackendLocale() { return $this->backendLocale; } ///< @return string
	public function isAdmin()          { return $this->isAdmin;       } ///< @return boolean

	/**
	 * @return array
	 */
	public function getAllowedCategories() {
		preg_match_all('/#csw\[(\d+)\]/', $this->getRights(), $matches);
		return isset($matches[1]) ? $matches[1] : array();
	}

	/**
	 * @return array
	 */
	public function getAllowedMediaCategories() {
		preg_match_all('/#media\[(\d+)\]/', $this->getRights(), $matches);
		return isset($matches[1]) ? $matches[1] : array();
	}

	/**
	 * @return array
	 */
	public function getAllowedModules() {
		preg_match_all('/#module\[(.+?)\]/', $this->getRights(), $matches);
		return isset($matches[1]) ? $matches[1] : array();
	}

	/**
	 * @return array
	 */
	public function getAllowedCLangs() {
		$allowedLanguages = array();
		foreach (sly_Util_Language::findAll(true) as $language) {
			if ($this->isAdmin() || $this->hasRight('language', 'access', $language)) {
				$allowedLanguages[] = $language;
			}
		}
		return $allowedLanguages;
	}

	/**
	 * @param  string $context
	 * @param  string $right
	 * @return boolean
	 */
	public function hasRight($context, $right, $value = true) {
		return sly_Authorisation::hasPermission($this->getId(), $context, $right, $value);
	}

	// Misc

	/**
	 * @return int
	 */
	public function delete() {
		return sly_Service_Factory::getUserService()->delete(array('id' => $this->id));
	}

	/**
	 * @return boolean
	 */
	public function hasStructureRight() {
		return $this->isAdmin() || sly_Util_Category::canReadCategory($this, 0);
	}
}
