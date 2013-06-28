<?php

	date_default_timezone_set('Europe/Stockholm');

	require_once('../classes/autoload.php');

	$response = new stdClass();
	$response->code = 501;

	if(isset($_GET['uri'])) {
		list($domain, $id) = explode('/', $_GET['uri']);
		error_log('Domain: ' . $domain . ', ID: ' . $id);
		$className = 'API\\' . $domain;
		if(class_exists($className)) {
			$class = new $className;
			try {
				$class->Request($response, $id);
			} catch(Exception $e) {
				error_log($e);
				error_log($e->getTraceAsString());
				$response->code = $e->getCode();
				$response->message = $e->getMessage();
			}
		}
	}

	http_response_code($response->code);
	header('Content-type: application/json');

	if(isset($response->data)) {
		echo(json_encode($response->data));
	} else {
		echo(json_encode($response));
	}

?>