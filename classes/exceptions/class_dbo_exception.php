<?php

	namespace Exceptions {
		use Exception;
		use PDOStatement;

		class DBOException extends Exception {
			protected $query;
			protected $args = array();

			public function __construct(PDOStatement $stmt, $args = array(), Exception $prev = null) {
				$error = $stmt->errorInfo();
				parent::__construct($error[2], $error[1], $prev);
				$this->query = $stmt->queryString;
				$this->args = $args;
			}

			public function getQuery() {
				return $this->query;
			}

			public function getArgs() {
				return $this->args;
			}

			public function hasArgs() {
				return (count($this->args) > 0);
			}

			public function __toString() {
				return '"' . get_called_class() . '" with message "' . $this->getMessage() . ' in "' . $this->getQuery() . '"' . ($this->hasArgs() ? ' and args: ' . print_r($this->getArgs(), true) : '') . '"';
			}
		}
	}

?>