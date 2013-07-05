<?php

	namespace System {
		use PDO;
		use PDOStatement;
		use Exceptions\DBOException;
		use DateTime;

		class DBO {
			protected $connection;

			protected static $instance;

			/**
			 * Singleton operator, to avoid unnescessairy extra instances of this class.
			 * @return static An instance of this class.
			 */

			public static function Instance() {
				if(!(static::$instance instanceof static)) {
					static::$instance = new static();
				}
				return static::$instance;
			}

			public function __construct() {
				$this->connection = new PDO('mysql:host=' . Config::Instance()->db->host . ';dbname=' . Config::Instance()->db->database, Config::Instance()->db->user, Config::Instance()->db->password);
			}

			/**
			 * Parses the provided values, and modifies the query if needed. Provides the posiblity to do "IN (:ids)" with :ids as an array in PDO, something that is not natively possible. Also converts DatabaseObjects to Id's, and DateTime-objects to database-compatible formats.
			 * @param string $query The query to be run.
			 * @param array $args An array containing the arguments for the query.
			 * @return string The possibly modified query.
			 */

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

			/**
			 * Runs a query against the database
			 * @param string $query The query to be run.
			 * @param array $args An array containing the arguments for the query.
			 * @return PDOStatement A PDOStatement containing the results of the query.
			 * @throws DBOException Throws this if the query failed in any way. Contains details about the error.
			 */

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
			
			/**
			 * Fetches the inserted Id of the last insert query run.
			 * @return int The Id.
			 */

			public function LastInsertId() {
				return $this->connection->lastInsertId();
			}
		}
	}

?>