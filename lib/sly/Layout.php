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
 * Base class for layouts
 *
 * Layouts are responsible for handling and rendering the HTML head and footer.
 * This class lays out the general API for all concrete layouts (like XHTML
 * or XHTML5).
 *
 * @ingroup layout
 * @author  Zozi
 */
abstract class sly_Layout extends sly_Viewable {
	protected $title           = '';       ///< string
	protected $cssCode         = '';       ///< string
	protected $javaScriptCode  = '';       ///< string
	protected $favIcon         = null;     ///< string
	protected $cssFiles        = array();  ///< array
	protected $javaScriptFiles = array();  ///< array
	protected $feedFiles       = array();  ///< array
	protected $bodyAttrs       = array();  ///< array
	protected $httpMetas       = array();  ///< array
	protected $metas           = array();  ///< array
	protected $links           = array();  ///< array
	protected $content         = '';       ///< string
	protected $base            = '';       ///< string

	/**
	 * Open a new buffer
	 *
	 * This method is just a wrapper for ob_start().
	 */
	public function openBuffer() {
		ob_start();
	}

	/**
	 * Close the buffer
	 *
	 * This method closes the buffer and stores the output inside the content
	 * field of this instance.
	 */
	public function closeBuffer() {
		$this->content = ob_get_clean();
	}

	/**
	 * Close all buffers
	 */
	public function closeAllBuffers() {
		while (ob_get_level()) ob_end_clean();
	}

	/**
	 * Set the page content directly
	 *
	 * @param string $content
	 */
	public function setContent($content) {
		$this->content = trim($content);
	}

	/**
	 * Render the page
	 *
	 * This method starts a buffer, prints the header, content and footer and
	 * then returns the complete page's content.
	 *
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
	 * Set the page title
	 *
	 * @param string $title  the new title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Append something to the title
	 *
	 * @param string $title  the string to append to the current title
	 */
	public function appendToTitle($title) {
		$this->title .= $title;
	}

	/**
	 * Set the fav icon
	 *
	 * @param string $iconPath  the full URI to the favicon
	 */
	public function setFavIcon($iconPath) {
		$this->favIcon = trim($iconPath);
	}

	/**
	 * Set the base URI
	 *
	 * @param string $base  the base URI
	 */
	public function setBase($base) {
		$this->base = trim($base);
	}

	/**
	 * Add inline CSS to the page
	 *
	 * Use this method if you have to generate dynamic CSS and add it directly to
	 * the page, using a <style> tag. All added inline CSS will be printed in a
	 * single <style> tag.
	 *
	 * @param string $css  the inline CSS code
	 */
	public function addCSS($css) {
		$css = trim($css);
		$this->cssCode .= "\n$css";
	}

	/**
	 * Add CSS file
	 *
	 * This method adds a new CSS file to the layout. Files will be put into
	 * groups, so that addOns can partially access them. Files must be unique
	 * (or else the method returns false).
	 *
	 * @param  string $cssFile  path to css file
	 * @param  string $media    media attribute für den CSS link
	 * @param  string $group    group files by this param
	 * @return boolean          true if the file was added, false if it already existed
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
	 * Add inline JavaScript to the page
	 *
	 * Use this method if you have to generate dynamic JS and add it directly to
	 * the page, using a <script> tag. All added inline JS will be printed in a
	 * single <script> tag.
	 *
	 * @param string $javascript  the inline JavaScript code
	 */
	public function addJavaScript($javascript) {
		$javascript = trim($javascript);
		$this->javaScriptCode .= "\n$javascript";
	}

	/**
	 * Add JavaScript file
	 *
	 * This method adds a new JS file to the layout. Files will be put into
	 * groups, so that addOns can partially access them. Files must be unique
	 * (or else the method returns false).
	 *
	 * @param  string $cssFile  path to js file
	 * @param  string $group    group files by this param
	 * @return boolean          true if the file was added, false if it already existed
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
	 * Add an attribute to the body tag
	 *
	 * Attributes beginning with 'on' will not be added to the tag, but rather
	 * as JavaScript event handler using inline JavaScript.
	 *
	 * @param string $name   attribute name
	 * @param string $value  attribute value
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
	 * Get body attribute(s)
	 *
	 * @param  string $name  the attribute name or null for 'all'
	 * @return mixed         either an array or a string
	 */
	public function getBodyAttr($name = null) {
		return ($name && isset($this->bodyAttrs[$name])) ? $this->bodyAttrs[$name] : $this->bodyAttrs;
	}

	/**
	 * Appends a class name to the body
	 *
	 * @param string $class  a single or multiple classes as a string (like 'foo bar')
	 */
	public function appendBodyClass($class) {
		$classes = $this->getBodyAttr('class');
		$classes = $classes ? explode(' ', $classes) : array();

		foreach (explode(' ', $class) as $cl) {
			$classes[] = $cl;
		}

		$this->setBodyAttr('class', implode(' ', array_unique($classes)));
	}

	/**
	 * Add meta tag
	 *
	 * Adds a regular meta tag to the page header.
	 *
	 * @param string $name     meta name
	 * @param string $content  content attribute of the tag
	 */
	public function addMeta($name, $content) {
		$this->metas[trim($name)] = trim($content);
	}

	/**
	 * Add a http-equiv meta tag
	 *
	 * Adds a meta tag for HTTP equivalents to the page header. Use this to
	 * specify the content-type.
	 *
	 * @param string $name     meta name
	 * @param string $content  content attribute of the tag
	 */
	public function addHttpMeta($name, $content) {
		$this->httpMetas[trim($name)] = trim($content);
	}

	/**
	 * Add generic link
	 *
	 * This methods adds a generic <link> tag to the head. Use specialized
	 * methods (like addCSSFile) whenever possible. Note that the links are not
	 * made unique!
	 *
	 * @param string $rel    rel attribute value
	 * @param string $href   href attribute value
	 * @param string $type   type attribute value
	 * @param string $title  title attribute value
	 */
	public function addLink($rel, $href, $type = '', $title= '') {
		$this->links[] = array('rel' => trim($rel), 'href' => trim($href), 'type' => trim($type), 'title' => trim($title));
	}

	/**
	 * Add a feed
	 *
	 * This method is a specialized version of addLink() and adds a RSS/Atom link
	 * to the page header, automatically setting the title and type.
	 *
	 * @param string $feedFile  the URL to the feed
	 * @param string $type      the type (rss, rss1, rss2 or atom)
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
	 * Write the inline CSS
	 *
	 * This method will filter the inline CSS with the event HEADER_CSS and, if
	 * it's not empty, writes it by calling the layout specific
	 * printCSSConcrete() method.
	 */
	protected function printCSS() {
		$this->cssCode = sly_Core::dispatcher()->filter('HEADER_CSS', $this->cssCode);
		if (!empty($this->cssCode)) $this->printCSSConcrete();
	}

	/**
	 * Write the CSS files
	 *
	 * This method will filter the CSS files with the event HEADER_CSS_FILES and,
	 * if they're not empty, write them by calling the layout specific
	 * printCSSFilesConcrete() method.
	 */
	protected function printCSSFiles() {
		$this->cssFiles = sly_Core::dispatcher()->filter('HEADER_CSS_FILES', $this->cssFiles);
		$this->printCSSFilesConcrete();
	}

	/**
	 * Write the inline JavaScript
	 *
	 * This method will filter the inline JavaScript with the event
	 * HEADER_JAVASCRIPT and, if it's not empty, writes it by calling the layout
	 * specific printJavaScriptConcrete() method.
	 */
	protected function printJavaScript() {
		$this->javaScriptCode = sly_Core::dispatcher()->filter('HEADER_JAVASCRIPT', $this->javaScriptCode);
		if (!empty($this->javaScriptCode)) $this->printJavaScriptConcrete();
	}

	/**
	 * Write the JavaScript files
	 *
	 * This method will filter the JS files with the event
	 * HEADER_JAVASCRIPT_FILES and, if they're not empty, write them by calling
	 * the layout specific printJavaScriptFilesConcrete() method.
	 */
	protected function printJavaScriptFiles() {
		$this->javaScriptFiles = sly_Core::dispatcher()->filter('HEADER_JAVASCRIPT_FILES', $this->javaScriptFiles);
		$this->printJavaScriptFilesConcrete();
	}

	/**
	 * Print all links
	 *
	 * This function only loops over all links and calls printLink() for each
	 * one.
	 */
	protected function printLinks() {
		foreach ($this->links as $link) {
			$this->printLink($link);
		}
	}

	/**
	 * Print the inline CSS code
	 */
	protected abstract function printCSSConcrete();

	/**
	 * Print the list of CSS files
	 */
	protected abstract function printCSSFilesConcrete();

	/**
	 * Print the inline JavaScript code
	 */
	protected abstract function printJavaScriptConcrete();

	/**
	 * Print the list of JS files
	 */
	protected abstract function printJavaScriptFilesConcrete();

	/**
	 * Print the body attributes
	 */
	protected abstract function printBodyAttrs();

	/**
	 * Print regular meta tag
	 */
	protected abstract function printMetas();

	/**
	 * Print HTTP meta tag
	 */
	protected abstract function printHttpMetas();

	/**
	 * Prints a <link> tag
	 *
	 * @param array $attributes  a hash with all attributes (name => value)
	 */
	protected abstract function printLink($attributes);

	/**
	 * Print the header
	 *
	 * Starts the page by writing the html, head, title and body tag (no meta,
	 * no doctype, no links, no script, ...). Most layouts will override this
	 * method.
 	 */
	public function printHeader() {
		print '<html><head><title>'.sly_html(trim($this->title)).'</title></head><body>';
	}

	/**
	 * Print the footer
	 *
	 * Prints the closing body and html tags.
	 */
	public function printFooter() {
		$this->printJavaScriptFiles();
		$this->printJavaScript();
		print '</body></html>';
	}

	/**
	 * Get the full path for a view
	 *
	 * This methods prepends the filename of a specific view with its path. If
	 * the view is not found inside the core, an exception is thrown.
	 *
	 * @throws sly_Exception  if the view could not be found
	 * @param  string $file   the relative filename
	 * @return string         the full path to the view file
	 */
	protected function getViewFile($file) {
		$full = SLY_COREFOLDER.'/views/'.$file;
		if (file_exists($full)) return $full;

		throw new sly_Exceptiont(t('view_not_found', $file));
	}
}
