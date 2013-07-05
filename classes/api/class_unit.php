<?php

	namespace API {
		use stdClass;
		use UnitCollection;
		use Employee;
		use Address;
		use Exception;

		class Unit extends Base {
			public function Get($id, stdClass $response) {
				$response->code = 200;
				$response->data = \Unit::GetById($id)->AsArray();
			}

			public function CollectionGet(stdClass $response) {
				$response->data = UnitCollection::GetAll()->AsArray();
				$response->code = 200;
			}

			public function CollectionPost(stdClass $response) {
				$instance = new \Unit();

				if($this->updateUnit($instance)) {
					$response->code = 200;
					$response->data = $instance->AsArray();
				}
			}

			public function Put($id, stdClass $response) {
				$instance = \Unit::GetById($id);

				if($this->updateUnit($instance)) {
					$response->code = 200;
					$response->data = $instance->AsArray();
				}
			}

			/**
			 * Sets the received data to the provided Unit instance.
			 * @param Unit $instance Unit instance to be updated.
			 */

			protected function updateUnit(\Unit $instance) {
				$data = json_decode(file_get_contents('php://input'), true);

				$instance->Name = $data['Name'];
				try {
					$address = Address::GetDuplicate($data['Address']['Street'], $data['Address']['Zip'], $data['Address']['City'], $data['Address']['Country']);
				} catch(Exception $e) {
					$address = new Address();
					$address->Street = $data['Address']['Street'];
					$address->Zip = $data['Address']['Zip'];
					$address->City = $data['Address']['City'];
					$address->Country = $data['Address']['Country'];
				}
				$instance->Address = $address;
				$instance->Description = $data['Description'];
				$instance->ChiefEmployee = Employee::GetById($data['ChiefEmployee']);
				if($instance->Commit()) {
					return true;
				}
				return false;
			}

			public function Delete($id, stdClass $response) {
				$instance = \Unit::GetById($id);
				if($instance->Delete()) {
					$response->code = 200;
					$response->data = $instance->AsArray();
				}
			}
		}
	}

?>