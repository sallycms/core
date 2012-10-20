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
 * @ingroup table
 */
class sly_Table extends sly_Viewable {
	protected $id;                  ///< string
	protected $columns;             ///< array
	protected $classes;             ///< array
	protected $isEmpty;             ///< boolean
	protected $emptyNotice;         ///< string
	protected $enableSorting;       ///< boolean
	protected $enableSearching;     ///< boolean
	protected $enableDragAndDrop;   ///< boolean
	protected $dragAndDropHandler;  ///< boolean
	protected $totalElements;       ///< int
	protected $caption;             ///< string
	protected $searchParams;        ///< array

	private $content;               ///< string

	protected static $perPage = 30; ///< int

	/**
	 * @param string $id
	 * @param array  $classes  list of additional CSS classes for the table tag
	 */
	public function __construct($id = null, array $classes = array()) {
		// Das 'a'-Präfix verhindert, dass die ID mit einer Zahl beginnt, was in HTML nicht erlaubt ist.

		$this->id                 = $id === null ? 'a'.substr(md5(uniqid()), 0, 10) : $id;
		$this->columns            = array();
		$this->classes            = array('sly-table');
		$this->isEmpty            = false;
		$this->emptyNotice        = t('table_is_empty');
		$this->enableSorting      = false;
		$this->enableSearching    = false;
		$this->enableDragAndDrop  = false;
		$this->dragAndDropHandler = '';
		$this->totalElements      = null;
		$this->caption            = null;
		$this->searchParams       = null;

		foreach ($classes as $className) $this->addClass($className);
	}

	/**
	 * @param string $id
	 */
	public function setID($id) {
		$this->id = trim($id);
	}

	/**
	 * @return string
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * @param  sly_Request $request   the request to use or null for the global one
	 * @return boolean
	 */
	public static function isDragAndDropMode(sly_Request $request = null) {
		$request = $request ? $request : sly_Core::getRequest();
		return $request->get('tableswitch', 'string') === 'dodraganddrop';
	}

	/**
	 * @param int $perPage
	 */
	public static function setElementsPerPageStatic($perPage) {
		self::$perPage = abs((int) $perPage);

		if (self::$perPage < 1) {
			self::$perPage = 1;
		}
	}

	/**
	 * @param int $perPage
	 */
	public function setElementsPerPage($perPage = 20) {
		self::setElementsPerPageStatic($perPage);
	}

	/**
	 * @param  int         $totalElements  leave this to null to disable the pager
	 * @param  sly_Request $request        the request to use
	 * @return string                      the rendered header
	 */
	protected function renderHeader($totalElements = null, sly_Request $request) {
		$this->totalElements = $totalElements;
		return $this->renderView('header.phtml', compact('totalElements', 'request'));
	}

	/**
	 * @return string  the rendered footer
	 */
	protected function renderFooter() {
		return $this->renderView('footer.phtml');
	}

	public function openBuffer() {
		ob_start();
	}

	public function closeBuffer() {
		$this->content = ob_get_clean();
	}

	/**
	 * @param  int         $totalElements  leave this to null to disable the pager
	 * @param  sly_Request $request        the request to use or null for the global one
	 * @return string                      the rendered content
	 */
	public function render($totalElements = null, sly_Request $request = null) {
		ob_start();
		$request = $request ? $request : sly_Core::getRequest();
		print $this->renderHeader($totalElements, $request);
		print $this->content;
		print $this->renderFooter();
		return ob_get_clean();
	}

	/**
	 * @param boolean $isEmpty
	 */
	public function setIsEmpty($isEmpty) {
		$this->isEmpty = (bool) $isEmpty;
	}

	/**
	 * @param string $caption
	 */
	public function setCaption($caption) {
		$this->caption = $caption;
	}

	/**
	 * @param string $notice
	 */
	public function setEmptyNotice($notice) {
		$this->emptyNotice = $notice;
	}

	/**
	 * @param boolean $enable
	 */
	public function enableSorting($enable = true) {
		$this->enableSorting = (bool) $enable;
	}

	/**
	 * @param boolean $enable
	 */
	public function enableDragAndDrop($enable = true) {
		$this->enableDragAndDrop = (bool) $enable;
	}

	/**
	 * @param string $function  JS callable
	 */
	public function setDragAndDropHandler($function) {
		$this->dragAndDropHandler = $function;
	}

	/**
	 * @param sly_Table_Column $col
	 */
	public function addColumn(sly_Table_Column $col) {
		$this->columns[] = $col;
	}

	/**
	 * @param  int $idx
	 * @return sly_Table_Column
	 */
	public function getColumn($idx) {
		return sly_setarraytype($this->columns, $idx, 'raw', null);
	}

	/**
	 * @param boolean $enable
	 * @param array   $getParams  override default detection of GET parameters by giving an array instead of null
	 */
	public function enableSearching($enable = true, array $getParams = null) {
		$this->enableSearching = (bool) $enable;
		$this->searchParams    = $getParams;
	}

	/**
	 * @return boolean
	 */
	public function isSorting() {
		return $this->enableSorting;
	}

	/**
	 * @return boolean
	 */
	public function isSearching() {
		return $this->enableSearching;
	}

	/**
	 * @param  string      $default
	 * @param  sly_Request $request   the request to use or null for the global one
	 * @return string
	 */
	public function getSortKey($default = null, sly_Request $request = null) {
		$request = $request ? $request : sly_Core::getRequest();
		return $request->get($this->id.'_sortby', 'string', $default);
	}

	/**
	 * @param  string      $default
	 * @param  sly_Request $request   the request to use or null for the global one
	 * @return string
	 */
	public function getDirection($default = 'asc', sly_Request $request = null) {
		$request = $request ? $request : sly_Core::getRequest();
		return $request->get($this->id.'_direction', 'string', $default);
	}

	/**
	 * Gibt true zurück, wenn ein Pager angezeigt werden soll.
	 *
	 * @return boolean
	 */
	public function hasPager() {
		return $this->totalElements !== null && $this->totalElements > self::$perPage;
	}

	/**
	 * Gibt true zurück, wenn die Suchmaske angezeigt werden soll.
	 *
	 * @return boolean
	 */
	public function hasSearch() {
		// Die Suchfunktion ist immer dann aktiviert, wenn sie im Objekt
		// aktiviert wurde (enableSearching) und wenn der Tabellenmodus
		// nicht auf dodraganddrop steht.

		return $this->enableSearching;
	}

	/**
	 * Gibt true zurück, ob Drag&Drop aktiviert werden soll.
	 *
	 * @return boolean
	 */
	public function hasDragAndDrop() {
		// D&D ist nur aktiv, wenn es explizit aktiviert wurde. Zusätzlich:
		// D&D ist immer dann aktiv, wenn die Tabelle sich im dodraganddrop-Modus
		// befindet oder wenn weder Pager noch Suchfunktion aktiv sind. Also:

		// D&D = ACTIVE & (dodraganddrop || (!PAGER && !SEARCH))

		return $this->enableDragAndDrop && (self::isDragAndDropMode() || (!$this->hasPager() && !$this->enableSearching));
	}

	/**
	 * @param  string      $tableName
	 * @param  boolean     $hasPager
	 * @param  boolean     $hasDragAndDrop
	 * @param  sly_Request $request         the request to use or null for the global one
	 * @return array
	 */
	public static function getPagingParameters($tableName = 'table', $hasPager = false, $hasDragAndDrop = false, sly_Request $request = null) {
		$perPage = self::$perPage;

		$request  = $request ? $request : sly_Core::getRequest();
		$page     = $request->get('p_'.$tableName, 'int', 0);
		$start    = $page * $perPage;
		$elements = $perPage;
		$end      = $start + $perPage;
		$getAll   = array('page' => 0, 'start' => 0, 'end' => PHP_INT_MAX, 'elements' => PHP_INT_MAX);

		if (!$hasPager || ($hasDragAndDrop && self::isDragAndDropMode())) {
			return $getAll;
		}

		return array('page' => $page, 'start' => $start, 'end' => $end, 'elements' => $elements);
	}

	/**
	 * @param  string      $tableName
	 * @param  sly_Request $request    the request to use or null for the global one
	 * @return string
	 */
	public static function getSearchParameters($tableName = 'table', sly_Request $request = null) {
		$request = $request ? $request : sly_Core::getRequest();
		return $request->get('q_'.$tableName, 'string');
	}

	/**
	 * @param  string      $tableName
	 * @param  string      $defaultColumn
	 * @param  array       $enabledColumns
	 * @param  string      $defaultDirection
	 * @param  sly_Request $request           the request to use or null for the global one
	 * @return array
	 */
	public static function getSortingParameters($tableName, $defaultColumn, $enabledColumns = array(), $defaultDirection = 'asc', sly_Request $request = null) {
		// support the old interface: get($defaultColumn, $enabledColumns)
		if (empty($enabledColumns) && is_array($defaultColumn)) {
			$enabledColumns = $defaultColumn;
			$defaultColumn  = $tableName;
			$tableName      = 'table';
		}

		$request   = $request ? $request : sly_Core::getRequest();
		$sortby    = $request->get($tableName.'_sortby', 'string', $defaultColumn);
		$direction = strtolower($request->get($tableName.'_direction', 'string', $defaultDirection)) === 'desc' ? 'DESC' : 'ASC';

		if (!in_array($sortby, $enabledColumns)) {
			$sortby = $defaultColumn;
		}

		return array('sortby' => $sortby, 'direction' => $direction);
	}

	/**
	 * @throws sly_Exception
	 * @param  string $file
	 * @return string
	 */
	protected function getViewFile($file) {
		$full = SLY_COREFOLDER.'/views/table/'.$file;
		if (file_exists($full)) return $full;

		throw new sly_Exception(t('view_not_found', $file));
	}

	/**
	 * Add a new class to the form
	 *
	 * This method will add a CSS class to the form tag. You can give mutliple
	 * classes at once, the method will split them up and add them one at a time,
	 * ensuring that they are unique.
	 *
	 * @param  string $class  the CSS class
	 * @return array          the list of current classes (unique)
	 */
	public function addClass($class) {
		$class = explode(' ', $class);
		foreach ($class as $c) $this->classes[] = $c;
		$this->classes = array_unique($this->classes);
		return $this->classes;
	}

	/**
	 * Remove all classes
	 *
	 * This method removes all set CSS classes for this form.
	 */
	public function clearClasses() {
		$this->classes = array();
	}

	/**
	 * Get all classes
	 *
	 * @return array  the list of CSS classes for this form
	 */
	public function getClasses() {
		return $this->classes;
	}
}
