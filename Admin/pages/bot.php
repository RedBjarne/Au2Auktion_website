<?php
	/*
		Kører scripts, der skal køres med jævne mellemrum
		Dog kun så længe en bruger er logget ind, hvis ikke cronjob url benyttes
	*/
	
	global $usr, $module;
	
	// Er det cronjob?
	$check = cms_setting("bot_check");
	if ($check == $vars["check"] and $check != "")
	{
		// Cronjob
		$run = true;
		cms_setting("bot_cronjob_time", time());
		add_log_message("Cronjob OK");
	}
	elseif ($usr->logged_in)
	{
		// Admin-bruger - tjek om cronjob url har været aktiv seneste time
		$run = (intval(cms_setting("bot_cronjob_time")) < time() - 3600);
	}
	else
	{
		// Ikke logget ind
		$run = false;
	}
	
	if ($run)
	{
		// Interval for bot i sekunder
		$bot_interval = ini_get("max_execution_time");
		$timeout = time() + $bot_interval - 5;
		
		// Tjekker for sidste kørsel
		if (intval(cms_setting("next_bot_time")) <= time() or $vars["check"] != "")
		{
			// Sætter næste kørsel
			cms_setting("next_bot_time", time() + $bot_interval);
			
			// Tjekker for return-mail
			if ($_settings_["RETURN_EMAIL_SERVER"] != "" and $_settings_["RETURN_EMAIL_USER"] != "" and $_settings_["RETURN_EMAIL_PASS"] != "")
			{
				$p = new pop3;
				if ($p->connect($_settings_["RETURN_EMAIL_SERVER"]) and $p->login($_settings_["RETURN_EMAIL_USER"], $_settings_["RETURN_EMAIL_PASS"]))
				{		
					// Henter antal mails
					list($count, $bytes) = $p->stat();
					
					// Gennemløber mail
					for ($id = 1; $id <= $count and $timeout > time(); $id++)
					{
						// Tjekker mail størrelse
						$list = $p->get_list($id);
	
						if ($list[$id] > 1*1024*1024)
						{
							// Sletter uden at hente
							$p->delete_mail($id);
							
							// Tilføj til log
							add_log_message("Return mail bot\r\n" . 
								"State: Mail $id larger than 1 Mb - skipping");
						}
						else
						{		
							// Henter mail
							$mail = $p->get_mail($id);
							$mail = str_replace("\r", "", $mail);
							
							// Sletter mail
							$p->delete_mail($id);
	
							// Finder message-id
							if (eregi("message-id: <Stadel\.dk\.CMS\.([a-z]{0,2})\.([a-z0-9_-]*)\.([a-z0-9_-]*)\.([a-z0-9_-]*)\.([0-9]*)\.([^@]*)@[^>]+>", $mail, $array))
							{
								$tmp_lang_id = $array[1];
								$tmp_module = $array[2];
								$tmp_page = $array[3];
								$tmp_do = $array[4];
								$tmp_id = $array[5];
								$tmp_message_id = $array[6];
								
								// Finder original modtager					
								if (eregi("(original|final)-recipient: (rfc822;){0,1}([^\n]+)\n", $mail, $array1))
								{
									$tmp_email = trim($array1[3]);
								}
								else
								{
									$tmp_email = "";
								}
								
								// Finder tid for mail
								if (eregi("\ndate: ([^\n]+)\n", $mail, $array))
								{
									$tmp_time = date("Y-m-d H:i:s", strtotime(trim($array[1])));
								}
								else
								{
									$tmp_time = date("Y-m-d H:i:s");
								}
								
								// Indsætter i database
								$db->execute("
									INSERT INTO
										" . $_table_prefix . "_return_mail
									(
										`time`,
										lang_id,
										module,
										page,
										`do`,
										id,
										message_id,
										email
									)
									VALUES
									(
										'" . $db->escape($tmp_time) . "',
										'" . $db->escape($tmp_lang_id) . "',
										'" . $db->escape($tmp_module) . "',
										'" . $db->escape($tmp_page) . "',
										'" . $db->escape($tmp_do) . "',
										'" . $db->escape($tmp_id) . "',
										'" . $db->escape($tmp_message_id) . "',
										'" . $db->escape($tmp_email) . "'
									)
									");
									
								// Tilføj til log
								add_log_message("Return mail bot\r\n" .
									"State: Got return mail\r\n" .
									"Original recipient: " . $tmp_email . "\r\n" .
									"ID: CMS." . $tmp_lang_id . "." . $tmp_module . "." . $tmp_page . "." . $tmp_do . "." . $tmp_id . "." . $tmp_message_id . "\r\n");
							}
							else
							{
								// Finder original modtager					
								if (eregi("from: ([^\n]+)\n", $mail, $array1))
								{
									$tmp_email = trim($array1[1]);
								}
								else
								{
									$tmp_email = "";
								}
								
								// Finder subject
								if (eregi("subject: ([^\n]+)\n", $mail, $array1))
								{
									$tmp_subject = trim($array1[1]);
								}
								else
								{
									$tmp_subject = "";
								}
								
								// Tilføj til log
								add_log_message("Return mail bot\r\n" .
									"State: Got unknown return mail\r\n" .
									"From: $tmp_email\r\n" .
									"Subject: $tmp_subject");
							}
						}
					}
	
					// Lukker forbindelse
					$p->close();
				}
				else
				{
					// Tilføj til log
					add_log_message("Return mail bot\r\n" .
						"Could not connect to POP3-server");
				}
			}
			
			// Kører admin bots for moduler
			
			// Henter alle installerede moduler
			$array_module_installed = admin_module_installed();
			
			// Inkluderer bot-filer fra moduler
			for ($int_module_installed = 0; $int_module_installed < count($array_module_installed) and $timeout > time(); $int_module_installed++)
			{
				if (is_file($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/admin/bot.php"))
				{
					$tmp_old_module = $module;
					$tmp_old_page = $page;
					$tmp_old_do = $do;
					$tmp_old_id = $id;
					$module = $array_module_installed[$int_module_installed];
					$page = "";
					$do = "";
					$id = 0;
					include($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/admin/bot.php");
					$module = $tmp_old_module;
					$page = $tmp_old_page;
					$do = $tmp_old_do;
					$id = $tmp_old_id;
				}
			}
			
		}
	}
?>