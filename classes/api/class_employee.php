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
				$data = json_decode(file_get_contents('php://input'), true);
				error_log('Input' . print_r($data, true));

				$instance = new \Employee();
				$instance->Name = $data['Name'];
				try {
					$address = Address::GetDuplicate($data['Street'], $data['Zip'], $data['City'], $data['Country']);
				} catch(Exception $e) {
					$address = new Address();
					$address->Street = $data['Address']['Street'];
					$address->Zip = $data['Address']['Zip'];
					$address->City = $data['Address']['City'];
					$address->Country = $data['Address']['Country'];
				}
				$instance->Address = $address;
				$instance->StartAt = new DateTime($data['StartAt']);
				if(isset($data['EndAt'])) {
					$instance->EndAt = new DateTime($data['EndAt']);
				}
				$instance->Email = $data['Email'];
				$instance->Phone = $data['Phone'];
				if(isset($data['Unit'])) {
					$instance->Unit = Unit::GetById($data['Unit']);
				}
				error_log(print_r($instance, true));
				if($instance->Commit()) {
					$response->code = 200;
					$resposne->data = $instance;
				}
			}
		}
	}

?>