<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
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
	protected $name;
	protected $description;
	protected $login;
	protected $psw;
	protected $status; // TODO: Ist in der Datenbank noch ein VARCHAR...
	protected $rights;
	protected $createuser;
	protected $updateuser;
	protected $createdate;
	protected $updatedate;
	protected $lasttrydate;
	protected $timezone;
	protected $revision;

	protected $startpage;
	protected $backendLocale;
	protected $rightsArray;

	protected $_attributes = array(
		'name' => 'string', 'description' => 'string', 'login' => 'string', 'psw' => 'string',
		'status' => 'int', 'rights' => 'string', 'updateuser' => 'string',
		'updatedate' => 'int', 'createuser' => 'string', 'createdate' => 'int', 'lasttrydate' => 'int',
		'timezone' => 'string', 'revision' => 'int'
	);

	public function __construct($params = array()) {
		parent::__construct($params);
		$this->evalRights();
	}

	protected function evalRights() {
		$config = sly_Core::config();

		$this->rightsArray   = array_filter(explode('#', $this->getRights()));
		$this->startpage     = $config->get('START_PAGE');
		$this->backendLocale = $config->get('LANG');
		$this->isAdmin       = false;

		foreach ($this->rightsArray as $right) {
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

	public function setName($name)               { $this->name        = $name;         }
	public function setDescription($description) { $this->description = $description;  }
	public function setLogin($login)             { $this->login       = $login;        }

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

	public function setStatus($status)           { $this->status      = (int) $status; }
	public function setCreateDate($createdate)   { $this->createdate  = $createdate;   }
	public function setUpdateDate($updatedate)   { $this->updatedate  = $updatedate;   }
	public function setCreateUser($createuser)   { $this->createuser  = $createuser;   }
	public function setUpdateUser($updateuser)   { $this->updateuser  = $updateuser;   }
	public function setLastTryDate($lasttrydate) { $this->lasttrydate = $lasttrydate;  }
	public function setTimeZone($timezone)       { $this->timezone    = $timezone;     }
	public function setRevision($revision)       { $this->revision    = $revision;     }

	public function getName()        { return $this->name;        }
	public function getDescription() { return $this->description; }
	public function getLogin()       { return $this->login;       }
	public function getPassword()    { return $this->psw;         }
	public function getStatus()      { return $this->status;      }
	public function getRights()      { return $this->rights;      }
	public function getCreateDate()  { return $this->createdate;  }
	public function getUpdateDate()  { return $this->updatedate;  }
	public function getCreateUser()  { return $this->createuser;  }
	public function getUpdateUser()  { return $this->updateuser;  }
	public function getLastTryDate() { return $this->lasttrydate; }
	public function getTimeZone()    { return $this->timezone;    }
	public function getRevision()    { return $this->revision;    }

	// Wenn Rechte gesetzt werden, müssen wir etwas mehr arbeiten.

	public function setRights($rights) {
		$this->rights = '#'.trim($rights, '#').'#';
		$this->evalRights();
	}

	// Hilfsfunktionen für abgeleitete Attribute

	public function getStartPage()     { return $this->startpage;     }
	public function getBackendLocale() { return $this->backendLocale; }
	public function isAdmin()          { return $this->isAdmin;       }

	public function getAllowedCategories() {
		preg_match_all('/#csw\[(\d+)\]/', $this->getRights(), $matches);
		return isset($matches[1]) ? $matches[1] : array();
	}

	public function getAllowedMediaCategories() {
		preg_match_all('/#media\[(\d+)\]/', $this->getRights(), $matches);
		return isset($matches[1]) ? $matches[1] : array();
	}

	public function getAllowedModules() {
		preg_match_all('/#module\[(.+?)\]/', $this->getRights(), $matches);
		return isset($matches[1]) ? $matches[1] : array();
	}

	public function getAllowedCLangs() {
		preg_match_all('/#clang\[(\d+)\]/', $this->getRights(), $matches);
		return isset($matches[1]) ? $matches[1] : array();
	}

	public function getRightsAsArray() {
		return $this->rightsArray;
	}

	public function hasRight($right) {
		return in_array($right, $this->rightsArray);
	}

	public function hasPerm($right) {
		return $this->hasRight($right);
	}

	public function toggleRight($right, $switch = true) {
		$right = trim($right, '#');

		foreach ($this->rightsArray as $idx => $iRight) {
			if ($right == $iRight && !$switch) {
				unset($this->rightsArray[$idx]);
				break;
			}
		}

		if ($switch) {
			$this->rightsArray[] = $right;
		}

		$this->rightsArray = array_unique($this->rightsArray);
		$this->setRights(implode('#', $this->rightsArray));
	}

	// Misc

	public function delete() {
		return sly_Service_Factory::getUserService()->delete(array('id' => $this->id));
	}

	public function hasCategoryRight($categoryID) {
		$categoryID = (int) $categoryID;
		return $this->isAdmin() || $this->hasRight('csw[0]') || $this->hasRight('csr['.$categoryID.']') || $this->hasRight('csw['.$categoryID.']');
	}

	public function hasStructureRight() {
		return $this->isAdmin() || strpos($this->rights, '#csw[') !== false || strpos($this->rights, '#csr[') !== false;
	}
}
