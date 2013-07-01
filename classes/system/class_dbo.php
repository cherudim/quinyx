<?php

	namespace System {
		use PDO;
		use PDOStatement;
		use Exceptions\DBOException;
		use DateTime;

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

			protected function parseArgs($query, &$args) {
				foreach($args as $id => $arg) {
					if(is_array($arg)) {
						$n = 0;
						$ids = array();
						foreach($arg as $value) {
							if($value instanceof DatabaseObject) {
								$value = $value->Id;
							}
							$args[$id.$n] = $value;
							$ids[] = $id.$n;
							$n++;
						}
						unset($args[$id]);
						$query = str_replace($id, implode(', ', $ids), $query);
					} elseif($arg instanceof Collection) {
						$n = 0;
						$ids = array();
						foreach($arg as $value) {
							$args[$id.$n] = $value->Id;
							$ids[] = $id.$n;
							$n++;
						}
						unset($args[$id]);
						$query = str_replace($id, implode(', ', $ids), $query);
					} elseif($arg instanceof DateTime) {
						$args[$id] = $arg->format('Y-m-d H:i:s');
					} elseif($arg instanceof DatabaseObject) {
						$args[$id] = $arg->Id;
					}
				}
				return $query;
			}

			public function Execute($query, $args = array()) {
				if($query instanceof PDOStatement) {
					$stmt = $query;
				} else {
					$query = $this->parseArgs($query, $args);
					$stmt = $this->connection->prepare($query);
				}
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
				error_log('Query: ' . $stmt->queryString);
				if($stmt->execute($args)) {
					return $stmt;
				}
				throw new DBOException($stmt, $args);
			}
			
			public function LastInsertId() {
				return $this->connection->lastInsertId();
			}
		}
	}

?>