<?php
				// MySQL tabel struktur
				$mysql_structure = array (
  $_table_prefix . '_module_' . $module . '_pages' => 
  array (
    'fields' => 
    array (
      'id' => '`id` int(10) unsigned NOT NULL auto_increment',
      'title' => '`title` varchar(50) NOT NULL',
      'html' => '`html` text NOT NULL',
      'html_da' => '`html_da` text DEFAULT NULL',
      'html_en' => '`html_en` text DEFAULT NULL',
      'keywords_da' => '`keywords_da` text DEFAULT NULL',
      'public_da' => '`public_da` tinyint(1) unsigned NOT NULL DEFAULT \'0\'',
      'public' => '`public` tinyint(1) unsigned NOT NULL DEFAULT \'1\'',
      'keywords_en' => '`keywords_en` text DEFAULT NULL',
      'group' => '`group` varchar(25) NOT NULL',
      'class' => '`class` varchar(25) NOT NULL',
    ),
    'keys' => 
    array (
      'PRIMARY' => 'PRIMARY KEY (`id`)',
    ),
  ),
);
				
				// Gennemløber
				reset($mysql_structure);
				while (list($table, $table_structure) = each($mysql_structure))
				{
					// Tjekker om tabel findes
					$table_found = $db->execute_field("SHOW TABLES LIKE '$table'");
					$sql = "";
					
					// Gennemløber felter
					reset($table_structure["fields"]);
					while (list($field, $field_structure) = each($table_structure["fields"]))
					{
						if (!$table_found)
						{
							if ($sql != "") $sql .= ", ";
							$sql .= $field_structure;
						}
						else
						{
							// Tjekker om felt findes
							if (!$db->execute_field("
								SHOW FIELDS FROM
									`$table`
								WHERE
									`Field` = '$field'
								"))
							{
								// Opretter felt
								add_log_message("Opretter felt $table . $field");
								$db->execute("
									ALTER TABLE
										`$table`
									ADD
										$field_structure
									");
							}
						}
					}
					
					// Gennemløber nøgler
					reset($table_structure["keys"]);
					while (list($key, $key_structure) = each($table_structure["keys"]))
					{
						if (!$table_found)
						{
							$sql .= ", " . $key_structure;
						}
						else
						{
							// Tjekker om nøgle findes
							if (!$db->execute_field("
								SHOW KEYS FROM
									`$table`
								WHERE
									`Key_name` = '$key'
								"))
							{
								// Opretter nøgle
								add_log_message("Opretter nøgle $table . $key");
								$db->execute("
									ALTER TABLE
										`$table`
									ADD
										$key_structure
									");
							}
						}
					}

					if (!$table_found)
					{					
						// Opretter tabel
						add_log_message("Opretter tabel $table");
						$db->execute("
							CREATE TABLE
								`$table`
							(
								$sql
							)
							");
					}
				}
				?>