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
 * DB Model Klasse für Slices
 *
 * @author  zozi@webvariants.de
 * @ingroup service
 */
class sly_Service_ArticleSlice implements sly_ContainerAwareInterface {
	protected $tablename = 'article_slice'; ///< string
	protected $persistence;
	protected $sliceService;                ///< sly_Service_Slice
	protected $templateService;             ///< sly_Service_Template
	protected $container;                   ///< sly_Container
	protected $dispatcher;                  ///< sly_Event_IDispatcher

	/**
	 * Constructor
	 *
	 * @param sly_DB_Persistence    $persistence
	 * @param sly_Event_IDispatcher $dispatcher
	 * @param sly_Service_Slice     $sliceService
	 * @param sly_Service_Template  $templateService
	 * @param sly_Service_Article   $container
	 */
	public function __construct(sly_DB_Persistence $persistence, sly_Event_IDispatcher $dispatcher, sly_Service_Slice $sliceService, sly_Service_Template $templateService) {
		$this->persistence     = $persistence;
		$this->sliceService    = $sliceService;
		$this->templateService = $templateService;
		$this->dispatcher      = $dispatcher;
	}

	public function setContainer(sly_Container $container = null) {
		$this->container = $container;
	}

	/**
	 * @return sly_Service_Article
	 */
	protected function getArticleService() {
		if (!$this->container) {
			throw new LogicException('Container must be set before article slices can be handled.');
		}

		return $this->container->getArticleService();
	}

	/**
	 *
	 * @return sly_DB_PDO_Persistence
	 */
	protected function getPersistence() {
		return $this->persistence;
	}

	/**
	 * @param  array  $where
	 * @param  string $having
	 * @return sly_Model_Base
	 */
	public function findOne($where = null, $having = null) {
		$res = $this->find($where, null, null, null, 1, $having);
		return count($res) === 1 ? $res[0] : null;
	}

	/**
	 * @param  array  $where
	 * @param  string $group
	 * @param  string $order
	 * @param  int    $offset
	 * @param  int    $limit
	 * @param  string $having
	 * @return array
	 */
	public function find($where = null, $group = null, $order = null, $offset = null, $limit = null, $having = null) {
		$return  = array();
		$db      = $this->getPersistence();

		$db->select($this->tablename, '*', $where, $group, $order, $offset, $limit, $having);

		foreach ($db as $row) {
			$return[] = $this->makeInstance($row);
		}

		return $return;
	}

	/**
	 * @param  array $params
	 * @return sly_Model_ArticleSlice
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_ArticleSlice($params);
	}

	public function insert(sly_Model_ArticleSlice $slice) {
		$sql = $this->getPersistence();

		return $sql->transactional(array($this, 'insertTrx'), array($slice));
	}

	protected function update(sly_Model_ArticleSlice $slice) {
		$sql = $this->getPersistence();

		$sql->update($this->tablename, $slice->toHash(), $slice->getPKHash());
	}

	public function insertTrx(sly_Model_ArticleSlice $slice) {
		$sql = $this->getPersistence();
		$pre = $sql->getPrefix();

		$sql->query(
			'UPDATE '.$pre.$this->tablename.' SET pos = pos + 1 ' .
			'WHERE article_id = ? AND clang = ? AND slot = ? ' .
			'AND revision = ? AND pos >= ?', array(
				$slice->getArticleId(),
				$slice->getClang(),
				$slice->getSlot(),
				$slice->getRevision(),
				$slice->getPosition()
			)
		);
		$sql->insert($this->tablename, $slice->toHash());
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_ArticleSlice  $article
	 */
	public function deleteByArticleSlice(sly_Model_ArticleSlice $slice) {
		return $this->delete($slice->getArticle(), $slice->getSlot(), $slice->getPosition());
	}

	/**
	 * Deletes articleSlices. If pos === null then delete all aqll slices in slot,
	 * If $slot === null too then delete all slices for the article
	 *
	 * @param  sly_Model_Article $article
	 * @param  string            $slot
	 * @param  int               $pos
	 * @throws sly_Exception
	 */
	public function delete(sly_Model_Article $article, $slot = null, $pos = null) {
		$articleService = $this->getArticleService();
		$article        = $articleService->touch($article);

		$where = array(
			'article_id' => $article->getId(),
			'clang'      => $article->getClang(),
			'revision'   => $article->getRevision()
		);

		if ($pos !== null && $slot === null) {
			throw new sly_Exception();
		}

		if ($slot !== null) {
			$where['slot'] = $slot;
		}

		if ($pos !== null) {
			$where['pos'] = $pos;
		}

		$sql = $this->getPersistence();

		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			$articleSlices = $this->find($where);

			// fix order if its only one articleSlice
			if ($slot !== null && $pos !== null) {
				$sql->query('UPDATE '.$sql->getPrefix().'article_slice SET pos = pos -1 WHERE
					article_id = :article_id AND clang = :clang AND slot = :slot AND revision = :revision AND pos > :pos',
					$where
				);
			}

			foreach ($articleSlices as $articleSlice) {
				// delete slice
				$this->sliceService->deleteById($articleSlice->getSliceId());

				// delete articleslice
				$sql->delete($this->tablename, array(
						'article_id' => $articleSlice->getArticleId(),
						'clang'      => $articleSlice->getClang(),
						'revision'   => $articleSlice->getRevision(),
						'slot'       => $articleSlice->getSlot(),
						'pos'        => $articleSlice->getPosition()
					)
				);
			}

		}
		catch (Exception $e) {
			if ($ownTrx) {
				$sql->rollBack();
			}

			throw $e;
		}

		$this->dispatcher->notify('SLY_SLICE_DELETED', null, $where);
	}

	/**
	 *
	 * @param  sly_Model_Article       $article
	 * @param  array                   $values
	 * @param  sly_Model_User          $user
	 * @return sly_Model_ArticleSlice
	 */
	public function edit(sly_Model_Article $article, $slot, $pos, array $values, sly_Model_User $user = null) {
		$sql    = $this->getPersistence();
		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		// here we go

		try {
			$artService   = $this->getArticleService();
			$article      = $artService->touch($article);
			$articleSlice = $this->findOne(
				array(
					'article_id' => $article->getId(),
					'clang'      => $article->getClang(),
					'revision'   => $article->getRevision(),
					'slot'       => $slot,
					'pos'        => $pos
				)
			);

			if (!$articleSlice) {
				throw new sly_Exception_ArticleSliceNotFound(t('slice_not_found'));
			}

			$slice = $articleSlice->getSlice();
			$slice->setValues($values);
			$this->sliceService->save($slice);
		}
		catch (Exception $e) {
			if ($ownTrx) {
				$sql->rollBack();
			}

			throw $e;
		}
		$this->dispatcher->notify('SLY_SLICE_EDITED', $articleSlice);

		return $articleSlice;
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
	public function add(sly_Model_Article $article, $slot, $module, array $values, $pos = null, sly_Model_User $user = null) {
		if (!$article->hasTemplate()) {
			throw new sly_Exception(t('article_has_no_template'));
		}

		$artService = $this->getArticleService();
		$article    = $artService->touch($article, $user);

		$tpl = $article->getTemplateName();

		if (!$this->templateService->hasSlot($tpl, $slot)) {
			throw new sly_Exception(t('article_has_no_such_slot', $slot));
		}

		$user     = $this->getActor($user, __METHOD__);
		$artID    = $article->getId();
		$clang    = $article->getClang();
		$revision = $article->getRevision();

		// prepare database transaction

		$sql    = $this->getPersistence();
		$ownTrx = !$sql->isTransRunning();

		if ($ownTrx) {
			$sql->beginTransaction();
		}

		// here we go

		try {
			$maxPos = $this->getMaxPosition($article, $slot);
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
			$articleSlice->setCreateColumns($user);
			$articleSlice->setSlice($slice);
			$articleSlice->setSlot($slot);
			$articleSlice->setArticle($article);
			$articleSlice->setRevision($revision);

			$this->insert($articleSlice);

			// commit changes

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
		$this->dispatcher->notify('SLY_SLICE_ADDED', $articleSlice);
		return $articleSlice;
	}

	public function moveTo(sly_Model_Article $article, $slot, $curPos, $newPos, sly_Model_User $user = null) {
		$curPos = (int) $curPos;
		$newPos = (int) $newPos;
		$sql    = $this->getPersistence();
		$ownTrx = !$sql->isTransRunning();

		// make sure a transaction is startet
		if ($ownTrx) {
			$sql->beginTransaction();
		}

		try {
			$artService = $this->getArticleService();
			$user       = $this->getActor($user, __METHOD__);
			$article    = $artService->touch($article, $user);
			$maxPos     = $this->getMaxPosition($article, $slot);
			$newPos     = max(array(0, min(array($newPos, $maxPos)))); // normalize
			$articleId  = $article->getId();
			$clang      = $article->getClang();
			$revision   = $article->getRevision();

			// if it equals $curPos is eighter $maxPos, or 0 and should be moved
			// out of range
			if ($newPos === $curPos) {
				throw new sly_Exception_ArticleSlicePositionOutOfBounds();
			}

			$articleSlice = $this->findOne(array('article_id' => $articleId, 'clang' => $clang, 'slot' => $slot, 'pos' => $curPos, 'revision' => $revision));

			if (!$articleSlice) {
				throw new sly_Exception_ArticleSliceNotFound(t('slice_not_found'));
			}

			$op  = ($newPos < $curPos) ? '+' : '-';
			$rel = ($newPos < $curPos) ? '<=' : '>=';

			//move other slices
			$sql->query(sprintf(
				'UPDATE %s'.$this->tablename.' SET pos = pos %s 1 ' .
				'WHERE article_id = ? AND clang = ? AND slot = ? ' .
				'AND revision = ? AND pos %s ?', $sql->getPrefix(), $op, $rel), array(
					$articleId,
					$clang,
					$slot,
					$revision,
					$newPos
				)
			);

			$articleSlice->setPosition($newPos);
			$articleSlice->setUpdateColumns($user);
			$this->update($articleSlice);

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

		// @deprecated revove direction from the event in the future
		$direction = $op === '+' ? 'up' : 'down';

		// notify system
		$this->dispatcher->notify('SLY_SLICE_MOVED', $articleSlice, array(
			'clang'     => $clang,
			'direction' => $direction,
			'old_pos'   => $curPos,
			'new_pos'   => $newPos,
			'user'      => $user
		));
	}

	/**
	 * Move a slice up or down
	 *
	 * @deprecated since 0.9  use moveTo() instead
	 *
	 * @throws sly_Exception
	 * @param  int            $slice_id   article slice ID
	 * @param  string         $direction  direction to move, either 'up' or 'down'
	 * @param  sly_Model_User $user       updateuser or null for current user
	 */
	public function move($slice_id, $direction, sly_Model_User $user = null) {
		$slice_id = (int) $slice_id;

		if (!in_array($direction, array('up', 'down'))) {
			throw new sly_Exception(t('unsupported_direction', $direction));
		}

		$articleSlice = $this->findById($slice_id);

		$curPos = $articleSlice->getPosition();
		$newPos = $direction === 'up' ? $curPos - 1 : $curPos + 1;
		$this->moveTo($articleSlice->getArticle(), $articleSlice->getSlot(), $curPos, $newPos, $user);
	}

	/**
	 * Get the previous slice in the same slot
	 *
	 * @param  sly_Model_ArticleSlice $slice
	 * @param  sly_Model_ArticleSlice         the previous slice or null
	 */
	public function getPrevious(sly_Model_ArticleSlice $slice) {
		return $this->getSibling($slice, 'slot = %s AND pos < %d AND article_id = %d AND clang = %d AND revision = %d ORDER BY pos DESC');
	}

	/**
	 * Get the next slice in the same slot
	 *
	 * @param  sly_Model_ArticleSlice $slice
	 * @param  sly_Model_ArticleSlice         the next slice or null
	 */
	public function getNext(sly_Model_ArticleSlice $slice) {
		return $this->getSibling($slice, 'slot = %s AND pos > %d AND article_id = %d AND clang = %d AND revision = %d ORDER BY pos ASC');
	}

	/**
	 * Get slice sibling
	 *
	 * @param  sly_Model_ArticleSlice $slice
	 * @param  string                 $where  the WHERE statement with placeholders
	 * @param  sly_Model_ArticleSlice
	 */
	protected function getSibling(sly_Model_ArticleSlice $slice, $where) {
		$slot    = $this->getPersistence()->quote($slice->getSlot());
		$pos     = $slice->getPosition();
		$article = $slice->getArticleId();
		$clang   = $slice->getClang();
		$rev     = $slice->getRevision();

		return $this->findOne(sprintf($where, $slot, $pos, $article, $clang, $rev));
	}

	/**
	 * get the maximum position in a slot
	 *
	 * @param  sly_Model_Article $article  an article
	 * @param  string            $slot     a slot (identifier) in this article
	 * @return int                         maximum value of pos
	 */
	protected function getMaxPosition(sly_Model_Article $article, $slot) {
		$sql = $this->getPersistence();
		return (int) $sql->magicFetch('article_slice', 'MAX(pos)', array('article_id' => $article->getId(), 'clang' => $article->getClang(), 'slot' => $slot, 'revision' => $article->getRevision()));
	}

	protected function getActor(sly_Model_User $user = null, $methodName = null) {
		if ($user === null) {
			$user = sly_Util_User::getCurrentUser();

			if ($user === null) {
				throw new sly_Exception($methodName ? t('operation_requires_user_context', $methodName) : t('an_operation_requires_user_context'));
			}
		}

		return $user;
	}
}
