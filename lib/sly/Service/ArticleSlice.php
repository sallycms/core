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
 * DB Model Klasse für Slices
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_ArticleSlice extends sly_Service_Model_Base_Id {

	protected $tablename = 'article_slice'; ///< string

	/**
	 * @param  array $params
	 * @return sly_Model_ArticleSlice
	 */

	protected function makeInstance(array $params) {
		return new sly_Model_ArticleSlice($params);
	}

	public function create($params) {
		if (empty($params['slice_id'])) {
			if (empty($params['module'])) {
				throw new sly_Exception('sly_Service_ArticleSlice: A new ArticleSlice must eighter contain a slice_id, oder module value');
			}
			$slice = sly_Service_Factory::getSliceService()->create(array('module' => $params['module']));
			$params['slice_id'] = $slice->getId();
			sly_dump($params['slice_id']);
		}
		$articleSlice = parent::create($params);

		$pre = sly_Core::config()->get('DATABASE/TABLE_PREFIX');
		$sql = sly_DB_Persistence::getInstance();
		$sql->query(
			'UPDATE ' . $pre . $this->tablename . ' SET pos = pos + 1 ' .
			'WHERE article_id = ? AND clang = ? AND slot = ? ' .
			'AND pos >= ? AND id <> ?', array(
				$articleSlice->getArticleId(),
				$articleSlice->getClang(),
				$articleSlice->getSlot(),
				$articleSlice->getPosition(),
				$articleSlice->getId()
			)
		);

		return $articleSlice;
	}

	public function delete($where) {
		$sql = sly_DB_Persistence::getInstance();
		$sql->select($this->tablename, 'id', $where);
		foreach ($sql as $id) {
			$this->deleteById($id);
		}
		return true;
	}

	/**
	 * tries to delete a slice
	 *
	 * @param int $article_slice_id
	 * @return boolean
	 */
	public function deleteById($id) {
		$id = (int) $id;

		$articleSlice = $this->findById($id);

		// remove cachefiles
		sly_Util_Slice::clearSliceCache($articleSlice->getSliceId());

		$sql = sly_DB_Persistence::getInstance();
		$pre = sly_Core::config()->get('DATABASE/TABLE_PREFIX');

		// fix order
		$sql->query('UPDATE ' . $pre . 'article_slice SET pos = pos -1 WHERE
			article_id = ? AND clang = ? AND slot = ? AND pos > ?',
			array(
				$articleSlice->getArticleId(),
				$articleSlice->getClang(),
				$articleSlice->getSlot(),
				$articleSlice->getPosition()
			)
		);

		// delete slice
		sly_Service_Factory::getSliceService()->delete(array('id' => $articleSlice->getSliceId()));

		// delete articleslice
		$sql->delete($this->tablename, array('id' => $id));

		return $sql->affectedRows() == 1;
	}

	/**
	 * Verschiebt einen Slice
	 *
	 * @param  int    $slice_id   ID des Slices
	 * @param  int    $clang      ID der Sprache
	 * @param  string $direction  Richtung in die verschoben werden soll
	 * @return array              ein Array welches den Status sowie eine Fehlermeldung beinhaltet
	 */
	public function move($slice_id, $clang, $direction) {
		$slice_id = (int) $slice_id;
		$clang = (int) $clang;

		if (!in_array($direction, array('up', 'down'))) {
			throw new sly_Exception('ArticleSliceService: Unsupported direction "' . $direction . '"!', E_USER_ERROR);
		}

		$success = false;

		$articleSlice = $this->findById($slice_id);

		if ($articleSlice) {
			$sql        = sly_DB_Persistence::getInstance();
			$article_id = $articleSlice->getArticleId();
			$pos        = $articleSlice->getPosition();
			$slot       = $articleSlice->getSlot();
			$newpos     = $direction == 'up' ? $pos - 1 : $pos + 1;
			$sliceCount = $this->count(array('article_id' => $article_id, 'clang' => $clang, 'slot' => $slot));

			if ($newpos > -1 && $newpos < $sliceCount) {
				$sql->update('article_slice', array('pos' => $pos), array('article_id' => $article_id, 'clang' => $clang, 'slot' => $slot, 'pos' => $newpos));
				$articleSlice->setPosition($newpos);
				$this->save($articleSlice);

				// notify system
				sly_Core::dispatcher()->notify('SLY_SLICE_MOVED', $articleSlice, array(
					'clang'     => $clang,
					'direction' => $direction,
					'old_pos'   => $pos,
					'new_pos'   => $newpos
				));

				$success = true;
			}
		}

		return $success;
	}
}
