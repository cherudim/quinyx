<?php

	namespace API {
		use stdClass;


		class Address extends Base {
			public function Get($id, stdClass $response) {
				$response->code = 200;
				$response->data = \Address::GetById($id)->AsArray();
			}
		}
	}

?>