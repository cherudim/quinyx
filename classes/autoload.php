<?php

	function name2path($name) {
		return trim(strtolower(preg_replace('#(?|([a-z])(?=[A-Z0-9])|([A-Z0-9]+)(?=[A-Z0-9][a-z]))#', '$1_', $name)), '_');
	}

	function autoload_classes($class_name) {
		$path = explode('\\' ,$class_name);
		$file = 'class_' . name2path(array_pop($path)) . '.php';
		$path = array_map('name2path', $path);
		$path = dirname(__FILE__) . DIRECTORY_SEPARATOR . (count($path) > 0 ? implode(DIRECTORY_SEPARATOR , $path) . DIRECTORY_SEPARATOR : '') . $file;
		//error_log('Include file: ' . $path);
		if(file_exists($path)) {
			include $path;
		}
		return false;
	}

	spl_autoload_register('autoload_classes', true);

?>