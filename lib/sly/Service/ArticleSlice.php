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
 * DB Model Klasse für Slices
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_ArticleSlice extends sly_Service_Model_Base_Id {
	protected $tablename = 'article_slice'; ///< string
	protected $sliceService;                ///< sly_Service_Slice
	protected $templateService;             ///< sly_Service_Template
	protected $dispatcher;                  ///< sly_Event_IDispatcher

	/**
	 * Constructor
	 *
	 * @param sly_DB_Persistence    $persistence
	 * @param sly_Event_IDispatcher $dispatcher
	 * @param sly_Service_Slice     $sliceService
	 * @param sly_Service_Template  $templateService
	 */
	public function __construct(sly_DB_Persistence $persistence, sly_Event_IDispatcher $dispatcher, sly_Service_Slice $sliceService, sly_Service_Template $templateService) {
		parent::__construct($persistence);

		$this->sliceService    = $sliceService;
		$this->templateService = $templateService;
		$this->dispatcher      = $dispatcher;
	}

	/**
	 * @param  array $params
	 * @return sly_Model_ArticleSlice
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_ArticleSlice($params);
	}

	public function save(sly_Model_Base $model) {
		$sql = $this->getPersistence();

		return $sql->transactional(array($this, 'saveTrx'), array($model));
	}

	public function saveTrx(sly_Model_ArticleSlice $slice) {
		if ($slice->getId() === sly_Model_Base_Id::NEW_ID) {
			$sql = $this->getPersistence();
			$pre = sly_Core::getTablePrefix();

			$sql->query(
				'UPDATE '.$pre.$this->tablename.' SET pos = pos + 1 ' .
				'WHERE article_id = ? AND clang = ? AND slot = ? ' .
				'AND pos >= ?', array(
					$slice->getArticleId(),
					$slice->getClang(),
					$slice->getSlot(),
					$slice->getPosition()
				)
			);
		}

		return parent::save($slice);
	}

	public function delete($where) {
		$sql = $this->getPersistence();
		$sql->select($this->tablename, 'id', $where);

		foreach ($sql as $id) {
			$this->deleteById($id);
		}

		return true;
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_ArticleSlice  $article
	 * @return boolean
	 */
	public function deleteByArticleSlice(sly_Model_ArticleSlice $slice) {
		return $this->deleteById($slice->getId());
	}

	/**
	 * tries to delete a slice
	 *
	 * @throws sly_Exception
	 * @param  int $id
	 * @return boolean
	 */
	public function deleteById($id) {
		$id           = (int) $id;
		$articleSlice = $this->findById($id);

		if (!$articleSlice) {
			throw new sly_Exception(t('article_slice_not_found', $id));
		}

		$sql = $this->getPersistence();
		$pre = sly_Core::getTablePrefix();

		// fix order
		$sql->query('UPDATE '.$pre.'article_slice SET pos = pos -1 WHERE
			article_id = ? AND clang = ? AND slot = ? AND pos > ?',
			array(
				$articleSlice->getArticleId(),
				$articleSlice->getClang(),
				$articleSlice->getSlot(),
				$articleSlice->getPosition()
			)
		);

		// delete slice
		$this->sliceService->deleteById($articleSlice->getSliceId());

		// delete articleslice
		$sql->delete($this->tablename, array('id' => $id));

		return $sql->affectedRows() == 1;
	}

	public function findByArticleClangSlot($articleID, $clang = null, $slot = null) {
		if ($clang === null) {
			$clang = sly_Core::getCurrentClang();
		}

		$where = array('article_id' => $articleID, 'clang' => $clang);
		$order = 'pos ASC';

		if ($slot !== null) {
			$where['slot'] = $slot;
		}
		else {
			$order = 'slot ASC, '.$order;
		}

		return $this->find($where, null, $order);
	}

	/**
	 * add a new article slice to an article
	 *
	 * The new slice will be placed after the existing slices by default, but you
	 * can give an explicit position to override this.
	 *
	 * @throws sly_Exception               if the article has no template or not the requested slot
	 * @param  sly_Model_Article $article  the target article
	 * @param  string            $slot     the target slot
	 * @param  string            $module   module name
	 * @param  array             $values   slice values
	 * @param  int               $pos      explicit position or null for the end
	 * @param  sly_Model_User    $creator  the createuser or null for the current user
	 * @return sly_Model_ArticleSlice
	 */
	public function add(sly_Model_Article $article, $slot, $module, array $values, $pos = null, sly_Model_User $creator = null) {
		if (!$article->hasTemplate()) {
			throw new sly_Exception(t('article_has_no_template'));
		}

		$tpl = $article->getTemplateName();

		if (!$this->templateService->hasSlot($tpl, $slot)) {
			throw new sly_Exception(t('article_has_no_such_slot', $slot));
		}

		$creator = $this->getActor($creator, __METHOD__);
		$now     = time();
		$artID   = $article->getId();
		$clang   = $article->getClang();

		// prepare database transaction

		$sql    = $this->getPersistence();
		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		// here we go

		try {
			$maxPos = $sql->magicFetch('article_slice', 'MAX(pos)', array('article_id' => $artID, 'clang' => $clang, 'slot' => $slot));
			$target = $maxPos + 1;

			if ($pos !== null) {
				if ($pos < 0) {
					$target = 0;
				}
				elseif ($pos <= $target) {
					$target = $pos;
				}
			}

			// build the models

			$slice = new sly_Model_Slice();
			$slice->setModule($module);
			$slice->setValues($values);
			$slice = $this->sliceService->save($slice);

			$articleSlice = new sly_Model_ArticleSlice();
			$articleSlice->setPosition($target);
			$articleSlice->setCreateColumns($creator);
			$articleSlice->setSlice($slice);
			$articleSlice->setSlot($slot);
			$articleSlice->setArticle($article);
			$articleSlice->setRevision(0);

			$this->save($articleSlice);

			// commit changes

			if ($ownTrx) {
				$sql->commit();
			}

			return $articleSlice;
		}
		catch (Exception $e) {
			if ($ownTrx) {
				$sql->rollBack();
			}

			throw $e;
		}
	}

	/**
	 * Move a slice up or down
	 *
	 * @throws sly_Exception
	 * @param  int            $slice_id   article slice ID
	 * @param  string         $direction  direction to move, either 'up' or 'down'
	 * @param  sly_Model_User $user       updateuser or null for current user
	 * @return boolean                    true if moved, else false
	 */
	public function move($slice_id, $direction, sly_Model_User $user = null) {
		$slice_id = (int) $slice_id;

		if (!in_array($direction, array('up', 'down'))) {
			throw new sly_Exception(t('unsupported_direction', $direction));
		}

		$user         = $this->getActor($user, __METHOD__);
		$articleSlice = $this->findById($slice_id);

		if (!$articleSlice) {
			throw new sly_Exception(t('slice_not_found', $slice_id));
		}

		$success    = false;
		$sql        = $this->getPersistence();
		$article_id = $articleSlice->getArticleId();
		$clang      = $articleSlice->getClang();
		$pos        = $articleSlice->getPosition();
		$slot       = $articleSlice->getSlot();
		$newpos     = $direction === 'up' ? $pos - 1 : $pos + 1;
		$sliceCount = $this->count(array('article_id' => $article_id, 'clang' => $clang, 'slot' => $slot));

		if ($newpos > -1 && $newpos < $sliceCount) {
			$ownTrx = !$sql->isTransRunning();

			if ($ownTrx) {
				$sql->beginTransaction();
			}

			try {
				$sql->update('article_slice', array('pos' => $pos), array('article_id' => $article_id, 'clang' => $clang, 'slot' => $slot, 'pos' => $newpos));
				$articleSlice->setPosition($newpos);
				$articleSlice->setUpdateColumns($user);
				$this->save($articleSlice);

				if ($ownTrx) {
					$sql->commit();
				}
			}
			catch (Exception $e) {
				if ($ownTrx) {
					$sql->rollBack();
				}

				throw $e;
			}

			// notify system
			$this->dispatcher->notify('SLY_SLICE_MOVED', $articleSlice, array(
				'clang'     => $clang,
				'direction' => $direction,
				'old_pos'   => $pos,
				'new_pos'   => $newpos,
				'user'      => $user
			));

			$success = true;
		}

		return $success;
	}
}
