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
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Category extends sly_Service_Model_Base {
	protected $tablename = 'article'; ///< string

	/**
	 * @param  array $params
	 * @return sly_Model_Category
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Category($params);
	}

	/**
	 * @param  sly_Model_Category $cat
	 * @return sly_Model_Category
	 */
	protected function update(sly_Model_Category $cat) {
		$persistence = sly_DB_Persistence::getInstance();
		$persistence->update($this->getTableName(), $cat->toHash(), $cat->getPKHash());
		return $cat;
	}

	/**
	 * @param  int $id
	 * @param  int $clang
	 * @return sly_Model_Category
	 */
	public function findById($id, $clang = null) {
		if ($clang === null || $clang === false) $clang = sly_Core::getCurrentClang();

		$key      = $id.'_'.$clang;
		$category = sly_Core::cache()->get('sly.category', $key, null);

		if ($category === null) {
			$category = $this->findOne(array('id' => (int) $id, 'clang' => $clang));

			if ($category !== null) {
				sly_Core::cache()->set('sly.category', $key, $category);
			}
		}

		return $category;
	}

	/**
	 * @param  mixed  $where
	 * @param  string $group
	 * @param  string $order
	 * @param  int    $offset
	 * @param  int    $limit
	 * @param  string $having
	 * @return array
	 */
	public function find($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null) {
		if (is_array($where)) {
			$where['startpage'] = 1;
		}
		else {
			$where = array('startpage' => 1);
		}

		return parent::find($where, $group, $order, $offset, $limit, $having);
	}

	/**
	 * @throws sly_Exception
	 * @param  int    $parentID
	 * @param  string $name
	 * @param  int    $status
	 * @param  int    $position
	 * @return int
	 */
	public function add($parentID, $name, $status = 0, $position = -1) {
		$db       = sly_DB_Persistence::getInstance();
		$parentID = (int) $parentID;
		$position = (int) $position;
		$status   = (int) $status;
		$clang    = sly_Core::getCurrentClang(); // any existing clang ID will be sufficient

		// Parent validieren

		if ($parentID !== 0 && !sly_Util_Category::exists($parentID)) {
			throw new sly_Exception('Parent category does not exist.');
		}

		// Artikeltyp vom Startartikel der jeweiligen Sprache vererben

		$startpageTypes = array();

		if ($parentID !== 0) {
			$db->select('article', 'clang, type', array('id' => $parentID, 'startpage' => 1));
			foreach ($db as $row) {
				$startpageTypes[$row['clang']] = $row['type'];
			}
		}

		// Position validieren

		$maxPos   = $db->magicFetch('article', 'MAX(catprior)', 're_id = '.$parentID.' AND catprior <> 0 AND clang = '.$clang) + 1;
		$position = ($position <= 0 || $position > $maxPos) ? $maxPos : $position;

		// Pfad ermitteln

		if ($parentID !== 0) {
			$path  = $db->magicFetch('article', 'path', array('id' => $parentID, 'startpage' => 1, 'clang' => $clang));
			$path .= $parentID.'|';
		}
		else {
			$path = '|';
		}

		// Die ID ist für alle Sprachen gleich und entspricht einfach der aktuell
		// höchsten plus 1.

		$newID = $db->magicFetch('article', 'MAX(id)') + 1;

		// Entferne alle Kategorien aus dem Cache, die nach der aktuellen kommen und
		// daher ab Ende dieser Funktion eine neue Positionsangabe haben.

		$cache = sly_Core::cache();

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			$db->select('article', 'id', 'catprior > '.$position.' AND startpage = 1 AND clang = '.$clangID.' AND re_id = '.$parentID);

			foreach ($db as $row) {
				$cache->delete('sly.category', $row['id'].'_'.$clangID);
			}
		}

		// Bevor wir die neuen Datensätze einfügen, machen wir in den sortierten
		// Listen (je eine pro Sprache) Platz, indem wir alle Kategorien, deren
		// Priorität größergleich der Priorität der neuen Kategorie ist, um eine
		// Position nach unten schieben.

		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		$db->query(
			'UPDATE '.$prefix.'article SET catprior = catprior + 1 '.
			'WHERE re_id = '.$parentID.' AND catprior <> 0 AND catprior >= '.$position.' '.
			'ORDER BY catprior ASC'
		);

		// Kategorie in allen Sprachen anlegen

		$defaultType = sly_Core::getDefaultArticleType();
		$dispatcher  = sly_Core::dispatcher();

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			if (!empty($startpageTypes[$clangID])) {
				$type = $startpageTypes[$clangID];
			}
			else {
				$type = $defaultType;
			}

			$cat = new sly_Model_Category(array(
				        'id' => $newID,
				     're_id' => $parentID,
				      'name' => $name,
				   'catname' => $name,
				  'catprior' => $position,
				'attributes' => '',
				 'startpage' => 1,
				     'prior' => 1,
				      'path' => $path,
				    'status' => $status ? 1 : 0,
				      'type' => $type,
				     'clang' => $clangID,
				  'revision' => 0
			));

			$cat->setUpdateColumns();
			$cat->setCreateColumns();
			$db->insert($this->tablename, array_merge($cat->getPKHash(), $cat->toHash()));

			$cache->delete('sly.category.list', $parentID.'_'.$clangID.'_0');
			$cache->delete('sly.category.list', $parentID.'_'.$clangID.'_1');

			// System benachrichtigen

			$dispatcher->notify('SLY_CAT_ADDED', $newID, array(
				're_id'    => $parentID,
				'clang'    => $clangID,
				'name'     => $name,
				'position' => $position,
				'path'     => $path,
				'status'   => $status,
				'type'     => $type
			));
		}

		return $newID;
	}

	/**
	 * @throws sly_Exception
	 * @param  int    $categoryID
	 * @param  int    $clangID
	 * @param  string $name
	 * @param  mixed  $position
	 * @return boolean
	 */
	public function edit($categoryID, $clangID, $name, $position = false) {
		$categoryID = (int) $categoryID;
		$clangID    = (int) $clangID;
		$db         = sly_DB_Persistence::getInstance();
		$cache      = sly_Core::cache();

		// Kategorie validieren
		$cat = $this->findById($categoryID, $clangID);

		if ($cat === null) {
			throw new sly_Exception(t('category_doesnt_exist'));
		}

		// Kategorie selbst updaten
		$cat->setCatname($name);
		$cat->setUpdateColumns();
		$this->update($cat);

		// Cache sicherheitshalber schon einmal leeren
		$cache->delete('sly.category', $categoryID.'_'.$clangID);

		// Name der Kategorie in den Kindern ändern
		$where = array('re_id' => $categoryID, 'startpage' => 0, 'clang' => $clangID);
		$db->update('article', array('catname' => $name), $where);

		// Kinder abrufen, um für jedes Kind den Cache zu leeren.
		$db->select('article', 'id', $where);

		foreach ($db as $child) {
			rex_deleteCacheArticle($child['id'], $clangID);
		}

		// Kategorie verschieben, wenn nötig
		if ($position !== false && $position != $cat->getCatprior()) {
			$parentID = $cat->getParentId();
			$oldPrio  = $cat->getCatprior();
			$position = (int) $position;

			$where   = 're_id = '.$parentID.' AND catprior <> 0 AND clang = '.$clangID;
			$maxPrio = $db->magicFetch('article', 'MAX(catprior)', $where);
			$newPrio = ($position <= 0 || $position > $maxPrio) ? $maxPrio : $position;

			// Nur aktiv werden, wenn sich auch etwas geändert hat.
			if ($newPrio != $oldPrio) {
				$prefix      = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
				$relation    = $newPrio < $oldPrio ? '+' : '-';
				list($a, $b) = $newPrio < $oldPrio ? array($newPrio, $oldPrio) : array($oldPrio, $newPrio);

				// alle anderen entsprechend verschieben
				$db->query(
					'UPDATE '.$prefix.'article SET catprior = catprior '.$relation.' 1 '.
					'WHERE catprior BETWEEN '.$a.' AND '.$b.' AND '.$where
				);

				// eigene neue Position speichern
				$cat->setCatprior($newPrio);
				$this->update($cat);

				// alle Kategorien in dieser Ebene aus dem Cache entfernen
				$db->select('article', 'id', 're_id = '.$parentID.' AND clang = '.$clangID.' AND catprior <> 0');

				foreach ($db as $row) {
					$cache->delete('sly.category', $row['id'].'_'.$clangID);
				}

				$cache->delete('sly.category.list', $parentID.'_'.$clangID.'_0');
				$cache->delete('sly.category.list', $parentID.'_'.$clangID.'_1');
			}
		}

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_CAT_UPDATED', $cat);

		return true;
	}

	/**
	 * @throws sly_Exception
	 * @param  int $categoryID
	 * @return boolean
	 */
	public function delete($categoryID) {
		$categoryID = (int) $categoryID;
		$db         = sly_DB_Persistence::getInstance();
		$cache      = sly_Core::cache();
		$cat        = $this->findById($categoryID);

		// Prüfen ob die Kategorie existiert
		if ($cat === null) {
			throw new sly_Exception(t('category_doesnt_exist'));
		}

		// Prüfen ob die Kategorie noch Kinder (Kategorien oder Artikel) besitzt
		$where    = array('re_id' => $categoryID);
		$children = $db->magicFetch('article', 'COUNT(*)', $where);

		if ($children > 0) {
			throw new sly_Exception('Category has still content and therefore cannot be deleted.');
		}

		// Nachbarkategorien neu positionieren
		$parent = $cat->getParentId();
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			$iCat     = $this->findById($categoryID, $clangID);
			$catprior = $iCat->getCatprior();

			$db->query(
				'UPDATE '.$prefix.'article SET catprior = catprior - 1 '.
				'WHERE re_id = '.$parent.' AND catprior > '.$catprior.' '.
				'AND catprior <> 0 AND clang = '.$clangID
			);

			$cache->delete('sly.category', $categoryID.'_'.$clangID);
			$cache->delete('sly.category.list', $parent.'_'.$clangID.'_0');
			$cache->delete('sly.category.list', $parent.'_'.$clangID.'_1');

			// Cache leeren
			$db->select('article', 'id', 're_id = '.$parent.' AND catprior >= '.$catprior.' AND clang = '.$clangID);

			foreach ($db as $row) {
				$cache->delete('sly.category', $row['id'].'_'.$clangID);
			}
		}

		// Kategorie löschen
		$return = rex_deleteArticle($categoryID);
		if (!$return['state']) throw new sly_Exception($return['message']);

		// Event auslösen
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_CAT_DELETED', $cat);

		return true;
	}

	/**
	 * @throws sly_Exception
	 * @param  int $categoryID
	 * @param  int $clangID
	 * @param  int $newStatus
	 * @return boolean
	 */
	public function changeStatus($categoryID, $clangID, $newStatus = null) {
		$categoryID = (int) $categoryID;
		$clangID    = (int) $clangID;
		$cat        = $this->findById($categoryID, $clangID);

		// Prüfen ob die Kategorie existiert
		if ($cat === null) {
			throw new sly_Exception(t('category_doesnt_exist'));
		}

		$stati     = $this->getStati();
		$re_id     = $cat->getParentId();
		$oldStatus = $cat->getStatus();

		// Status wurde nicht von außen vorgegeben,
		// => zyklisch auf den nächsten weiterschalten
		if ($newStatus === null) {
			$newStatus = ($oldStatus + 1) % count($stati);
		}

		// Kategorie updaten
		$cat->setStatus($newStatus);
		$cat->setUpdateColumns();
		$this->update($cat);

		// Cache leeren
		rex_deleteCacheArticle($categoryID, $clangID);

		$cache = sly_Core::cache();
		$cache->delete('sly.category', $categoryID.'_'.$clangID);
		$cache->delete('sly.article', $categoryID.'_'.$clangID);
		$cache->delete('sly.category.list', $re_id.'_'.$clangID.'_0');
		$cache->delete('sly.article.list', $categoryID.'_'.$clangID.'_0');
		$cache->delete('sly.category.list', $re_id.'_'.$clangID.'_1');
		$cache->delete('sly.article.list', $categoryID.'_'.$clangID.'_1');

		// Event auslösen
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_CAT_STATUS', $cat);

		return true;
	}

	/**
	 * @return array
	 */
	public function getStati() {
		static $stati;

		if (!$stati) {
			$stati = array(
				// Name, CSS-Klasse
				array(t('status_offline'), 'rex-offline'),
				array(t('status_online'),  'rex-online')
			);

			$stati = sly_Core::dispatcher()->filter('SLY_CAT_STATUS_TYPES', $stati);
		}

		return $stati;
	}

	/**
	 * return all categories of a parent
	 *
	 * @param  int     $parentId
	 * @param  boolean $ignore_offlines
	 * @param  int     $clang
	 * @return array
	 */
	public function findByParentId($parentId, $ignore_offlines = false, $clang = null) {
		$parentId = (int) $parentId;

		if ($clang === null) {
			$clang = sly_Core::getCurrentClang();
		}

		$clang     = (int) $clang;
		$namespace = 'sly.category.list';
		$key       = $parentId.'_'.$clang.'_'.($ignore_offlines ? '1' : '0');
		$clist     = sly_Core::cache()->get($namespace, $key, null);

		if ($clist === null) {
			$sql = sly_DB_Persistence::getInstance();
			$sql->select($this->getTableName(), 'id', array('startpage' => 1, 're_id' => $parentId, 'clang' => $clang), null, 'catprior, name ASC');
			$clist = array();
			foreach($sql as $row) {
				$clist[] = $row['id'];
			}
			sly_Core::cache()->set($namespace, $key, $clist);
		}

		$catlist = array();

		foreach ($clist as $var) {
			$category = $this->findById($var, $clang);

			if ($category && (!$ignore_offlines || ($ignore_offlines && $category->isOnline()))) {
				$catlist[] = $category;
			}
		}

		return $catlist;
	}
}
