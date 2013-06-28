<?php

	use System\DatabaseObject;

	class Unit extends DatabaseObject {
		protected $_Address = self::IdField;
		protected $_Name;
		protected $_Description;
		protected $_ChiefEmployee = self::IdField;

		protected function getAddress() {
			if(!($this->_Address instanceof Address)) {
				$this->_Address = Address::GetById($this->_Address);
			}
			return $this->_Address;
		}

		protected function getChiefEmployee() {
			if(!($this->_ChiefEmployee instanceof Employee)) {
				$this->_ChiefEmployee = Employee::GetById($this->_ChiefEmployee);
			}
			return $this->_ChiefEmployee;
		}

		protected function setAddress(Address $address) {
			$this->_Address = $address;
		}

		protected function setChiefEmployee(Employee $employee) {
			$this->_ChiefEmployee = $employee;
		}
	}

?>