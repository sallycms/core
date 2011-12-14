<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * HTTP1.1 Client Cache Features
 *
 * @package redaxo4
 */

/**
 * Sendet einen sly_Model_Article zum Client
 *
 * @param sly_Model_Article $article      der zu sendene Artikel
 * @param string            $content      Inhalt des Artikels
 * @param string            $environment  die Umgebung aus der der Inhalt gesendet wird (frontend/backend)
 */
function rex_send_article($article, $content, $environment) {
	// if no content is given, close all buffer and collect the output

	if ($content === null) {
		$content = '';
		while (ob_get_level()) $content = ob_get_clean().$content;
	}

	$config     = sly_Core::config();
	$dispatcher = sly_Core::dispatcher();
	$response   = sly_Core::getResponse();

	$content = $dispatcher->filter('OUTPUT_FILTER', $content, compact('environment'));
	$response->setContent($content);

	// check if this is a 404 article and set HTTP status accordingly
	// (This works only for projects not using a realurl implementation.)

	if ($article) {
		$lastModified = $article->getUpdateDate();
		$requestedID  = sly_request('article_id', 'int');
		$notFoundID   = sly_Core::getNotFoundArticleId();
		$startID      = sly_Core::getSiteStartArticleId();

		if ($requestedID !== $notFoundID && $article->getId() === $notFoundID && $article->getId() !== $startID) {
			$response->setStatusCode(404);
		}
	}
	else {
		$lastModified = time();
	}

	$etag    = substr(md5($content), 0, 12);
	$lastMod = $config->get('USE_LAST_MODIFIED');
	$useEtag = $config->get('USE_ETAG');
	$check   = false;

	if ($lastMod === true || $lastMod == $environment) { $check = true; $response->setLastModified($lastModified); }
	if ($useEtag === true || $useEtag == $environment) { $check = true; $response->setEtag($etag);                 }

	if ($check) $response->isNotModified();

	// and send it
	$response->send();
}
