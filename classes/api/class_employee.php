<?php

	namespace API {
		use stdClass;
		use Address;
		use Unit;
		use Exception;
		use DateTime;
		use EmployeeCollection;

		class Employee extends Base {
			public function Get($id, stdClass $response) {
				$response->code = 200;
				$response->data = \Employee::GetById($id)->AsArray();
			}

			public function CollectionGet(stdClass $response) {
				$response->data = EmployeeCollection::GetAll()->AsArray();
				$response->code = 200;
			}

			public function CollectionPost(stdClass $response) {
				$instance = new \Employee();

				if($this->updateEmployee($instance)) {
					$response->code = 200;
					$response->data = $instance->AsArray();
				}
			}

			public function Put($id, stdClass $response) {
				$instance = \Employee::GetById($id);

				if($this->updateEmployee($instance)) {
					$response->code = 200;
					$response->data = $instance->AsArray();
				}
			}

			protected function updateEmployee(\Employee $instance) {
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
				$instance->StartAt = new DateTime($data['StartAt']);
				if(isset($data['EndAt']) && $data['EndAt']) {
					$instance->EndAt = new DateTime($data['EndAt']);
				} else {
					$instance->EndAt = null;
				}
				$instance->Email = $data['Email'];
				$instance->Phone = $data['Phone'];
				$instance->BornAt = new DateTime($data['BornAt']);
				if(isset($data['Unit'])) {
					try {
						$instance->Unit = Unit::GetById($data['Unit']);
					} catch(Exception $e) {
						$instance->Unit = null;
					}
				}
				if($instance->Commit()) {
					return true;
				}
				return false;
			}

			public function Delete($id, stdClass $response) {
				$instance = \Employee::GetById($id);
				if($instance->Delete()) {
					$response->code = 200;
					$response->data = $instance->AsArray();
				}
			}
		}
	}

?>