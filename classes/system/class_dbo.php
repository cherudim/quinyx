<?php

	namespace System {
		use PDO;
		use PDOStatement;
		use Exceptions\DBOException;

		class DBO {
			protected $connection;

			protected static $instance;

			public static function Instance() {
				if(!(static::$instance instanceof static)) {
					static::$instance = new static();
				}
				return static::$instance;
			}

			public function __construct() {
				$this->connection = new PDO('mysql:host=' . Config::Instance()->db->host . ';dbname=' . Config::Instance()->db->database, Config::Instance()->db->user, Config::Instance()->db->password);
			}

			public function Execute($query, $args = array()) {
				if($query instanceof PDOStatement) {
					$stmt = $query;
				} else {
					$stmt = $this->connection->prepare($query);
				}
				error_log('Query: ' . $stmt->queryString);
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
				if($stmt->execute($args)) {
					return $stmt;
				}
				throw new DBOException($stmt, $args);
			}
		}
	}

?>