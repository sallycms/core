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
 * XHTML layout
 *
 * This class generates a XHTML 1.0 Strict valid page and is the base for all
 * backend pages. Transitional pages are possible, but have to be enabled.
 *
 * @ingroup layout
 * @author  Zozi
 */
class sly_Layout_XHTML extends sly_Layout {
	protected $isTransitional = false; ///< boolean  transitional flag
	protected $language; ///< string

	/**
	 * Set the page to be transitional
	 *
	 * This method changes the generated DOCTYPE of the resulting page.
	 *
	 * @param boolean $isTransitional  true or false, it's your choice
	 */
	public function setTransitional($isTransitional = true) {
		$this->isTransitional = (boolean) $isTransitional;
	}

	/**
	 * Set the document language
	 *
	 * @param string $language  the short locale (de, en, fr, ...)
	 */
	public function setLanguage($language) {
		$this->language = $language;
	}

	/**
	 * Print the inline CSS code
	 *
	 * This method generates a normal <style> tag containing the inline CSS.
	 */
	protected function printCSSConcrete() {
		print "\t<style type=\"text/css\">$this->cssCode</style>\n";
	}

	protected function printCSSFilesConcrete() {
		foreach ($this->cssFiles as $group => $medias) {
			$isConditional = strtoupper(substr($group, 0, 3)) == 'IF ';

			if ($isConditional) print "\t<!--[if ".strtoupper(substr($group, 3))."]>\n";

			foreach ($medias as $media => $files) {
				foreach ($files as $file) {
					print "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"$file[src]\" media=\"$media\" />\n";
				}
			}

			if ($isConditional) print "\t<![endif]-->\n";
		}
	}

	/**
	 * Print the inline JavaScript code
	 *
	 * This method generates a normal <script> tag containing the inline JS.
	 */
	protected function printJavaScriptConcrete() {
		print "\t".'<script type="text/javascript">/*<![CDATA[*/'.trim($this->javaScriptCode).'/*]]>*/</script>'."\n";
	}

	protected function printJavaScriptFilesConcrete() {
		foreach ($this->javaScriptFiles as $group => $files) {
			$isConditional = strtoupper(substr($group, 0, 3)) == 'IF ';

			foreach ($files as $idx => $file) {
				$files[$idx] = sly_html($file);
			}

			if ($isConditional) print '<!--[if '.strtoupper(substr($group, 3)).']>'."\n";
			print "\t".'<script type="text/javascript" src="'.join('"></script>'."\n\t".'<script type="text/javascript" src="' , $files).'"></script>'."\n";
			if ($isConditional) print '<![endif]-->'."\n";
		}
	}

	protected function printBodyAttrs() {
		$this->printHeadElements(' %s="%s"', $this->bodyAttrs);
	}

	protected function printMetas() {
		$this->printHeadElements("\t".'<meta name="%s" content="%s" />'."\n", $this->metas);
	}

	protected function printHttpMetas() {
		$this->printHeadElements("\t".'<meta http-equiv="%s" content="%s" />'."\n", $this->httpMetas);
	}

	/**
	 * @param array $attributes
	 */
	protected function printLink($attributes) {
		print "\t".'<link '.sly_Util_HTML::buildAttributeString($attributes)."/>\n";
	}

	public function printHeader() {
		print $this->renderView('layout/xhtml/head.phtml');
	}

	/**
	 * @param string $format
	 * @param array  $data
	 */
	private function printHeadElements($format, $data) {
		foreach ($data as $key => $value) {
			printf($format, sly_html(trim($key)), sly_html(trim($value)));
		}
	}
}
