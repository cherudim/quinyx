<?php

	namespace Exceptions {
		use Exception;

		class InvalidValueException extends Exception {
			protected $class;
			protected $property;
			protected $value;

			public function __construct($class, $property, $value, Exception $prev = null) {
				$this->class = $class;
				$this->property = $property;
				$this->value = $value;
			}

			public function getClassName() {
				return (is_object($this->class) ? get_class($this->class) : $this->class);
			}

			public function getProperty() {
				return $this->property;
			}

			public function getValue() {
				return $this->value;
			}

			public function __toString() {
				return '"' . get_called_class() . '" with message "Invalid value provided to ' . $this->getClassName() . '::' . $this->getProperty() . '"';
			}
		}
	}

?>