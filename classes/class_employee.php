<?php

	use System\DatabaseObject;
	use Exceptions\InvalidValueException;

	class Employee extends DatabaseObject {
		protected $_Name;
		protected $_Address = self::IdField;
		protected $_StartAt = self::TimestampField;
		protected $_EndAt = self::TimestampField;
		protected $_Email;
		protected $_Phone;
		protected $_BornAt = self::TimestampField;
		protected $_Unit = self::IdField;

		protected function getAddress() {
			if(!($this->_Address instanceof Address)) {
				$this->_Address = Address::GetById($this->_Address);
			}
			return $this->_Address;
		}

		protected function getUnit() {
			if(!($this->_Unit instanceof Unit)) {
				try {
					$this->_Unit = Unit::GetById($this->_Unit);
				} catch(Exception $e) {
					$this->_Unit = null;
				}
			}
			return $this->_Unit;
		}

		protected function setAddress(Address $address) {
			$this->_Address = $address;
		}

		protected function setStartAt($value) {
			if($value instanceof DateTime || $value == self::CurrentTime) {
				$this->_StartAt = $value;
			} else {
				throw new InvalidValueException(get_called_class(), 'StartAt', $value);
			}
		}

		protected function setEndAt($value) {
			if($value instanceof DateTime || $value == self::CurrentTime || is_null($value)) {
				$this->_EndAt = $value;
			} else {
				throw new InvalidValueException(get_called_class(), 'EndAt', $value);
			}
		}

		protected function setEmail($value) {
			if(preg_match('#^([a-zA-Z0-9_\-\.])+\@([a-zA-Z0-9_\-\.])+\.([A-Za-z]{2,4})$#', $value)) {
				$this->_Email = $value;
			} else {
				throw new InvalidValueException(get_called_class(), 'Email', $value);
			}
		}

		protected function setUnit($value) {
			if($value instanceof Unit || is_null($value)) {
				$this->_Unit = $value;
			} else {
				throw new InvalidValueException(get_called_class(), 'Unit', $value);
			}
		}
	}

?>