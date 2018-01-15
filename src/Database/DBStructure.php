<?php
/**
 * @file src/Database/DBStructure.php
 */
namespace Friendica\Database;

use Friendica\Core\Config;
use Friendica\Database\DBM;
use dba;

require_once "boot.php";
require_once 'include/dba.php';
require_once 'include/enotify.php';
require_once "include/text.php";

/**
 * @brief This class contain functions for the database management
 *
 * This class contains functions that doesn't need to know if pdo, mysqli or whatever is used.
 */
class DBStructure {
	/*
	 * Converts all tables from MyISAM to InnoDB
	 */
	public static function convertToInnoDB() {
		$r = q("SELECT `TABLE_NAME` FROM `information_schema`.`tables` WHERE `engine` = 'MyISAM' AND `table_schema` = '%s'",
			dbesc(dba::database_name()));

		if (!DBM::is_result($r)) {
			echo t('There are no tables on MyISAM.')."\n";
			return;
		}

		foreach ($r AS $table) {
			$sql = sprintf("ALTER TABLE `%s` engine=InnoDB;", dbesc($table['TABLE_NAME']));
			echo $sql."\n";

			$result = dba::e($sql);
			if (!DBM::is_result($result)) {
				self::printUpdateError($sql);
			}
		}
	}

	/*
	 * send the email and do what is needed to do on update fails
	 *
	 * @param update_id		(int) number of failed update
	 * @param error_message	(str) error message
	 */
	public static function updateFail($update_id, $error_message) {
		$a = get_app();

		//send the administrators an e-mail
		$admin_mail_list = "'".implode("','", array_map(dbesc, explode(",", str_replace(" ", "", $a->config['admin_email']))))."'";
		$adminlist = q("SELECT uid, language, email FROM user WHERE email IN (%s)",
			$admin_mail_list
		);

		// No valid result?
		if (!DBM::is_result($adminlist)) {
			logger(sprintf('Cannot notify administrators about update_id=%d, error_message=%s', $update_id, $error_message), LOGGER_NORMAL);

			// Don't continue
			return;
		}

		// every admin could had different language
		foreach ($adminlist as $admin) {
			$lang = (($admin['language'])?$admin['language']:'en');
			push_lang($lang);

			$preamble = deindent(t("
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can't do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid."));
			$body = t("The error message is\n[pre]%s[/pre]");
			$preamble = sprintf($preamble, $update_id);
			$body = sprintf($body, $error_message);

			notification(array(
				'type' => SYSTEM_EMAIL,
				'to_email' => $admin['email'],
				'preamble' => $preamble,
				'body' => $body,
				'language' => $lang)
			);
		}

		//try the logger
		logger("CRITICAL: Database structure update failed: ".$error_message);
	}


	private static function tableStructure($table) {
		$structures = q("DESCRIBE `%s`", $table);

		$full_columns = q("SHOW FULL COLUMNS FROM `%s`", $table);

		$indexes = q("SHOW INDEX FROM `%s`", $table);

		$table_status = q("SHOW TABLE STATUS WHERE `name` = '%s'", $table);

		if (DBM::is_result($table_status)) {
			$table_status = $table_status[0];
		} else {
			$table_status = array();
		}

		$fielddata = array();
		$indexdata = array();

		if (DBM::is_result($indexes)) {
			foreach ($indexes AS $index) {
				if ($index['Key_name'] != 'PRIMARY' && $index['Non_unique'] == '0' && !isset($indexdata[$index["Key_name"]])) {
					$indexdata[$index["Key_name"]] = array('UNIQUE');
				}

				$column = $index["Column_name"];

				if ($index["Sub_part"] != "") {
					$column .= "(".$index["Sub_part"].")";
				}

				$indexdata[$index["Key_name"]][] = $column;
			}
		}
		if (DBM::is_result($structures)) {
			foreach ($structures AS $field) {
				// Replace the default size values so that we don't have to define them
				$search = ['tinyint(1)', 'tinyint(4)', 'smallint(5) unsigned', 'smallint(6)', 'mediumint(9)', 'bigint(20)', 'int(11)'];
				$replace = ['boolean', 'tinyint', 'smallint unsigned', 'smallint', 'mediumint', 'bigint', 'int'];
				$field["Type"] = str_replace($search, $replace, $field["Type"]);

				$fielddata[$field["Field"]]["type"] = $field["Type"];
				if ($field["Null"] == "NO") {
					$fielddata[$field["Field"]]["not null"] = true;
				}

				if (isset($field["Default"])) {
					$fielddata[$field["Field"]]["default"] = $field["Default"];
				}

				if ($field["Extra"] != "") {
					$fielddata[$field["Field"]]["extra"] = $field["Extra"];
				}

				if ($field["Key"] == "PRI") {
					$fielddata[$field["Field"]]["primary"] = true;
				}
			}
		}
		if (DBM::is_result($full_columns)) {
			foreach ($full_columns AS $column) {
				$fielddata[$column["Field"]]["Collation"] = $column["Collation"];
				$fielddata[$column["Field"]]["comment"] = $column["Comment"];
			}
		}

		return array("fields" => $fielddata, "indexes" => $indexdata, "table_status" => $table_status);
	}

	public static function printStructure() {
		$database = self::definition();

		echo "-- ------------------------------------------\n";
		echo "-- ".FRIENDICA_PLATFORM." ".FRIENDICA_VERSION." (".FRIENDICA_CODENAME,")\n";
		echo "-- DB_UPDATE_VERSION ".DB_UPDATE_VERSION."\n";
		echo "-- ------------------------------------------\n\n\n";
		foreach ($database AS $name => $structure) {
			echo "--\n";
			echo "-- TABLE $name\n";
			echo "--\n";
			self::createTable($name, $structure['fields'], true, false, $structure["indexes"]);

			echo "\n";
		}
	}

	/**
	 * @brief Print out database error messages
	 *
	 * @param string $message Message to be added to the error message
	 *
	 * @return string Error message
	 */
	private static function printUpdateError($message) {
		echo sprintf(t("\nError %d occurred during database update:\n%s\n"),
			dba::errorNo(), dba::errorMessage());

		return t('Errors encountered performing database changes: ').$message.EOL;
	}

	/**
	 * Updates DB structure and returns eventual errors messages
	 *
	 * @param bool  $verbose
	 * @param bool  $action     Whether to actually apply the update
	 * @param array $tables     An array of the database tables
	 * @param array $definition An array of the definition tables
	 * @return string Empty string if the update is successful, error messages otherwise
	 */
	public static function update($verbose, $action, array $tables = null, array $definition = null) {
		if ($action) {
			Config::set('system', 'maintenance', 1);
			Config::set('system', 'maintenance_reason', sprintf(t(': Database update'), DBM::date().' '.date('e')));
		}

		$errors = '';

		logger('updating structure', LOGGER_DEBUG);

		// Get the current structure
		$database = array();

		if (is_null($tables)) {
			$tables = q("SHOW TABLES");
		}

		if (DBM::is_result($tables)) {
			foreach ($tables AS $table) {
				$table = current($table);

				logger(sprintf('updating structure for table %s ...', $table), LOGGER_DEBUG);
				$database[$table] = self::tableStructure($table);
			}
		}

		// Get the definition
		if (is_null($definition)) {
			$definition = self::definition();
		}

		// MySQL >= 5.7.4 doesn't support the IGNORE keyword in ALTER TABLE statements
		if ((version_compare(dba::server_info(), '5.7.4') >= 0) &&
			!(strpos(dba::server_info(), 'MariaDB') !== false)) {
			$ignore = '';
		} else {
			$ignore = ' IGNORE';
		}

		// Compare it
		foreach ($definition AS $name => $structure) {
			$is_new_table = False;
			$group_by = "";
			$sql3 = "";
			if (!isset($database[$name])) {
				$r = self::createTable($name, $structure["fields"], $verbose, $action, $structure['indexes']);
				if (!DBM::is_result($r)) {
					$errors .= self::printUpdateError($name);
				}
				$is_new_table = True;
			} else {
				$is_unique = false;
				$temp_name = $name;

				foreach ($structure["indexes"] AS $indexname => $fieldnames) {
					if (isset($database[$name]["indexes"][$indexname])) {
						$current_index_definition = implode(",",$database[$name]["indexes"][$indexname]);
					} else {
						$current_index_definition = "__NOT_SET__";
					}
					$new_index_definition = implode(",",$fieldnames);
					if ($current_index_definition != $new_index_definition) {
						if ($fieldnames[0] == "UNIQUE") {
							$is_unique = true;
							if ($ignore == "") {
								$temp_name = "temp-".$name;
							}
						}
					}
				}

				/*
				 * Drop the index if it isn't present in the definition
				 * or the definition differ from current status
				 * and index name doesn't start with "local_"
				 */
				foreach ($database[$name]["indexes"] as $indexname => $fieldnames) {
					$current_index_definition = implode(",",$fieldnames);
					if (isset($structure["indexes"][$indexname])) {
						$new_index_definition = implode(",",$structure["indexes"][$indexname]);
					} else {
						$new_index_definition = "__NOT_SET__";
					}
					if ($current_index_definition != $new_index_definition && substr($indexname, 0, 6) != 'local_') {
						$sql2=self::dropIndex($indexname);
						if ($sql3 == "") {
							$sql3 = "ALTER".$ignore." TABLE `".$temp_name."` ".$sql2;
						} else {
							$sql3 .= ", ".$sql2;
						}
					}
				}
				// Compare the field structure field by field
				foreach ($structure["fields"] AS $fieldname => $parameters) {
					if (!isset($database[$name]["fields"][$fieldname])) {
						$sql2=self::addTableField($fieldname, $parameters);
						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
						} else {
							$sql3 .= ", ".$sql2;
						}
					} else {
						// Compare the field definition
						$field_definition = $database[$name]["fields"][$fieldname];

						// Remove the relation data that is used for the referential integrity
						unset($parameters['relation']);

						// We change the collation after the indexes had been changed.
						// This is done to avoid index length problems.
						// So here we always ensure that there is no need to change it.
						unset($parameters['Collation']);
						unset($field_definition['Collation']);

						// Only update the comment when it is defined
						if (!isset($parameters['comment'])) {
							$parameters['comment'] = "";
						}

						$current_field_definition = implode(",", $field_definition);
						$new_field_definition = implode(",", $parameters);
						if ($current_field_definition != $new_field_definition) {
							$sql2 = self::modifyTableField($fieldname, $parameters);
							if ($sql3 == "") {
								$sql3 = "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
							} else {
								$sql3 .= ", ".$sql2;
							}
						}
					}
				}
			}

			/*
			 * Create the index if the index don't exists in database
			 * or the definition differ from the current status.
			 * Don't create keys if table is new
			 */
			if (!$is_new_table) {
				foreach ($structure["indexes"] AS $indexname => $fieldnames) {
					if (isset($database[$name]["indexes"][$indexname])) {
						$current_index_definition = implode(",",$database[$name]["indexes"][$indexname]);
					} else {
						$current_index_definition = "__NOT_SET__";
					}
					$new_index_definition = implode(",",$fieldnames);
					if ($current_index_definition != $new_index_definition) {
						$sql2 = self::createIndex($indexname, $fieldnames);

						// Fetch the "group by" fields for unique indexes
						if ($fieldnames[0] == "UNIQUE") {
							$group_by = self::groupBy($indexname, $fieldnames);
						}
						if ($sql2 != "") {
							if ($sql3 == "") {
								$sql3 = "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
							} else {
								$sql3 .= ", ".$sql2;
							}
						}
					}
				}

				if (isset($database[$name]["table_status"]["Comment"])) {
					if ($database[$name]["table_status"]["Comment"] != $structure['comment']) {
						$sql2 = "COMMENT = '".dbesc($structure['comment'])."'";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
						} else {
							$sql3 .= ", ".$sql2;
						}
					}
				}

				if (isset($database[$name]["table_status"]["Collation"])) {
					if ($database[$name]["table_status"]["Collation"] != 'utf8mb4_general_ci') {
						$sql2 = "DEFAULT COLLATE utf8mb4_general_ci";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
						} else {
							$sql3 .= ", ".$sql2;
						}
					}
				}

				if ($sql3 != "") {
					$sql3 .= "; ";
				}

				// Now have a look at the field collations
				// Compare the field structure field by field
				foreach ($structure["fields"] AS $fieldname => $parameters) {
					// Compare the field definition
					$field_definition = $database[$name]["fields"][$fieldname];

					// Define the default collation if not given
					if (!isset($parameters['Collation']) && !is_null($field_definition['Collation'])) {
						$parameters['Collation'] = 'utf8mb4_general_ci';
					} else {
						$parameters['Collation'] = null;
					}

					if ($field_definition['Collation'] != $parameters['Collation']) {
						$sql2 = self::modifyTableField($fieldname, $parameters);
						if (($sql3 == "") || (substr($sql3, -2, 2) == "; ")) {
							$sql3 .= "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
						} else {
							$sql3 .= ", ".$sql2;
						}
					}
				}
			}

			if ($sql3 != "") {
				if (substr($sql3, -2, 2) != "; ") {
					$sql3 .= ";";
				}

				$field_list = '';
				if ($is_unique && $ignore == '') {
					foreach ($database[$name]["fields"] AS $fieldname => $parameters) {
						$field_list .= 'ANY_VALUE(`' . $fieldname . '`),';
					}
					$field_list = rtrim($field_list, ',');
				}

				if ($verbose) {
					// Ensure index conversion to unique removes duplicates
					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							echo "SET session old_alter_table=1;\n";
						} else {
							echo "DROP TABLE IF EXISTS `".$temp_name."`;\n";
							echo "CREATE TABLE `".$temp_name."` LIKE `".$name."`;\n";
						}
					}

					echo $sql3."\n";

					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							echo "SET session old_alter_table=0;\n";
						} else {
							echo "INSERT INTO `".$temp_name."` SELECT ".dba::any_value_fallback($field_list)." FROM `".$name."`".$group_by.";\n";
							echo "DROP TABLE `".$name."`;\n";
							echo "RENAME TABLE `".$temp_name."` TO `".$name."`;\n";
						}
					}
				}

				if ($action) {
					Config::set('system', 'maintenance_reason', sprintf(t('%s: updating %s table.'), DBM::date().' '.date('e'), $name));

					// Ensure index conversion to unique removes duplicates
					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							dba::e("SET session old_alter_table=1;");
						} else {
							dba::e("DROP TABLE IF EXISTS `".$temp_name."`;");
							if (!DBM::is_result($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}

							$r = dba::e("CREATE TABLE `".$temp_name."` LIKE `".$name."`;");
							if (!DBM::is_result($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
						}
					}

					$r = dba::e($sql3);
					if (!DBM::is_result($r)) {
						$errors .= self::printUpdateError($sql3);
					}
					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							dba::e("SET session old_alter_table=0;");
						} else {
							$r = dba::e("INSERT INTO `".$temp_name."` SELECT ".$field_list." FROM `".$name."`".$group_by.";");
							if (!DBM::is_result($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
							$r = dba::e("DROP TABLE `".$name."`;");
							if (!DBM::is_result($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
							$r = dba::e("RENAME TABLE `".$temp_name."` TO `".$name."`;");
							if (!DBM::is_result($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
						}
					}
				}
			}
		}

		if ($action) {
			Config::set('system', 'maintenance', 0);
			Config::set('system', 'maintenance_reason', '');
		}

		if ($errors) {
			Config::set('system', 'dbupdate', DB_UPDATE_FAILED);
		} else {
			Config::set('system', 'dbupdate', DB_UPDATE_SUCCESSFUL);
		}

		return $errors;
	}

	private static function FieldCommand($parameters, $create = true) {
		$fieldstruct = $parameters["type"];

		if (!is_null($parameters["Collation"])) {
			$fieldstruct .= " COLLATE ".$parameters["Collation"];
		}

		if ($parameters["not null"]) {
			$fieldstruct .= " NOT NULL";
		}

		if (isset($parameters["default"])) {
			if (strpos(strtolower($parameters["type"]),"int")!==false) {
				$fieldstruct .= " DEFAULT ".$parameters["default"];
			} else {
				$fieldstruct .= " DEFAULT '".$parameters["default"]."'";
			}
		}
		if ($parameters["extra"] != "") {
			$fieldstruct .= " ".$parameters["extra"];
		}

		if (!is_null($parameters["comment"])) {
			$fieldstruct .= " COMMENT '".dbesc($parameters["comment"])."'";
		}

		/*if (($parameters["primary"] != "") && $create)
			$fieldstruct .= " PRIMARY KEY";*/

		return($fieldstruct);
	}

	private static function createTable($name, $fields, $verbose, $action, $indexes=null) {
		$r = true;

		$sql_rows = array();
		$primary_keys = array();
		foreach ($fields AS $fieldname => $field) {
			$sql_rows[] = "`".dbesc($fieldname)."` ".self::FieldCommand($field);
			if (x($field,'primary') && $field['primary']!='') {
				$primary_keys[] = $fieldname;
			}
		}

		if (!is_null($indexes)) {
			foreach ($indexes AS $indexname => $fieldnames) {
				$sql_index = self::createIndex($indexname, $fieldnames, "");
				if (!is_null($sql_index)) {
					$sql_rows[] = $sql_index;
				}
			}
		}

		$sql = implode(",\n\t", $sql_rows);

		$sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (\n\t", dbesc($name)).$sql."\n) DEFAULT COLLATE utf8mb4_general_ci";
		if ($verbose) {
			echo $sql.";\n";
		}

		if ($action) {
			$r = dba::e($sql);
		}

		return $r;
	}

	private static function addTableField($fieldname, $parameters) {
		$sql = sprintf("ADD `%s` %s", dbesc($fieldname), self::FieldCommand($parameters));
		return($sql);
	}

	private static function modifyTableField($fieldname, $parameters) {
		$sql = sprintf("MODIFY `%s` %s", dbesc($fieldname), self::FieldCommand($parameters, false));
		return($sql);
	}

	private static function dropIndex($indexname) {
		$sql = sprintf("DROP INDEX `%s`", dbesc($indexname));
		return($sql);
	}

	private static function createIndex($indexname, $fieldnames, $method = "ADD") {
		$method = strtoupper(trim($method));
		if ($method!="" && $method!="ADD") {
			throw new \Exception("Invalid parameter 'method' in self::createIndex(): '$method'");
		}

		if ($fieldnames[0] == "UNIQUE") {
			array_shift($fieldnames);
			$method .= ' UNIQUE';
		}

		$names = "";
		foreach ($fieldnames AS $fieldname) {
			if ($names != "") {
				$names .= ",";
			}

			if (preg_match('|(.+)\((\d+)\)|', $fieldname, $matches)) {
				$names .= "`".dbesc($matches[1])."`(".intval($matches[2]).")";
			} else {
				$names .= "`".dbesc($fieldname)."`";
			}
		}

		if ($indexname == "PRIMARY") {
			return sprintf("%s PRIMARY KEY(%s)", $method, $names);
		}


		$sql = sprintf("%s INDEX `%s` (%s)", $method, dbesc($indexname), $names);
		return($sql);
	}

	private static function groupBy($indexname, $fieldnames) {
		if ($fieldnames[0] != "UNIQUE") {
			return "";
		}

		array_shift($fieldnames);

		$names = "";
		foreach ($fieldnames AS $fieldname) {
			if ($names != "") {
				$names .= ",";
			}

			if (preg_match('|(.+)\((\d+)\)|', $fieldname, $matches)) {
				$names .= "`".dbesc($matches[1])."`";
			} else {
				$names .= "`".dbesc($fieldname)."`";
			}
		}

		$sql = sprintf(" GROUP BY %s", $names);
		return $sql;
	}

	public static function definition() {
		$database = array();

		$database["addon"] = array(
				"comment" => "registered plugins",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"name" => array("type" => "varchar(190)", "not null" => "1", "default" => "", "comment" => ""),
						"version" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"installed" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"hidden" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"timestamp" => array("type" => "bigint", "not null" => "1", "default" => "0", "comment" => ""),
						"plugin_admin" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"name" => array("UNIQUE", "name"),
						)
				);
		$database["attach"] = array(
				"comment" => "file attachments",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"hash" => array("type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""),
						"filename" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"filetype" => array("type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""),
						"filesize" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"data" => array("type" => "longblob", "not null" => "1", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"edited" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"allow_cid" => array("type" => "mediumtext", "comment" => ""),
						"allow_gid" => array("type" => "mediumtext", "comment" => ""),
						"deny_cid" => array("type" => "mediumtext", "comment" => ""),
						"deny_gid" => array("type" => "mediumtext", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["auth_codes"] = array(
				"comment" => "OAuth usage",
				"fields" => array(
						"id" => array("type" => "varchar(40)", "not null" => "1", "primary" => "1", "comment" => ""),
						"client_id" => array("type" => "varchar(20)", "not null" => "1", "default" => "", "relation" => array("clients" => "client_id"), "comment" => ""),
						"redirect_uri" => array("type" => "varchar(200)", "not null" => "1", "default" => "", "comment" => ""),
						"expires" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"scope" => array("type" => "varchar(250)", "not null" => "1", "default" => "", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["cache"] = array(
				"comment" => "Used to store different data that doesn't to be stored for a long time",
				"fields" => array(
						"k" => array("type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => ""),
						"v" => array("type" => "mediumtext", "comment" => ""),
						"expire_mode" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"updated" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("k"),
						"expire_mode_updated" => array("expire_mode", "updated"),
						)
				);
		$database["challenge"] = array(
				"comment" => "",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"challenge" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"dfrn-id" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"expire" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"type" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"last_update" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["clients"] = array(
				"comment" => "OAuth usage",
				"fields" => array(
						"client_id" => array("type" => "varchar(20)", "not null" => "1", "primary" => "1", "comment" => ""),
						"pw" => array("type" => "varchar(20)", "not null" => "1", "default" => "", "comment" => ""),
						"redirect_uri" => array("type" => "varchar(200)", "not null" => "1", "default" => "", "comment" => ""),
						"name" => array("type" => "text", "comment" => ""),
						"icon" => array("type" => "text", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						),
				"indexes" => array(
						"PRIMARY" => array("client_id"),
						)
				);
		$database["config"] = array(
				"comment" => "main configuration storage",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"cat" => array("type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""),
						"k" => array("type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""),
						"v" => array("type" => "mediumtext", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"cat_k" => array("UNIQUE", "cat", "k"),
						)
				);
		$database["contact"] = array(
				"comment" => "contact table",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"self" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"remote_self" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"rel" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"duplex" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"network" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"nick" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"location" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"about" => array("type" => "text", "comment" => ""),
						"keywords" => array("type" => "text", "comment" => ""),
						"gender" => array("type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""),
						"xmpp" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"attag" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"avatar" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"photo" => array("type" => "text", "comment" => ""),
						"thumb" => array("type" => "text", "comment" => ""),
						"micro" => array("type" => "text", "comment" => ""),
						"site-pubkey" => array("type" => "text", "comment" => ""),
						"issued-id" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"dfrn-id" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"url" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"nurl" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"addr" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"alias" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"pubkey" => array("type" => "text", "comment" => ""),
						"prvkey" => array("type" => "text", "comment" => ""),
						"batch" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"request" => array("type" => "text", "comment" => ""),
						"notify" => array("type" => "text", "comment" => ""),
						"poll" => array("type" => "text", "comment" => ""),
						"confirm" => array("type" => "text", "comment" => ""),
						"poco" => array("type" => "text", "comment" => ""),
						"aes_allow" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"ret-aes" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"usehub" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"subhub" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"hub-verify" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"last-update" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"success_update" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"failure_update" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"name-date" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"uri-date" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"avatar-date" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"term-date" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"last-item" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"priority" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"blocked" => array("type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""),
						"readonly" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"writable" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"forum" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"prv" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"contact-type" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"hidden" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"archive" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"pending" => array("type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""),
						"rating" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"reason" => array("type" => "text", "comment" => ""),
						"closeness" => array("type" => "tinyint", "not null" => "1", "default" => "99", "comment" => ""),
						"info" => array("type" => "mediumtext", "comment" => ""),
						"profile-id" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"bdyear" => array("type" => "varchar(4)", "not null" => "1", "default" => "", "comment" => ""),
						"bd" => array("type" => "date", "not null" => "1", "default" => "0001-01-01", "comment" => ""),
						"notify_new_posts" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"fetch_further_information" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"ffi_keyword_blacklist" => array("type" => "text", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid_name" => array("uid", "name(190)"),
						"self_uid" => array("self", "uid"),
						"alias_uid" => array("alias(32)", "uid"),
						"pending_uid" => array("pending", "uid"),
						"blocked_uid" => array("blocked", "uid"),
						"uid_rel_network_poll" => array("uid", "rel", "network(4)", "poll(64)", "archive"),
						"uid_network_batch" => array("uid", "network(4)", "batch(64)"),
						"addr_uid" => array("addr(32)", "uid"),
						"nurl_uid" => array("nurl(32)", "uid"),
						"nick_uid" => array("nick(32)", "uid"),
						"dfrn-id" => array("dfrn-id(64)"),
						"issued-id" => array("issued-id(64)"),
						)
				);
		$database["conv"] = array(
				"comment" => "private messages",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"guid" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"recips" => array("type" => "text", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"creator" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"updated" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"subject" => array("type" => "text", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid" => array("uid"),
						)
				);
		$database["conversation"] = array(
				"comment" => "Raw data and structure information for messages",
				"fields" => array(
						"item-uri" => array("type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => ""),
						"reply-to-uri" => array("type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""),
						"conversation-uri" => array("type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""),
						"conversation-href" => array("type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""),
						"protocol" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"source" => array("type" => "mediumtext", "comment" => ""),
						"received" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("item-uri"),
						"conversation-uri" => array("conversation-uri"),
						"received" => array("received"),
						)
				);
		$database["event"] = array(
				"comment" => "Events",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"guid" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"cid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"uri" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"edited" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"start" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"finish" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"summary" => array("type" => "text", "comment" => ""),
						"desc" => array("type" => "text", "comment" => ""),
						"location" => array("type" => "text", "comment" => ""),
						"type" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"nofinish" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"adjust" => array("type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""),
						"ignore" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"allow_cid" => array("type" => "mediumtext", "comment" => ""),
						"allow_gid" => array("type" => "mediumtext", "comment" => ""),
						"deny_cid" => array("type" => "mediumtext", "comment" => ""),
						"deny_gid" => array("type" => "mediumtext", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid_start" => array("uid", "start"),
						)
				);
		$database["fcontact"] = array(
				"comment" => "Diaspora compatible contacts - used in the Diaspora implementation",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"guid" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"url" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"photo" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"request" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"nick" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"addr" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"batch" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"notify" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"poll" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"confirm" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"priority" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"network" => array("type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""),
						"alias" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"pubkey" => array("type" => "text", "comment" => ""),
						"updated" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"addr" => array("addr(32)"),
						"url" => array("UNIQUE", "url(190)"),
						)
				);
		$database["fsuggest"] = array(
				"comment" => "friend suggestion stuff",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"cid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"url" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"request" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"photo" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"note" => array("type" => "text", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["gcign"] = array(
				"comment" => "contacts ignored by friend suggestions",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"gcid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("gcontact" => "id"), "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid" => array("uid"),
						"gcid" => array("gcid"),
						)
				);
		$database["gcontact"] = array(
				"comment" => "global contacts",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"nick" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"url" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"nurl" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"photo" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"connect" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"updated" => array("type" => "datetime", "default" => NULL_DATE, "comment" => ""),
						"last_contact" => array("type" => "datetime", "default" => NULL_DATE, "comment" => ""),
						"last_failure" => array("type" => "datetime", "default" => NULL_DATE, "comment" => ""),
						"location" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"about" => array("type" => "text", "comment" => ""),
						"keywords" => array("type" => "text", "comment" => ""),
						"gender" => array("type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""),
						"birthday" => array("type" => "varchar(32)", "not null" => "1", "default" => "0001-01-01", "comment" => ""),
						"community" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"contact-type" => array("type" => "tinyint", "not null" => "1", "default" => "-1", "comment" => ""),
						"hide" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"nsfw" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"network" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"addr" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"notify" => array("type" => "text", "comment" => ""),
						"alias" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"generation" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"server_url" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"nurl" => array("UNIQUE", "nurl(190)"),
						"name" => array("name(64)"),
						"nick" => array("nick(32)"),
						"addr" => array("addr(64)"),
						"hide_network_updated" => array("hide", "network(4)", "updated"),
						"updated" => array("updated"),
						)
				);
		$database["glink"] = array(
				"comment" => "'friends of friends' linkages derived from poco",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"cid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"gcid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("gcontact" => "id"), "comment" => ""),
						"zcid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("gcontact" => "id"), "comment" => ""),
						"updated" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"cid_uid_gcid_zcid" => array("UNIQUE", "cid","uid","gcid","zcid"),
						"gcid" => array("gcid"),
						)
				);
		$database["group"] = array(
				"comment" => "privacy groups, group info",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"visible" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"deleted" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid" => array("uid"),
						)
				);
		$database["group_member"] = array(
				"comment" => "privacy groups, member info",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"gid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("group" => "id"), "comment" => ""),
						"contact-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"contactid" => array("contact-id"),
						"gid_contactid" => array("UNIQUE", "gid", "contact-id"),
						)
				);
		$database["gserver"] = array(
				"comment" => "Global servers",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"url" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"nurl" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"version" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"site_name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"info" => array("type" => "text", "comment" => ""),
						"register_policy" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"registered-users" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"poco" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"noscrape" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"network" => array("type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""),
						"platform" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"last_poco_query" => array("type" => "datetime", "default" => NULL_DATE, "comment" => ""),
						"last_contact" => array("type" => "datetime", "default" => NULL_DATE, "comment" => ""),
						"last_failure" => array("type" => "datetime", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"nurl" => array("UNIQUE", "nurl(190)"),
						)
				);
		$database["hook"] = array(
				"comment" => "plugin hook registry",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"hook" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"file" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"function" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"priority" => array("type" => "smallint", "not null" => "1", "default" => "0", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"hook_file_function" => array("UNIQUE", "hook(50)","file(80)","function(60)"),
						)
				);
		$database["intro"] = array(
				"comment" => "",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"fid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("fcontact" => "id"), "comment" => ""),
						"contact-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"knowyou" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"duplex" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"note" => array("type" => "text", "comment" => ""),
						"hash" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"datetime" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"blocked" => array("type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""),
						"ignore" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["item"] = array(
				"comment" => "All posts",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "relation" => array("thread" => "iid")),
						"guid" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"uri" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"contact-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"gcontact-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("gcontact" => "id"), "comment" => ""),
						"type" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"wall" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"gravity" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"parent" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("item" => "id"), "comment" => ""),
						"parent-uri" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"extid" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"thr-parent" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"edited" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"commented" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"received" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"changed" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"owner-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"owner-name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"owner-link" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"owner-avatar" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"author-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"author-name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"author-link" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"author-avatar" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"title" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"body" => array("type" => "mediumtext", "comment" => ""),
						"app" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"verb" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"object-type" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"object" => array("type" => "text", "comment" => ""),
						"target-type" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"target" => array("type" => "text", "comment" => ""),
						"postopts" => array("type" => "text", "comment" => ""),
						"plink" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"resource-id" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"event-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("event" => "id"), "comment" => ""),
						"tag" => array("type" => "mediumtext", "comment" => ""),
						"attach" => array("type" => "mediumtext", "comment" => ""),
						"inform" => array("type" => "mediumtext", "comment" => ""),
						"file" => array("type" => "mediumtext", "comment" => ""),
						"location" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"coord" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"allow_cid" => array("type" => "mediumtext", "comment" => ""),
						"allow_gid" => array("type" => "mediumtext", "comment" => ""),
						"deny_cid" => array("type" => "mediumtext", "comment" => ""),
						"deny_gid" => array("type" => "mediumtext", "comment" => ""),
						"private" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"pubmail" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"moderated" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"visible" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"spam" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"starred" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"bookmark" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"unseen" => array("type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""),
						"deleted" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"origin" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"forum_mode" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"last-child" => array("type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""),
						"mention" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"network" => array("type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""),
						"rendered-hash" => array("type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""),
						"rendered-html" => array("type" => "mediumtext", "comment" => ""),
						"global" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"guid" => array("guid(191)"),
						"uri" => array("uri(191)"),
						"parent" => array("parent"),
						"parent-uri" => array("parent-uri(191)"),
						"extid" => array("extid(191)"),
						"uid_id" => array("uid","id"),
						"uid_contactid_id" => array("uid","contact-id","id"),
						"uid_created" => array("uid","created"),
						"uid_unseen_contactid" => array("uid","unseen","contact-id"),
						"uid_network_received" => array("uid","network(4)","received"),
						"uid_network_commented" => array("uid","network(4)","commented"),
						"uid_thrparent" => array("uid","thr-parent(190)"),
						"uid_parenturi" => array("uid","parent-uri(190)"),
						"uid_contactid_created" => array("uid","contact-id","created"),
						"authorid_created" => array("author-id","created"),
						"ownerid" => array("owner-id"),
						"uid_uri" => array("uid", "uri(190)"),
						"resource-id" => array("resource-id(191)"),
						"contactid_allowcid_allowpid_denycid_denygid" => array("contact-id","allow_cid(10)","allow_gid(10)","deny_cid(10)","deny_gid(10)"), //
						"uid_type_changed" => array("uid","type(190)","changed"),
						"contactid_verb" => array("contact-id","verb(190)"),
						"deleted_changed" => array("deleted","changed"),
						"uid_wall_changed" => array("uid","wall","changed"),
						"uid_eventid" => array("uid","event-id"),
						"uid_authorlink" => array("uid","author-link(190)"),
						"uid_ownerlink" => array("uid","owner-link(190)"),
						)
				);
		$database["locks"] = array(
				"comment" => "",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"name" => array("type" => "varchar(128)", "not null" => "1", "default" => "", "comment" => ""),
						"locked" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"pid" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["mail"] = array(
				"comment" => "private messages",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"guid" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"from-name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"from-photo" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"from-url" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"contact-id" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "relation" => array("contact" => "id"), "comment" => ""),
						"convid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("conv" => "id"), "comment" => ""),
						"title" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"body" => array("type" => "mediumtext", "comment" => ""),
						"seen" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"reply" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"replied" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"unknown" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"uri" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"parent-uri" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid_seen" => array("uid", "seen"),
						"convid" => array("convid"),
						"uri" => array("uri(64)"),
						"parent-uri" => array("parent-uri(64)"),
						"contactid" => array("contact-id(32)"),
						)
				);
		$database["mailacct"] = array(
				"comment" => "Mail account data for fetching mails",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"server" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"port" => array("type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""),
						"ssltype" => array("type" => "varchar(16)", "not null" => "1", "default" => "", "comment" => ""),
						"mailbox" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"user" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"pass" => array("type" => "text", "comment" => ""),
						"reply_to" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"action" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"movetofolder" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"pubmail" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"last_check" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["manage"] = array(
				"comment" => "table of accounts that can manage each other",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"mid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid_mid" => array("UNIQUE", "uid","mid"),
						)
				);
		$database["notify"] = array(
				"comment" => "notifications",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"hash" => array("type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""),
						"type" => array("type" => "smallint", "not null" => "1", "default" => "0", "comment" => ""),
						"name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"url" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"photo" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"date" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"msg" => array("type" => "mediumtext", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"link" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"iid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("item" => "id"), "comment" => ""),
						"parent" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("item" => "id"), "comment" => ""),
						"seen" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"verb" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"otype" => array("type" => "varchar(16)", "not null" => "1", "default" => "", "comment" => ""),
						"name_cache" => array("type" => "tinytext", "comment" => ""),
						"msg_cache" => array("type" => "mediumtext", "comment" => "")
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"hash_uid" => array("hash", "uid"),
						"seen_uid_date" => array("seen", "uid", "date"),
						"uid_date" => array("uid", "date"),
						"uid_type_link" => array("uid", "type", "link(190)"),
						)
				);
		$database["notify-threads"] = array(
				"comment" => "",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"notify-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("notify" => "id"), "comment" => ""),
						"master-parent-item" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("item" => "id"), "comment" => ""),
						"parent-item" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"receiver-uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["oembed"] = array(
				"comment" => "cache for OEmbed queries",
				"fields" => array(
						"url" => array("type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => ""),
						"maxwidth" => array("type" => "mediumint", "not null" => "1", "primary" => "1", "comment" => ""),
						"content" => array("type" => "mediumtext", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("url", "maxwidth"),
						"created" => array("created"),
						)
				);
		$database["parsed_url"] = array(
				"comment" => "cache for 'parse_url' queries",
				"fields" => array(
						"url" => array("type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => ""),
						"guessing" => array("type" => "boolean", "not null" => "1", "default" => "0", "primary" => "1", "comment" => ""),
						"oembed" => array("type" => "boolean", "not null" => "1", "default" => "0", "primary" => "1", "comment" => ""),
						"content" => array("type" => "mediumtext", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("url", "guessing", "oembed"),
						"created" => array("created"),
						)
				);
		$database["participation"] = array(
				"comment" => "Storage for participation messages from Diaspora",
				"fields" => array(
						"iid" => array("type" => "int", "not null" => "1", "primary" => "1", "relation" => array("item" => "id"), "comment" => ""),
						"server" => array("type" => "varchar(60)", "not null" => "1", "primary" => "1", "comment" => ""),
						"cid" => array("type" => "int", "not null" => "1", "relation" => array("contact" => "id"), "comment" => ""),
						"fid" => array("type" => "int", "not null" => "1", "relation" => array("fcontact" => "id"), "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("iid", "server")
						)
				);
		$database["pconfig"] = array(
				"comment" => "personal (per user) configuration storage",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"cat" => array("type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""),
						"k" => array("type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""),
						"v" => array("type" => "mediumtext", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid_cat_k" => array("UNIQUE", "uid", "cat", "k"),
						)
				);
		$database["photo"] = array(
				"comment" => "photo storage",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"contact-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"guid" => array("type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""),
						"resource-id" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"edited" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"title" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"desc" => array("type" => "text", "comment" => ""),
						"album" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"filename" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"type" => array("type" => "varchar(128)", "not null" => "1", "default" => "image/jpeg"),
						"height" => array("type" => "smallint", "not null" => "1", "default" => "0", "comment" => ""),
						"width" => array("type" => "smallint", "not null" => "1", "default" => "0", "comment" => ""),
						"datasize" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"data" => array("type" => "mediumblob", "not null" => "1", "comment" => ""),
						"scale" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"profile" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"allow_cid" => array("type" => "mediumtext", "comment" => ""),
						"allow_gid" => array("type" => "mediumtext", "comment" => ""),
						"deny_cid" => array("type" => "mediumtext", "comment" => ""),
						"deny_gid" => array("type" => "mediumtext", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"contactid" => array("contact-id"),
						"uid_contactid" => array("uid", "contact-id"),
						"uid_profile" => array("uid", "profile"),
						"uid_album_scale_created" => array("uid", "album(32)", "scale", "created"),
						"uid_album_resource-id_created" => array("uid", "album(32)", "resource-id(64)", "created"),
						"resource-id" => array("resource-id(64)"),
						)
				);
		$database["poll"] = array(
				"comment" => "Currently unused table for storing poll results",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"q0" => array("type" => "text", "comment" => ""),
						"q1" => array("type" => "text", "comment" => ""),
						"q2" => array("type" => "text", "comment" => ""),
						"q3" => array("type" => "text", "comment" => ""),
						"q4" => array("type" => "text", "comment" => ""),
						"q5" => array("type" => "text", "comment" => ""),
						"q6" => array("type" => "text", "comment" => ""),
						"q7" => array("type" => "text", "comment" => ""),
						"q8" => array("type" => "text", "comment" => ""),
						"q9" => array("type" => "text", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid" => array("uid"),
						)
				);
		$database["poll_result"] = array(
				"comment" => "data for polls - currently unused",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"poll_id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("poll" => "id")),
						"choice" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"poll_id" => array("poll_id"),
						)
				);
		$database["process"] = array(
				"comment" => "Currently running system processes",
				"fields" => array(
						"pid" => array("type" => "int", "not null" => "1", "primary" => "1", "comment" => ""),
						"command" => array("type" => "varbinary(32)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("pid"),
						"command" => array("command"),
						)
				);
		$database["profile"] = array(
				"comment" => "user profiles data",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"profile-name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"is-default" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"hide-friends" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"pdesc" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"dob" => array("type" => "varchar(32)", "not null" => "1", "default" => "0001-01-01", "comment" => ""),
						"address" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"locality" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"region" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"postal-code" => array("type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""),
						"country-name" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"hometown" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"gender" => array("type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""),
						"marital" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"with" => array("type" => "text", "comment" => ""),
						"howlong" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"sexual" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"politic" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"religion" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"pub_keywords" => array("type" => "text", "comment" => ""),
						"prv_keywords" => array("type" => "text", "comment" => ""),
						"likes" => array("type" => "text", "comment" => ""),
						"dislikes" => array("type" => "text", "comment" => ""),
						"about" => array("type" => "text", "comment" => ""),
						"summary" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"music" => array("type" => "text", "comment" => ""),
						"book" => array("type" => "text", "comment" => ""),
						"tv" => array("type" => "text", "comment" => ""),
						"film" => array("type" => "text", "comment" => ""),
						"interest" => array("type" => "text", "comment" => ""),
						"romance" => array("type" => "text", "comment" => ""),
						"work" => array("type" => "text", "comment" => ""),
						"education" => array("type" => "text", "comment" => ""),
						"contact" => array("type" => "text", "comment" => ""),
						"homepage" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"xmpp" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"photo" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"thumb" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"publish" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"net-publish" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid_is-default" => array("uid", "is-default"),
						)
				);
		$database["profile_check"] = array(
				"comment" => "DFRN remote auth use",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"cid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"dfrn_id" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"sec" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"expire" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["push_subscriber"] = array(
				"comment" => "Used for OStatus: Contains feed subscribers",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"callback_url" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"topic" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"nickname" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"push" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"last_update" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"secret" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["queue"] = array(
				"comment" => "Queue for messages that couldn't be delivered",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"cid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"network" => array("type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"last" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"content" => array("type" => "mediumtext", "comment" => ""),
						"batch" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"cid" => array("cid"),
						"created" => array("created"),
						"last" => array("last"),
						"network" => array("network"),
						"batch" => array("batch"),
						)
				);
		$database["register"] = array(
				"comment" => "registrations requiring admin approval",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"hash" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"password" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"language" => array("type" => "varchar(16)", "not null" => "1", "default" => "", "comment" => ""),
						"note" => array("type" => "text", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["search"] = array(
				"comment" => "",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"term" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"uid" => array("uid"),
						)
				);
		$database["session"] = array(
				"comment" => "web session storage",
				"fields" => array(
						"id" => array("type" => "bigint", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"sid" => array("type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""),
						"data" => array("type" => "text", "comment" => ""),
						"expire" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"sid" => array("sid(64)"),
						"expire" => array("expire"),
						)
				);
		$database["sign"] = array(
				"comment" => "Diaspora signatures",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"iid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("item" => "id"), "comment" => ""),
						"signed_text" => array("type" => "mediumtext", "comment" => ""),
						"signature" => array("type" => "text", "comment" => ""),
						"signer" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"iid" => array("UNIQUE", "iid"),
						)
				);
		$database["term"] = array(
				"comment" => "item taxonomy (categories, tags, etc.) table",
				"fields" => array(
						"tid" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"oid" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("item" => "id"), "comment" => ""),
						"otype" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"type" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"term" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"url" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"guid" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"received" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"global" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"aid" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						),
				"indexes" => array(
						"PRIMARY" => array("tid"),
						"oid_otype_type_term" => array("oid","otype","type","term(32)"),
						"uid_otype_type_term_global_created" => array("uid","otype","type","term(32)","global","created"),
						"uid_otype_type_url" => array("uid","otype","type","url(64)"),
						"guid" => array("guid(64)"),
						)
				);
		$database["thread"] = array(
				"comment" => "Thread related data",
				"fields" => array(
						"iid" => array("type" => "int", "not null" => "1", "default" => "0", "primary" => "1", "relation" => array("item" => "id"), "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						"contact-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"gcontact-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("gcontact" => "id"), "comment" => ""),
						"owner-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"author-id" => array("type" => "int", "not null" => "1", "default" => "0", "relation" => array("contact" => "id"), "comment" => ""),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"edited" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"commented" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"received" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"changed" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"wall" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"private" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"pubmail" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"moderated" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"visible" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"spam" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"starred" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"ignored" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"bookmark" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"unseen" => array("type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""),
						"deleted" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"origin" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"forum_mode" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"mention" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"network" => array("type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("iid"),
						"uid_network_commented" => array("uid","network","commented"),
						"uid_network_created" => array("uid","network","created"),
						"uid_contactid_commented" => array("uid","contact-id","commented"),
						"uid_contactid_created" => array("uid","contact-id","created"),
						"contactid" => array("contact-id"),
						"ownerid" => array("owner-id"),
						"authorid" => array("author-id"),
						"uid_created" => array("uid","created"),
						"uid_commented" => array("uid","commented"),
						"uid_wall_created" => array("uid","wall","created"),
						"private_wall_commented" => array("private","wall","commented"),
						)
				);
		$database["tokens"] = array(
				"comment" => "OAuth usage",
				"fields" => array(
						"id" => array("type" => "varchar(40)", "not null" => "1", "primary" => "1", "comment" => ""),
						"secret" => array("type" => "text", "comment" => ""),
						"client_id" => array("type" => "varchar(20)", "not null" => "1", "default" => "", "relation" => array("clients" => "client_id")),
						"expires" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"scope" => array("type" => "varchar(200)", "not null" => "1", "default" => "", "comment" => ""),
						"uid" => array("type" => "mediumint", "not null" => "1", "default" => "0", "relation" => array("user" => "uid"), "comment" => "User id"),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						)
				);
		$database["user"] = array(
				"comment" => "The local users",
				"fields" => array(
						"uid" => array("type" => "mediumint", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"guid" => array("type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""),
						"username" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"password" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"nickname" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"email" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"openid" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"timezone" => array("type" => "varchar(128)", "not null" => "1", "default" => "", "comment" => ""),
						"language" => array("type" => "varchar(32)", "not null" => "1", "default" => "en", "comment" => ""),
						"register_date" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"login_date" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"default-location" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"allow_location" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"theme" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"pubkey" => array("type" => "text", "comment" => ""),
						"prvkey" => array("type" => "text", "comment" => ""),
						"spubkey" => array("type" => "text", "comment" => ""),
						"sprvkey" => array("type" => "text", "comment" => ""),
						"verified" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"blocked" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"blockwall" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"hidewall" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"blocktags" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"unkmail" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"cntunkmail" => array("type" => "int", "not null" => "1", "default" => "10", "comment" => ""),
						"notify-flags" => array("type" => "smallint unsigned", "not null" => "1", "default" => "65535", "comment" => ""),
						"page-flags" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"account-type" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""),
						"prvnets" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"pwdreset" => array("type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""),
						"maxreq" => array("type" => "int", "not null" => "1", "default" => "10", "comment" => ""),
						"expire" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"account_removed" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"account_expired" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""),
						"account_expires_on" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"expire_notification_sent" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""),
						"def_gid" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => ""),
						"allow_cid" => array("type" => "mediumtext", "comment" => ""),
						"allow_gid" => array("type" => "mediumtext", "comment" => ""),
						"deny_cid" => array("type" => "mediumtext", "comment" => ""),
						"deny_gid" => array("type" => "mediumtext", "comment" => ""),
						"openidserver" => array("type" => "text", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("uid"),
						"nickname" => array("nickname(32)"),
						)
				);
		$database["userd"] = array(
				"comment" => "Deleted usernames",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""),
						"username" => array("type" => "varchar(255)", "not null" => "1", "comment" => ""),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"username" => array("username(32)"),
						)
				);
		$database["workerqueue"] = array(
				"comment" => "Background tasks queue entries",
				"fields" => array(
						"id" => array("type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "Auto incremented worker task id"),
						"parameter" => array("type" => "text", "comment" => "Task command"),
						"priority" => array("type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Task priority"),
						"created" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Creation date"),
						"pid" => array("type" => "int", "not null" => "1", "default" => "0", "comment" => "Process id of the worker"),
						"executed" => array("type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Execution date"),
						"done" => array("type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Marked when the task was done, will be deleted later"),
						),
				"indexes" => array(
						"PRIMARY" => array("id"),
						"pid" => array("pid"),
						"parameter" => array("parameter(64)"),
						"priority_created" => array("priority", "created"),
						"executed" => array("executed"),
						)
				);

		return($database);
	}
}
