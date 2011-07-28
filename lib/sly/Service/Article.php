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
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Article extends sly_Service_Model_Base {
	protected $tablename = 'article';

	protected function makeInstance(array $params) {
		return new sly_Model_Article($params);
	}

	protected function update(sly_Model_Article $article) {
		$persistence = sly_DB_Persistence::getInstance();
		$persistence->update($this->getTableName(), $article->toHash(), $article->getPKHash());
		return $article;
	}

	public function findById($id, $clang = null) {
		if ($clang === null || $clang === false) $clang = sly_Core::getCurrentClang();

		$id = (int) $id;

		if ($id === 0) {
			return null;
		}

		$key     = $id.'_'.$clang;
		$article = sly_Core::cache()->get('sly.article', $key, null);

		if ($article === null) {
			$article = $this->findOne(array('id' => $id, 'clang' => $clang));

			if ($article !== null) {
				sly_Core::cache()->set('sly.article', $key, $article);
			}
		}

		return $article;
	}

	public function add($categoryID, $name, $status, $position = -1) {
		$db       = sly_DB_Persistence::getInstance();
		$parentID = (int) $categoryID;
		$position = (int) $position;
		$status   = (int) $status;

		// Parent validieren

		if ($parentID !== 0 && !sly_Util_Category::exists($parentID)) {
			throw new sly_Exception('Parent category does not exist.');
		}

		// Artikeltyp vom Startartikel der jeweiligen Sprache vererben
		// Catname ermitteln

		$types    = array();
		$catnames = array();

		if ($parentID !== 0) {
			$db->select('article', 'clang, type', array('id' => $parentID, 'startpage' => 1));
			foreach ($db as $row) {
				$types[$row['clang']] = $row['type'];
			}

			$db->select('article', 'clang, catname', 'id = '.$parentID.' AND catprior <> 0 AND startpage = 1');
			foreach ($db as $row) {
				$catnames[$row['clang']] = $row['catname'];
			}
		}

		// Position validieren

		$where    = '((re_id = '.$parentID.' AND catprior = 0) OR (id = '.$parentID.'))';
		$maxPos   = $db->magicFetch('article', 'MAX(prior)', $where) + 1;
		$position = ($position <= 0 || $position > $maxPos) ? $maxPos : $position;

		// Pfad ermitteln

		if ($parentID !== 0) {
			$path = $db->magicFetch('article', 'path', array('id' => $parentID, 'startpage' => 1));
			$path = $path.$parentID.'|';
		}
		else {
			$path = '|';
		}

		// Die ID ist für alle Sprachen gleich und entspricht einfach der aktuell
		// höchsten plus 1.

		$newID = $db->magicFetch('article', 'MAX(id)') + 1;

		// Entferne alle Artikel aus dem Cache, die nach dem aktuellen kommen und
		// daher ab Ende dieser Funktion eine neue Positionsangabe haben.

		$cache = sly_Core::cache();

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			$db->select('article', 'id', 'prior > '.$position.' AND startpage = 0 AND clang = '.$clangID.' AND re_id = '.$parentID);

			foreach ($db as $row) {
				$cache->delete('sly.article', $row['id'].'_'.$clangID);
			}
		}

		// Bevor wir die neuen Datensätze einfügen, machen wir in den sortierten
		// Listen (je eine pro Sprache) Platz, indem wir alle Artikel, deren
		// Priorität größergleich der Priorität des neuen Artikels ist, um eine
		// Position nach unten schieben.

		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		$db->query(
			'UPDATE '.$prefix.'article SET prior = prior + 1 '.
			'WHERE ((re_id = '.$parentID.' AND catprior = 0) OR id = '.$parentID.') AND prior >= '.$position.' '.
			'ORDER BY prior ASC'
		);

		// Artikel in allen Sprachen anlegen

		$defaultType = sly_Core::getDefaultArticleType();
		$dispatcher  = sly_Core::dispatcher();

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			$type    = !empty($types[$clangID]) ? $types[$clangID] : $defaultType;
			$article = new sly_Model_Article(array(
				        'id' => $newID,
				     're_id' => $parentID,
				      'name' => $name,
				   'catname' => !empty($catnames[$clangID]) ? $catnames[$clangID] : '',
				  'catprior' => 0,
				'attributes' => '',
				 'startpage' => 0,
				     'prior' => $position,
				      'path' => $path,
				    'status' => $status ? 1 : 0,
				      'type' => $type,
				     'clang' => $clangID,
				  'revision' => 0
			));

			$article->setUpdateColumns();
			$article->setCreateColumns();
			$db->insert($this->tablename, array_merge($article->getPKHash(), $article->toHash()));

			$cache->delete('sly.article.list', $parentID.'_'.$clangID.'_0');
			$cache->delete('sly.article.list', $parentID.'_'.$clangID.'_1');

			// System benachrichtigen

			$dispatcher->notify('SLY_ART_ADDED', $newID, array(
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

	public function edit($articleID, $clangID, $name, $position = false) {
		$articleID = (int) $articleID;
		$clangID   = (int) $clangID;
		$db        = sly_DB_Persistence::getInstance();
		$cache     = sly_Core::cache();

		// Artikel validieren
		$article = $this->findById($articleID, $clangID);

		if ($article === null) {
			throw new sly_Exception(t('no_such_article'));
		}

		// Artikel selbst updaten
		$article->setName($name);
		$article->setUpdateColumns();
		$this->update($article);

		// Cache sicherheitshalber schon einmal leeren
		$cache->delete('sly.article', $articleID.'_'.$clangID);

		// Kategorie verschieben, wenn nötig
		if ($position !== false && $position != $article->getPrior()) {
			$parent = $article->getParentId();
			$oldPrio  = $article->getPrior();
			$position = (int) $position;

			$where   = '((re_id = '.$parent.' AND catprior = 0) OR id = '.$parent.') AND clang = '.$clangID;
			$maxPrio = $db->magicFetch('article', 'MAX(prior)', $where);
			$newPrio = ($position <= 0 || $position > $maxPrio) ? $maxPrio : $position;

			// Nur aktiv werden, wenn sich auch etwas geändert hat.
			if ($newPrio != $oldPrio) {
				$prefix      = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
				$relation    = $newPrio < $oldPrio ? '+' : '-';
				list($a, $b) = $newPrio < $oldPrio ? array($newPrio, $oldPrio) : array($oldPrio, $newPrio);

				// alle anderen entsprechend verschieben
				$db->query(
					'UPDATE '.$prefix.'article SET prior = prior '.$relation.' 1 '.
					'WHERE prior BETWEEN '.$a.' AND '.$b.' AND '.$where
				);

				// eigene neue Position speichern
				$article->setPrior($newPrio);
				$this->update($article);

				// alle Artikel in dieser Ebene aus dem Cache entfernen
				$db->select('article', 'id', array('re_id' => $parent, 'clang' => $clangID, 'catprior' => 0));

				foreach ($db as $row) {
					$cache->delete('sly.article', $row['id'].'_'.$clangID);
					$cache->delete('sly.category', $row['id'].'_'.$clangID);
				}

				$cache->delete('sly.article.list', $parent.'_'.$clangID.'_0');
				$cache->delete('sly.article.list', $parent.'_'.$clangID.'_1');
			}
		}

		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_ART_UPDATED', $article);

		return true;
	}

	public function delete($articleID) {
		$articleID = (int) $articleID;
		$db        = sly_DB_Persistence::getInstance();
		$cache     = sly_Core::cache();
		$article   = $this->findById($articleID);

		// Prüfen ob der Artikel existiert
		if ($article === null) {
			throw new sly_Exception(t('no_such_article'));
		}

		// Nachbarartikel neu positionieren
		$parent = $article->getParentId();
		$prefix = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		foreach (sly_Util_Language::findAll(true) as $clangID) {
			$iArticle = $this->findById($articleID, $clangID);
			$prior    = $iArticle->getPrior();
			$where    = 'prior >= '.$prior.' AND re_id = '.$parent.' AND catprior = 0 AND clang = '.$clangID;

			$db->query('UPDATE '.$prefix.'article SET prior = prior - 1 WHERE '.$where);

			$cache->delete('sly.article', $articleID.'_'.$clangID);
			$cache->delete('sly.category', $articleID.'_'.$clangID);
			$cache->delete('sly.article.list', $parent.'_'.$clangID.'_0');
			$cache->delete('sly.category.list', $parent.'_'.$clangID.'_0');
			$cache->delete('sly.article.list', $parent.'_'.$clangID.'_1');
			$cache->delete('sly.category.list', $parent.'_'.$clangID.'_1');

			// Cache leeren
			$db->select('article', 'id', $where);

			foreach ($db as $row) {
				$cache->delete('sly.article', $row['id'].'_'.$clangID);
				$cache->delete('sly.category', $row['id'].'_'.$clangID);
			}
		}

		// Artikel löschen
		$return = rex_deleteArticle($articleID);
		if (!$return['state']) throw new sly_Exception($return['message']);

		// Event auslösen
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_ART_DELETED', $article);

		return true;
	}

	public function changeStatus(sly_Model_Article $article, $newStatus = null) {
		$stati     = $this->getStati();
		$re_id     = $article->getParentId();
		$clang     = $article->getClang();
		$oldStatus = $article->getStatus();

		// Status wurde nicht von außen vorgegeben,
		// => zyklisch auf den nächsten weiterschalten
		if ($newStatus === null) {
			$newStatus = ($oldStatus + 1) % count($stati);
		}

		// Artikel updaten
		$article->setStatus($newStatus);
		$article->setUpdateColumns();
		$this->update($article);

		// Cache leeren
		$cache = sly_Core::cache();
		$cache->delete('sly.article', $article->getId().'_'.$clang);
		$cache->delete('sly.article.list', $re_id.'_'.$clang.'_0');
		$cache->delete('sly.article.list', $re_id.'_'.$clang.'_1');

		if ($article->isStartArticle()) {
			$cache->delete('sly.category', $article->getId().'_'.$clang);
			$cache->delete('sly.category.list', $re_id.'_'.$clang.'_0');
			$cache->delete('sly.category.list', $re_id.'_'.$clang.'_1');
		}

		// Event auslösen
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_ART_STATUS', $article);

		return true;
	}

	public function getStati() {
		static $stati;

		if (!$stati) {
			$stati = array(
				// Name, CSS-Klasse
				array(t('status_offline'), 'rex-offline'),
				array(t('status_online'),  'rex-online')
			);

			$stati = sly_Core::dispatcher()->filter('SLY_ART_STATUS_TYPES', $stati);
		}

		return $stati;
	}

	public function findArticlesByCategory($categoryId, $ignore_offlines = false, $clangId = null) {
		if ($clangId === false || $clangId === null) {
			$clangId = sly_Core::getCurrentClang();
		}

		$categoryId = (int) $categoryId;
		$clangId    = (int) $clangId;

		$namespace = 'sly.article.list';
		$key       = $categoryId.'_'.$clangId.'_'.($ignore_offlines ? '1' : '0');
		$alist     = sly_Core::cache()->get($namespace, $key, null);

		if ($alist === null) {
			$alist = array();
			$sql   = sly_DB_Persistence::getInstance();
			$where = array('re_id' => $categoryId, 'clang' => $clangId, 'startpage' => 0);

			if ($ignore_offlines) $where['status'] = 1;

			$sql->select($this->tablename, 'id', $where, null, 'prior,name');
			foreach ($sql as $row) $alist[] = (int) $row['id'];

			if ($categoryId !== 0) {
				$category = sly_Service_Factory::getCategoryService()->findById($categoryId, $clangId);

				if ($category && (!$ignore_offlines || ($ignore_offlines && $category->isOnline()))) {
					array_unshift($alist, $category->getId());
				}
			}

			sly_Core::cache()->set($namespace, $key, $alist);
		}

		$artlist = array();

		foreach ($alist as $id) {
			$artlist[] = $this->findById($id, $clangId);
		}

		return $artlist;
	}

	public function setType(sly_Model_Article $article, $type) {
		$oldType   = $article->getType();
		$langs     = sly_Util_Language::findAll(true);
		$articleID = $article->getId();

		foreach ($langs as $clangID) {
			$article = sly_Util_Article::findById($articleID, $clangID);

			// Artikel updaten
			$article->setType($type);
			$article->setUpdateColumns();
			$this->update($article);

			// Cache leeren
			$cache = sly_Core::cache();
			$cache->delete('sly.article', $article->getId().'_'.$clangID);
			$cache->delete('sly.category', $article->getId().'_'.$clangID);
		}

		// Event auslösen
		$dispatcher = sly_Core::dispatcher();
		$dispatcher->notify('SLY_ART_TYPE', $article, array('old_type' => $oldType));

		return true;
	}

	public function touch(sly_Model_Article $article, sly_Model_User $user) {
		$article->setUpdatedate(time());
		$article->setUpdateuser($user->getLogin());
		$this->update($article);
	}
}
