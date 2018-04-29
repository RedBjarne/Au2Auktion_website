<?php
	// SMS modtagelse
	
	if ($_SERVER["REMOTE_ADDR"] != gethostbyname("sms.stadel.dk")) exit;
	
	$message = $vars["message"];
	$mobile = preg_replace("/^45/", "", $vars["mobile"]);
	if (!preg_match("/^[0-9]{8}$/", $mobile)) exit;
	
	if ($do == "qr_login" and preg_match("/^AUKTION LOGIN (.+)$/", $message, $array))
	{
		// Log ind via QR kode
		$secret = $array[1];
	
		// Finder QR kode
		$db->execute("
			DELETE FROM
				" . $_table_prefix . "_module_" . $module . "_qr_login
			WHERE
				`time` < '" . date("Y-m-d H:i:s", strtotime("-1 hour")) . "'
			");
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_qr_login
			WHERE
				`secret` = '" . $db->escape($secret) . "' AND
				`secret` <> ''
			");
		if ($res = $db->fetch_array())
		{
			// Finder bruger
			$usr = new user($module . "_users");
			if ($user = $usr->get_user_from_username($mobile))
			{
				// Login OK
				$db->execute("
					UPDATE
						" . $_table_prefix . "_module_" . $module . "_qr_login
					SET
						user_id = '" . $user["id"] . "'
					WHERE
						id = '" . $res["id"] . "'
					");
				send_sms("45" . $mobile, "Velkommen tilbage. Du logges nu automatisk på.\r\nMvh. " . $_settings_["SITE_TITLE"], "4542420321");
			}
			else
			{
				// Opretter bruger
				$usr->ereg_username = ".";
				$usr->ereg_password = ".";
				$uid = $usr->create($mobile, create_password(6));
				$usr->update($uid, array(
					"name" => $vars["name"],
					"address" => $vars["address"],
					"zipcode" => $vars["zipcode"],
					"city" => $vars["city"],
					"mobile" => $mobile
					));
				$db->execute("
					UPDATE
						" . $_table_prefix . "_module_" . $module . "_qr_login
					SET
						user_id = '" . $uid . "'
					WHERE
						id = '" . $res["id"] . "'
					");
				send_sms("45" . $mobile, "Velkommen som bruger. Du logges nu automatisk på.\r\nMvh. " . $_settings_["SITE_TITLE"], "4542420321");
			}
		}
		else
		{
			send_sms("45" . $mobile, "Den sendte kode er udløbet. Opdater siden og forsøg igen.\r\nMvh. " . $_settings_["SITE_TITLE"], "4542420321");
		}
	}
	elseif ($do == "create_user")
	{
		// Opret bruger og send password på SMS
		
		// Opretter password
		$password = create_password(6);
		
		// Finder bruger
		$usr = new user($module . "_users");
		if ($user = $usr->get_user_from_username($mobile))
		{
			// Bruger findes, skifter password
			$usr->change_password($password, $user["id"]);
		}
		else
		{
			// Opretter bruger
			$usr->ereg_username = ".";
			$usr->ereg_password = ".";
			$uid = $usr->create($mobile, $password);
			$usr->update($uid, array(
				"name" => $vars["name"],
				"address" => $vars["address"],
				"zipcode" => $vars["zipcode"],
				"city" => $vars["city"],
				"mobile" => $mobile
				));
		}
		
		// Sender password
		send_sms("45" . $mobile, "Dit password er: $password\r\nMvh. " . $_settings_["SITE_TITLE"], "4542420321");
			
	}
	
	exit;