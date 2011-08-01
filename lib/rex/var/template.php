<?php
/*
 * Copyright (C) 2009 REDAXO
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License Version 2 as published by the
 * Free Software Foundation.
 */

/**
 * REX_TEMPLATE[mytemplate]
 *
 * @ingroup redaxo
 */
class rex_var_template extends rex_var {
	// --------------------------------- Output

	public function getTemplate($content) {
		return $this->matchTemplate($content);
	}

	/**
	 * Wert für die Ausgabe
	 */
	public function matchTemplate($content) {
		$var        = 'SLY_TEMPLATE';
		$matches    = $this->getVarParams($content, $var);
		$tplService = sly_Service_Factory::getTemplateService();

		foreach ($matches as $match) {
			list ($param_str, $args)   = $match;
			list ($template_id, $args) = $this->extractArg('id', $args, 0);

			if (!empty($template_id) && $tplService->exists($template_id)) {
				$tpl = "<?php\n\$tplService = sly_Service_Factory::getTemplateService();";

				if (isset($args['callback'])) {
					$tpl .= "\n".'$args[\'subject\'] = file_get_contents($tplService->getContent(\'$template_id\'));';
					$tpl .= "\n".'eval(\'?>\'.call_user_func(unserialize("'.serialize($args['callback']).'", $args));';
				}
				else {
					$tpl .= "\n".'$tplService->includeFile(\''.$template_id.'\');';
				}

				$tpl .= "\n".'?>';

				$content = str_replace($var.'['.$param_str.']', $tpl, $content);
			}
		}

		return $content;
	}
}
