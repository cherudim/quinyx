<?php

	namespace API {
		use stdClass;
		use Exception;

		abstract class Base {
			const GET = 'get';
			const PUT = 'put';
			const POST = 'post';
			const DELETE = 'delete';

			/**
			 * Determined what kind of request was received
			 * @return string Returns a constant for the received method.
			 */

			protected function getMethod() {
				switch(strtolower($_SERVER['REQUEST_METHOD'])) {
					case 'put':
						return self::PUT;
						break;
					case 'post':
						return self::POST;
						break;
					case 'delete':
						return self::DELETE;
						break;
				}
				return self::GET;
			}

			protected function hasMethod($method) {
				return method_exists($this, $method);
			}

			/**
			 * Routes the request to the correct class method based on the http method.
			 * @throws Exception Exceptino with code 501 if the requested method does not exist.
			 */

			public function Request(stdClass $request, $id) {
				$methodName = ucfirst($this->getMethod());
				if(!$id) {
					$methodName = 'Collection' . $methodName;
				}
				if($this->hasMethod($methodName)) {
					if(!$id) {
						$this->$methodName($request);
					} else {
						$this->$methodName($id, $request);
					}
				} else {
					throw new Exception('Method ' . $methodName . ' is not implemented', 501);
				}
			}
		}
	}

?>