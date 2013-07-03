<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use Gaufrette\Filesystem;
use Gaufrette\Util\Path;
use wv\BabelCache\CacheInterface;

/**
 * Service class for managing media (aka files)
 *
 * @author  christoph@webvariants.de
 * @ingroup service
 */
class sly_Service_Medium extends sly_Service_Model_Base_Id implements sly_ContainerAwareInterface {
	protected $tablename = 'file'; ///< string
	protected $cache;              ///< CacheInterface
	protected $dispatcher;         ///< sly_Event_IDispatcher
	protected $container;          ///< sly_Container
	protected $mediaFs;            ///< Filesystem
	protected $extBlacklist;       ///< array
	protected $fsBaseUri;          ///< string

	/**
	 * Constructor
	 *
	 * @param sly_DB_Persistence    $persistence
	 * @param CacheInterface        $cache
	 * @param sly_Event_IDispatcher $dispatcher
	 * @param Filesystem            $mediaFs
	 * @param array                 $extBlacklist
	 * @param string                $fsBaseUri
	 */
	public function __construct(sly_DB_Persistence $persistence, CacheInterface $cache, sly_Event_IDispatcher $dispatcher, Filesystem $mediaFs, array $extBlacklist, $fsBaseUri) {
		parent::__construct($persistence);

		$this->cache        = $cache;
		$this->dispatcher   = $dispatcher;
		$this->mediaFs      = $mediaFs;
		$this->extBlacklist = $extBlacklist;
		$this->fsBaseUri    = rtrim($fsBaseUri, '/').'/';
	}

	public function setContainer(sly_Container $container = null) {
		$this->container = $container;
	}

	/**
	 * @return sly_Service_MediaCategory
	 */
	protected function getMediaCategoryService() {
		if (!$this->container) {
			throw new LogicException('Container must be set before media categories can be handled.');
		}

		return $this->container->getMediaCategoryService();
	}

	/**
	 * @param  array $params
	 * @return sly_Model_Medium
	 */
	protected function makeInstance(array $params) {
		return new sly_Model_Medium($params);
	}

	/**
	 * @param  int $id
	 * @return sly_Model_Medium
	 */
	public function findById($id) {
		$id = (int) $id;

		if ($id <= 0) {
			return null;
		}

		$medium = $this->cache->get('sly.medium', $id, null);

		if ($medium === null) {
			$medium = $this->findOne(array('id' => $id));

			if ($medium !== null) {
				$this->cache->set('sly.medium', $id, $medium);
			}
		}

		return $medium;
	}

	/**
	 * @param  string $filename
	 * @return sly_Model_Medium
	 */
	public function findByFilename($filename) {
		$hash = md5($filename);
		$id   = $this->cache->get('sly.medium', $hash, null);

		if ($id === null) {
			$db = $this->getPersistence();
			$id = $db->magicFetch('file', 'id', array('filename' => $filename));

			if ($id === false) {
				return null;
			}

			$this->cache->set('sly.medium', $hash, $id);
		}

		return $this->findById($id);
	}

	/**
	 * @param  string $extension
	 * @return array
	 */
	public function findMediaByExtension($extension) {
		$namespace = 'sly.medium.list';
		$list      = $this->cache->get($namespace, $extension, null);

		if ($list === null) {
			$sql  = $this->getPersistence();
			$list = array();

			$sql->select('file', 'id', array('SUBSTRING(filename, LOCATE(".", filename) + 1)' => $extension), null, 'filename');
			foreach ($sql as $row) $list[] = (int) $row['id'];

			$this->cache->set($namespace, $extension, $list);
		}

		$objlist = array();

		foreach ($list as $id) {
			$objlist[] = $this->findById($id);
		}

		return $objlist;
	}

	/**
	 * @param  int $categoryId
	 * @return array
	 */
	public function findMediaByCategory($categoryId) {
		$categoryId = (int) $categoryId;
		$namespace  = 'sly.medium.list';
		$list       = $this->cache->get($namespace, $categoryId, null);

		if ($list === null) {
			$list  = array();
			$sql   = $this->getPersistence();
			$where = array('category_id' => $categoryId);

			$sql->select('file', 'id', $where, null, 'filename');
			foreach ($sql as $row) $list[] = (int) $row['id'];

			$this->cache->set($namespace, $categoryId, $list);
		}

		$objlist = array();

		foreach ($list as $id) {
			$objlist[] = $this->findById($id);
		}

		return $objlist;
	}

	public function getFullPath(sly_Model_Medium $medium) {
		return $this->fsBaseUri.$medium->getFilename();
	}

	/**
	 * Check if a file exists in the media filesystem
	 *
	 * @param  string $filename  e.g. 'foo.jpg'
	 * @return boolean
	 */
	public function fileExists($filename) {
		return $this->mediaFs->has(basename($filename));
	}

	/**
	 * Import a file to the media filesystem
	 *
	 * This will import the given $source into the media filesystem. $source must
	 * be the full path to a file, either a local file ('/path/to/file.jpg') or
	 * an URI pointing to a valid stream wrapper ('sly://dyn/myfile.jpg').
	 *
	 * Only the basename of $targetName is relevant (all files in the media
	 * filesystem are on one level). The the target filename already exists,
	 * the filename will be incremented by appending '_1', '_2', ... to it. The
	 * final full URI to the imported file is returned.
	 *
	 * @param  string  $source          full path/URI to the source file outside the media fs
	 * @param  string  $targetName      desired target name
	 * @param  boolean $applyBlacklist  whether or not to to add '.txt' to blacklisted extensions
	 * @return string                   final URI of the imported file
	 */
	public function importFile($source, $targetName, $applyBlacklist) {
		if (!file_exists($source)) {
			throw new sly_Exception(t('file_not_found', $source));
		}

		$targetName = basename($targetName);

		if (mb_strlen($targetName) === 0) {
			throw new sly_Exception('No target name for importing "'.$source.'" given.');
		}

		$targetName = sly_Util_File::createFilename($targetName, true, $applyBlacklist, $this->mediaFs);

		// add file to media filesystem
		$service = new sly_Filesystem_Service($this->mediaFs);
		$service->importFile($source, $targetName);

		return $this->fsBaseUri.$targetName;
	}

	/**
	 * Upload a file to the media filesystem
	 *
	 * This is similar to importing, but takes the file information from PHP's
	 * $_FILES array and is suitable for file uploads.
	 *
	 * @param  array   $fileData        everything from $_FILES['yourfile']
	 * @param  boolean $doSubindexing
	 * @param  boolean $applyBlacklist  whether or not to to add '.txt' to blacklisted extensions
	 * @return string                   final URI of the imported file
	 */
	public function uploadFile(array $fileData, $doSubindexing, $applyBlacklist) {
		$service  = new sly_Filesystem_Service($this->mediaFs);
		$filename = $service->uploadFile($fileData, null, $doSubindexing, $applyBlacklist);

		return $this->fsBaseUri.$filename;
	}

	/**
	 * Add a file to the media database
	 *
	 * @throws sly_Exception
	 * @param  string         $filename      relative filename inside the media filesystem
	 * @param  string         $title
	 * @param  int            $categoryID
	 * @param  string         $mimetype
	 * @param  string         $originalName
	 * @param  sly_Model_User $user          creator or null for the current user
	 * @return sly_Model_Medium
	 */
	public function add($filename, $title, $categoryID, $mimetype = null, $originalName = null, sly_Model_User $user = null) {
		$user = $this->getActor($user, __METHOD__);

		// check file itself

		if (!$this->fileExists($filename)) {
			throw new sly_Exception(t('file_not_found', $filename));
		}

		// check category

		$categoryID = (int) $categoryID;
		$catService = $this->getMediaCategoryService();

		if ($catService->findById($categoryID) === null) {
			$categoryID = 0;
		}

		$basename = basename($filename);
		$fileURI  = $this->fsBaseUri.Path::normalize($filename);
		$mimetype = empty($mimetype) ? sly_Util_File::getMimetype($filename) : $mimetype;

		// create file object
		$file = new sly_Model_Medium();
		$file->setFiletype($mimetype);
		$file->setTitle($title);
		$file->setOriginalName($originalName === null ? $basename : basename($originalName));
		$file->setFilename($basename);
		$file->setFilesize(filesize($fileURI));
		$file->setCategoryId($categoryID);
		$file->setRevision(0);
		$file->setAttributes('');
		$file->setCreateColumns($user);

		$this->setImageSize($file, $fileURI, $mimetype);

		// store the file in our database
		$this->save($file);

		$this->cache->clear('sly.medium.list');
		$this->dispatcher->notify('SLY_MEDIA_ADDED', $file, compact('user'));

		return $file;
	}

	public function replace(sly_Model_Medium $medium, $newFile, $deleteSource) {
		// check file itself

		if (!file_exists($newFile)) {
			throw new sly_Exception(t('file_not_found', $newFile));
		}

		// check if the type of the new file matches the old one

		$mimetype = sly_Util_File::getMimetype($newFile);

		if ($mimetype !== $medium->getFiletype()) {
			throw new sly_Exception(t('types_of_old_and_new_do_not_match'));
		}

		// replace the existing file

		$service = new sly_Filesystem_Service($this->mediaFs);
		$service->importFile($newFile, $medium->getFilename(), $deleteSource);

		// update the medium

		$medium->setFilesize(filesize($newFile));
		$this->setImageSize($medium, $newFile, $mimetype);
	}

	public function replaceByUpload(sly_Model_Medium $medium, array $fileData) {
		$service = new sly_Filesystem_Service($this->mediaFs);
		$service->checkUpload($fileData);

		// check if the type of the new file matches the old one

		$mimetype = sly_Util_File::getMimetype($fileData['name']);

		if ($mimetype !== $medium->getFiletype()) {
			throw new sly_Exception(t('types_of_old_and_new_do_not_match'));
		}

		// replace the existing file

		$fileURI = $this->getFullPath($medium);

		$service->uploadFile($fileData, $filename, false, false);

		// update the medium

		$medium->setFilesize(filesize($fileURI));
		$this->setImageSize($medium, $fileURI, $mimetype);
	}

	/**
	 * @param sly_Model_Medium $medium
	 * @param sly_Model_User   $user    user or null for the current user
	 */
	public function update(sly_Model_Medium $medium, sly_Model_User $user = null) {
		$user = $this->getActor($user, __METHOD__);

		// store data
		$medium->setUpdateColumns($user);
		$this->save($medium);

		// notify the listeners and clear our own cache
		$this->cache->remove('sly.medium', $medium->getId());
		$this->dispatcher->notify('SLY_MEDIA_UPDATED', $medium, compact('user'));
	}

	/**
	 * @throws sly_Exception
	 * @param  sly_Model_Medium $medium
	 * @return boolean
	 */
	public function deleteByMedium(sly_Model_Medium $medium) {
		return $this->deleteById($medium->getId());
	}

	/**
	 * @throws sly_Exception
	 * @param  int $mediumID
	 * @return boolean
	 */
	public function deleteById($mediumID) {
		$medium = $this->findById($mediumID);

		if (!$medium) {
			throw new sly_Exception(t('medium_not_found', $mediumID));
		}

		try {
			$sql = $this->getPersistence();
			$sql->delete('file', array('id' => $medium->getId()));

			$filename = $medium->getFilename();

			if ($this->mediaFs->has($filename)) {
				$this->mediaFs->delete($filename);
			}
		}
		catch (Exception $e) {
			// re-wrap DB & PDO exceptions
			throw new sly_Exception($e->getMessage());
		}

		$this->cache->clear('sly.medium.list');
		$this->cache->remove('sly.medium', $medium->getId());

		$this->dispatcher->notify('SLY_MEDIA_DELETED', $medium);

		return true;
	}

	public function getUsages(sly_Model_Medium $medium) {
		$sql      = $this->getPersistence();
		$filename = $medium->getFilename();
		$prefix   = $sql->getPrefix();
		$query    =
			'SELECT s.article_id, s.clang, s.revision '.
			'FROM '.$prefix.'slice sv, '.$prefix.'article_slice s, '.$prefix.'article a '.
			'WHERE sv.id = s.slice_id AND a.id = s.article_id AND a.clang = s.clang AND serialized_values REGEXP ? '.
			'GROUP BY s.article_id, s.clang';

		$usages  = array();
		$b       = '[^[:alnum:]_+-]'; // more or less like a \b in PCRE
		$quoted  = str_replace(array('.', '+'), array('\.', '\+'), $filename);
		$data    = array("(^|$b)$quoted(\$|$b)");
		$service = $this->container->getArticleService();

		$sql->query($query, $data);

		foreach ($sql->all() as $row) {
			$article  = $service->findById($row['article_id'], $row['clang'], $row['revision']);
			$usages[] = array(
				'object' => $article,
				'type'   => 'sly-article'
			);
		}

		$usages = $this->dispatcher->filter('SLY_MEDIA_USAGES', $usages, array(
			'filename' => $filename, // deprecated since 0.9
			'media'    => $medium,   // deprecated since 0.9
			'medium'   => $medium
		));

		return $usages;
	}

	protected function setImageSize(sly_Model_Medium $medium, $filename, $mimetype) {
		$medium->setWidth(0);
		$medium->setHeight(0);

		if (substr($mimetype, 0, 6) === 'image/') {
			$size = @getimagesize($filename);

			if ($size) {
				$medium->setWidth($size[0]);
				$medium->setHeight($size[1]);
			}
		}
	}
}
