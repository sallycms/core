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
 * @ingroup layout
 */
abstract class sly_Layout extends sly_Viewable {
	protected $title           = '';
	protected $cssCode         = '';
	protected $javaScriptCode  = '';
	protected $favIcon         = null;
	protected $cssFiles        = array();
	protected $javaScriptFiles = array();
	protected $feedFiles       = array();
	protected $bodyAttrs       = array();
	protected $httpMetas       = array();
	protected $metas           = array();
	protected $links           = array();
	protected $content         = '';

	public function openBuffer() {
		ob_start();
	}

	public function closeBuffer() {
		$this->content = ob_get_clean();
	}

	public function closeAllBuffers() {
		while (ob_get_level()) ob_end_clean();
	}

	/**
	 * @return string
	 */
	public function render() {
		ob_start();
		$this->printHeader();
		print $this->content;
		$this->printFooter();
		return ob_get_clean();
	}

	/**
	 * Setzt den Inhalt den title Tags
	 *
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Erweitert den Inhalt des title Tags.
	 *
	 * @param string $title
	 */
	public function appendToTitle($title) {
		$this->title .= $title;
	}

	/**
	 * Setzt den pfad zu favicon
	 *
	 * @param string $iconPath
	 */
	public function setFavIcon($iconPath) {
		$this->favIcon = trim($iconPath);
	}

	/**
	 * Füegt den übergebenen String dem css code des layouts zu.
	 *
	 * @param string $css
	 */
	public function addCSS($css) {
		$css = trim($css);
		$this->cssCode .= "\n$css";
	}

	/**
	 * Fügt dem layout eine css datei zu. CSS Dateien werden Gruppen zugeordnet,
	 * Funktionen der Gruppen siehe printCSSFiles().
	 *
	 * @param string $cssFile  path to css file
	 * @param string $media    media Attribut für den CSS Link
	 * @param string $group    group css files by this param
	 */
	public function addCSSFile($cssFile, $media = 'all', $group = 'default') {
		$cssFile = trim($cssFile);

		foreach ($this->cssFiles as $files) {
			foreach ($files as $list) {
				foreach ($list as $file) {
					if ($file['src'] == $cssFile) return false;
				}
			}
		}

		$this->cssFiles[trim($group)][trim($media)][] = array('src' => $cssFile);
		return true;
	}

	/**
	 * Füegt den übergebenen String dem javascript code des layouts zu.
	 *
	 * @param string $javascript
	 */
	public function addJavaScript($javascript) {
		$javascript = trim($javascript);
		$this->javaScriptCode .= "\n$javascript";
	}

	/**
	 * Fügt dem layout eine Javascript datei zu. Javascript Dateien werden Gruppen zugeordnet,
	 * Funktionen der Gruppen siehe printJavaScriptFiles().
	 *
	 * @param string $javascriptFile path to javascript file
	 * @param string $group group javascript files by this param
	 */
	public function addJavaScriptFile($javascriptFile, $group = 'default') {
		$javascriptFile = trim($javascriptFile);

		foreach ($this->javaScriptFiles as $files) {
			if (in_array($javascriptFile, $files)) return false;
		}

		$this->javaScriptFiles[trim($group)][] = $javascriptFile;
		return true;
	}

	/**
	 * Fügt dem body tag des Layouts HTML Attribute hinzu.
	 * Attrributnamen, die mit 'on' beginnen werden statt inline gesetzt zu werden,
	 * als funktion window.on... dem Jvascript Code des Layouts zugefügt.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setBodyAttr($name, $value) {
		$name  = trim($name);
		$value = trim($value);

		if (sly_Util_String::startsWith($name, 'on')) {
			$this->addJavaScript('window.'.$name.' = function() { '.$value.' }');
		}
		else {
			$this->bodyAttrs[$name] = $value;
		}
	}

	/**
	 * Fügt dem Layout eine Metainformation hinzu.
	 *
	 * @param string $name
	 * @param string $content
	 */
	public function addMeta($name, $content) {
		$this->metas[trim($name)] = trim($content);
	}

	/**
	 * Fügt dem Layout eine HTTP Metainformation hinzu.
	 *
	 * @param string $name
	 * @param string $content
	 */
	public function addHttpMeta($name, $content) {
		$this->httpMetas[trim($name)] =trim($content);
	}

	/**
	 * Fügt dem Layout einen Link hinzu.
	 *
	 * @param string $rel
	 * @param string $href
	 * @param string $type
	 */
	public function addLink($rel, $href, $type = '', $title= '') {
		$this->links[] = array('rel' => trim($rel), 'href' => trim($href), 'type' => trim($type), 'title' => trim($title));
	}

	/**
	 * Fügt dem Layout einen Feed Link hinzu.
	 * Momentan werden RSS und Atom Feeds unterstützt
	 *
	 * @param string $feedFile
	 * @param string $type
	 */
	public function addFeedFile($feedFile, $type = '') {
		if (!in_array($type, array('rss', 'rss1', 'rss2', 'atom'))) {
			$type = 'rss';
		}

		static $types  = array('rss' => 'rss', 'rss1' => 'rss', 'rss2' => 'rss', 'atom' => 'atom');
		static $titles = array('rss' => 'RSS-Feed', 'rss1' => 'RSS-Feed 1.0', 'rss2' => 'RSS-Feed 2.0', 'atom' => 'Atom-Feed');

		$title = $titles[$type];
		$type  = 'application/'.$types[$type].'+xml';

		$this->addLink('alternate', $feedFile, $type, $title);
	}

	/**
	 * Schreibt css Styles in den Header
	 * Ruft den Extension Point HEADER_CSS auf und übergibt
	 * den CSS Code. Der vom Extension Point zurückgegebne
	 * Wert wird in das <style> Tag geschrieben. Zum schreiben wird
	 * die abstrakte Methode printCSSConcrete() aufgerufen.
	 */
	protected function printCSS() {
		$this->cssCode = sly_Core::dispatcher()->filter('HEADER_CSS', $this->cssCode);
		if (!empty($this->cssCode)) $this->printCSSConcrete();
	}

	/**
	 * Schreint den inhalt von $this->cssCode in den Header.
	 * Dabei werden layoutspezifische eigenheiten beachtet.
	 */
	protected abstract function printCSSConcrete();

	/**
	 * Schreibt CSS Files in den Header
	 * Ruft den Extension Point HEADER_CSS_FILES auf und übergibt
	 * die zugefügten CSS Dateien. Die vom Extension Point zurückgegebenen
	 * Dateien wird in den Header geschrieben. Zum Schreiben wird
	 * die abstrakte Methode printCSSFilesConcrete() aufgerufen.
	 */
	protected function printCSSFiles() {
		$this->cssFiles = sly_Core::dispatcher()->filter('HEADER_CSS_FILES', $this->cssFiles);
		$this->printCSSFilesConcrete();
	}

	/**
	 * Schreibt CSS Files ($this->cssFiles) in den Header
	 */
	protected abstract function printCSSFilesConcrete();

	/**
	 * Schreibt Javascript Code in den Header
	 * Ruft den Extension Point HEADER_JAVASCRIPT auf und übergibt
	 * den Javascript Code. Der vom Extension Point zurückgegebne
	 * Wert wird in den HJeader. Zum schreiben wird
	 * die abstrakte Methode printJavaScriptConcrete() aufgerufen.
	 *
	 */
	protected function printJavaScript() {
		$this->javaScriptCode = sly_Core::dispatcher()->filter('HEADER_JAVASCRIPT', $this->javaScriptCode);
		if (!empty($this->javaScriptCode)) $this->printJavaScriptConcrete();
	}

	/**
	 * Schreint den inhalt von $this->javaScriptCode in den Header.
	 * Dabei werden layoutspezifische eigenheiten beachtet.
	 */
	protected abstract function printJavaScriptConcrete();

	/**
	 * Schreibt Javascript Files in den Header
	 * Ruft den Extension Point HEADER_JAVASCRIPT_FILES auf und übergibt
	 * die zugefügten JS Dateien. Die vom Extension Point zurückgegebenen
	 * Dateien wird in den Header geschrieben. Zum Schreiben wird
	 * die abstrakte Methode printJavascriptFilesConcrete() aufgerufen.
	 *
	 */
	protected function printJavaScriptFiles() {
		$this->javaScriptFiles = sly_Core::dispatcher()->filter('HEADER_JAVASCRIPT_FILES', $this->javaScriptFiles);
		$this->printJavaScriptFilesConcrete();
	}
	/**
	 * Schreibt Javascript Files ($this->javaScriptFiles) in den Header
	 */
	protected abstract function printJavaScriptFilesConcrete();

	/**
	 * Schreibt die zugefügten body Attribute
	 */
	protected abstract function printBodyAttrs();

	/**
	 * Schreibt Metainformationen in den Header
	 */
	protected abstract function printMetas();

	/**
	 * Schreibt HTTP Metainformationen in den Header
	 */
	protected abstract function printHttpMetas();

	/**
	 * Schreibt die zugefügten Links in den Header
	 */
	protected function printLinks() {
		foreach ($this->links as $link) {
			$this->printLink($link);
		}
	}

	/**
	 * Schreibt einen Link in den Header
	 *
	 * @param array $attributes  ein Hash mit Attributen ($name => $value)
	 */
	protected abstract function printLink($attributes);

	/**
	 * Schreibt den Header
 	 */
	public function printHeader() {
		print '<html><head><title>'.$this->title.'</title></head><body>';
	}

	/**
	 * Schreibt den Footer
	 */
	public function printFooter() {
		print '</body></html>';
	}

	protected function getViewFile($file) {
		$full = SLY_COREFOLDER.'/views/'.$file;
		if (file_exists($full)) return $full;

		throw new sly_Exception('View '.$file.' could not be found.');
	}
}
