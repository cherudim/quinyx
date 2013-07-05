<?php

	use System\DatabaseObject;

	class Unit extends DatabaseObject {
		protected $_Address = self::IdField;
		protected $_Name;
		protected $_Description;
		protected $_ChiefEmployee = self::IdField;

		protected $employees;

		/**
		 * Loads and returns an instance of Address based on address_id from the database.
		 * @return Address Instance of Address.
		 */

		protected function getAddress() {
			if(!($this->_Address instanceof Address)) {
				$this->_Address = Address::GetById($this->_Address);
			}
			return $this->_Address;
		}

		/**
		 * Loads and returns an instance of Employee based on chief_employee_id from the database.
		 * @return Employee Instance of Address.
		 */

		protected function getChiefEmployee() {
			if(!($this->_ChiefEmployee instanceof Employee)) {
				$this->_ChiefEmployee = Employee::GetById($this->_ChiefEmployee);
			}
			return $this->_ChiefEmployee;
		}

		/**
		 * Loads and returns an instance of EmployeeCollection containing all Employees that belong to this Unit.
		 * @return EmployeeCollection Collection containing all Employees for this Unit.
		 */

		protected function getEmployees() {
			if(!($this->employees instanceof EmployeeCollection)) {
				$this->employees = EmployeeCollection::GetByUnit($this);
			}
			return $this->employees;
		}

		/**
		 * Set the Address for this Unit.
		 * @param Address $address Address instance to be set.
		 */

		protected function setAddress(Address $address) {
			$this->_Address = $address;
		}

		/**
		 * Set the Chief Employee for this Unit.
		 * @param Employee $employee Employee instance to be set.
		 */

		protected function setChiefEmployee(Employee $employee) {
			$this->_ChiefEmployee = $employee;
		}
	}

?>