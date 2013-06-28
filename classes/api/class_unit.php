<?php

	namespace API {
		use stdClass;

		class Unit extends Base {
			public function Get($id, stdClass $response) {
				$response->code = 200;
				$response->data = \Unit::GetById($id)->AsArray();
			}
		}
	}

?>