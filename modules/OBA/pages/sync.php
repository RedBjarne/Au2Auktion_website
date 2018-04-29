<?php
	/*
		Sync
	*/

	// Tjek IP-adresse
	//if ($_SERVER["REMOTE_ADDR"] != gethostbyname("webmail.softhouse.dk")) die("ERROR|Access denied from " . $_SERVER["REMOTE_ADDR"]);
	if ($vars["secret"] != "ntmh6e4Z4T7ZK4uqJb2qkQ7NeDeRSSu2Z67RuZN3748QsWQ3Hsdi5sJQFFwZ9w99") die("ERROR|Wrong password");
	
	// Parametre
	$action = $vars["action"];
	$data = $vars["data"];
	
	if ($action == "SETTING")
	{
		// Indstilling
		
		list($key, $val) = explode("|", $data);
		module_setting($key, $val);
		die("OK");
		
	}
	elseif ($action == "SQL")
	{
		// SQL
		
		if (!$db->execute($data))
		{
			$err = mysql_error();
			if (!preg_match("/^Duplicate entry/", $err))
			{
				echo("ERROR|$err");
				exit;
			}
		}
		
		die("OK");
		
	}
	elseif ($action == "SAVE_FILE")
	{
		// Fil, der skal gemmes
		
		if (is_uploaded_file($_FILES["file"]["tmp_name"]) and
			move_uploaded_file($_FILES["file"]["tmp_name"], $_document_root . "/modules/$module/upl/" . $data))
		{
			// OK
			die("OK");
		}
		else
		{
			// Fejl
			die("ERROR|Could not save file as: " . $_document_root . "/modules/$module/upl/" . $data);
		}
		
	}
	elseif ($action == "DELETE_FILE")
	{
		// Fil, der skal slettes
	
		if (!is_file($_document_root . "/modules/$module/upl/" . $data) or 
			unlink($_document_root . "/modules/$module/upl/" . $data))
		{
			// OK
			die("OK");
		}
		else
		{
			// Fejl
			die("ERROR|Could not delete file");
		}
	}
	elseif ($action == "ACTIVE_IDS")
	{
		// Opdater aktive auktions-ID
	
		list($prev_id, $cur_id, $next_id) = explode("|", $data);
		module_setting("prev_auction_id", $prev_id);
		module_setting("cur_auction_id", $cur_id);
		module_setting("next_auction_id", $next_id);
		
		die("OK");	

	}
	elseif ($action == "SYNC")
	{
		// Svar server med sync-kommando
		
		echo("OK");

		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_sync
			WHERE
				`error` = 0
			ORDER BY
				id
			");
		if ($res = $db->fetch_array())
		{
			if ($res["action"] == "SAVE_FILE" and preg_match("/^[a-zA-Z0-9_\-]+\.jpg$/", $res["data"]))
			{
				if (is_file($_document_root . "/modules/$module/upl/" . $res["data"]))
				{
					// Produktions-site
					$data = $res["data"] . "|" . base64_encode(file_get_contents($_document_root . "/modules/$module/upl/" . $res["data"]));
				}
			}
			else
			{
				$data = $res["data"];
			}
			echo("|" . $res["id"] . "|" . $res["action"] . "|" . $data);
		}
		
		exit;
		
	}
	elseif ($action == "SYNC_OK")
	{
		// Server bekræfter at sync er udført korrekt
		
		$db->execute("
			DELETE FROM
				" . $_table_prefix . "_module_" . $module . "_sync
			WHERE
				id = '" . intval($data) . "'
			");
		
		die("OK");
		
	}
	elseif ($action == "SYNC_ERROR")
	{
		// Server siger at sync er udført med fejl
		
		$db->execute("
			UPDATE
				" . $_table_prefix . "_module_" . $module . "_sync
			SET
				`error` = 1
			WHERE
				id = '" . intval($data) . "'
			");
		
		die("OK");
		
	}
	
	echo("ERROR|Unkworn error");

	exit;