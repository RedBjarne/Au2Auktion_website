<?php
	// Bruger / byder
	
	require_once($_document_root . "/modules/$module/inc/functions.php");
	
	$usr = new user($module . "_cust");
	
	if ($do == "logged_in")
	{
		$html .= ($usr->logged_in ? "1" : "0");
	}
	elseif (!$usr->logged_in)
	{
		if ($do == "login")
		{
			// Login
			$frm = new form;
			if (isset($vars["when_done"])) $frm->hidden("when_done", $vars["when_done"]);
			$frm->submit_text = "Log ind";
			$frm->input(
				"Brugernavn",
				"username",
				"",
				"^.+$",
				"Påkrævet"
				);
			$frm->password(
				"Password",
				"password",
				"",
				"^.+$",
				"Påkrævet"
				);
				
			if ($frm->done())
			{
				if ($usr->login($frm->values["username"], $frm->values["password"]))
				{
					if (isset($vars["when_done"])) 
					{
						header("Location: " . $vars["when_done"]);
						exit;
					}
					else
					{
						header("Location: /LOGGET_IND");
						exit;
					}
				}
				
				$msg = new message;
				$msg->type("error");
				$msg->title("<div class=\"error_login_msg\">Fejl i brugernavn eller password</div>");
				$html .= $msg->html();
			}
			
			// Tilmeld
			$frm_signup = new form("signup");
			$frm_signup->submit_text = "Opret bruger";
			$frm_signup->select(
				"Brugertype",
				"type",
				"",
				"^.+$",
				"Påkrævet",
				"",
				array(
					array("", ""),
					array("private", "Privat"),
					array("dealer", "Forhandler")
					)
				);
			$frm_signup->input(
				"Navn",
				"name",
				"",
				"^.+$",
				"Påkrævet"
				);
			$frm_signup->input(
				"Adresse",
				"address",
				"",
				"^.+$",
				"Påkrævet"
				);
			$frm_signup->input(
				"Adresse 2",
				"address2",
				""
				);
			$frm_signup->input(
				"Postnr",
				"zipcode",
				"",
				"^.+$",
				"Påkrævet"
				);
			$frm_signup->input(
				"By",
				"city",
				"",
				"^.+$",
				"Påkrævet"
				);
			$frm_signup->input(
				"Telefon",
				"phone",
				"",
				"^.+$",
				"Påkrævet"
				);
			$frm_signup->input(
				"E-mail",
				"email",
				"",
				"^.+$",
				"Påkrævet"
				);
				
			$frm_signup->input(
				"CVR-nr",
				"vat",
				"",
				$vars["type"] == "dealer" ? "^.+$" : "",
				"Påkrævet"
				);
				
			$frm_signup->input(
				"Brugernavn",
				"username",
				"",
				"^[a-z_\-\.\@]{3,15}$",
				"3-15 tegn, bestående af: a-z - _ . @",
				'
					$usrobject = new user("' . $module . '_cust");
					if ($user = $usrobject->get_user_from_username($this->values["username"]))
					{
						$error = "Brugernavn allerede i brug";
					}
				'
				);
			$frm_signup->password(
				"Password",
				"password",
				"",
				"^.{6,}$",
				"Mindst 6 tegn"
				);
				
			if ($frm_signup->done())
			{
				$usr->ereg_username = ".";
				$usr->ereg_password = ".";
				$id = $usr->create($frm_signup->values["username"], $frm_signup->values["password"]);
				if (is_numeric($id))
				{
					// Lægger 100 til ID så vi undgår overlap
					$db->execute("
						UPDATE
							" . $_table_prefix . "_user_" . $module . "_cust
						SET
							id = '" . ($id + 100) . "'
						WHERE
							id = '$id'
						");
					$id += 100;
					
					$usr->update($id, array(
						"name" => $frm_signup->values["name"],
						"address" => trim($frm_signup->values["address"] . "\n" . $frm_signup->values["address2"]),
						"zipcode" => $frm_signup->values["zipcode"],
						"city" => $frm_signup->values["city"],
						"phone" => $frm_signup->values["phone"],
						"email" => $frm_signup->values["email"],
						"vat" => ($frm_signup->values["type"] == "dealer" ? $frm_signup->values["vat"] : "")
						));
					
					$usr->extra_set("type", $frm_signup->values["type"], $id);	
					$usr->extra_set("bank_regno", $frm_signup->values["bank_regno"], $id);
					$usr->extra_set("bank_account", $frm_signup->values["bank_account"], $id);
					
					// Forhandler?
					if ($frm_signup->values["type"] == "dealer") $usr->deactivate($id);
						
					OBA_sync_sql_row($_table_prefix . "_user_" . $module . "_cust", $id);
						
					if ($frm_signup->values["type"] == "dealer") 
					{
						// Send mail til admin om ny forhandler
						$e = new email;
						$e->to($_settings_["SITE_EMAIL"]);
						$e->subject("Ny forhandler skal godkendes");
						$e->body(
							"Navn: " . $frm_signup->values["name"] . "<br>" .
							"Adresse: " . $frm_signup->values["address"] . "<br>" .
							"Adresse 2: " . $frm_signup->values["address2"] . "<br>" .
							"Postnr: " . $frm_signup->values["zipcode"] . "<br>" .
							"By: " . $frm_signup->values["city"] . "<br>" .
							"Telefon: " . $frm_signup->values["phone"] . "<br>" .
							"E-mail: " . $frm_signup->values["email"] . "<br>" .
							"CVR-nr: " . $frm_signup->values["vat"] . "<br>" .
							"<br>" .
							"Forhandler kan godkendes i admin under Kunder -> Ikke godkendte");
						$e->send();
						
						header("Location: /Forhandler_oprettet");
						exit;
					}
					else
					{
						// Log ind
						$usr->login($frm_signup->values["username"], $frm_signup->values["password"]);						
						
						header("Location: /LOGGET_IND");
						exit;
					}
				}
			}
			
			$tmp = new tpl("MODULE|$module|user_login");
			$tmp->set("form", $frm->html());
			$tmp->set("form_signup", $frm_signup->html());
			$html .= $tmp->html();
			
		}
		else
		{
			header("Location: /site/$_lang_id/$module/$page/login");
			exit;
		}
	}
	else
	{
		if ($do == "login")
		{
			header("Location: /LOGGET_IND");
			exit;
		}
		
		if ($do == "logout")
		{
			// Log af
			$usr->logout();
			
			header("Location: /site/$_lang_id/$module/$page/login");
			exit;
		}
		elseif ($do == "profile")
		{
			// Profil
			$frm = new form;
			
			$frm->tplprefix("MODULE|$module|user_profile"); // Prefiks til formularens html-filer
			
			$frm->submit_text = "Gem profil";
			$frm->input(
				"Navn",
				"name",
				stripslashes($usr->data["name"]),
				"^.+$",
				"Påkrævet"
				);
			$frm->input(
				"Adresse",
				"address",
				$usr->data["address"],
				"^.+$",
				"Påkrævet"
				);
			
			/*
			$frm->input(
				"Adresse 2",
				"address2",
				$a2
				);
			*/	
			
			$frm->input(
				"Postnr",
				"zipcode",
				stripslashes($usr->data["zipcode"]),
				"^.+$",
				"Påkrævet"
				);
			$frm->input(
				"By",
				"city",
				stripslashes($usr->data["city"]),
				"^.+$",
				"Påkrævet"
				);
			$frm->input(
				"Telefon",
				"phone",
				$usr->data["phone"],
				"^.+$",
				"Påkrævet"
				);
			$frm->input(
				"E-mail",
				"email",
				stripslashes($usr->data["email"]),
				"^.+$",
				"Påkrævet"
				);
				
			/*
			if ($usr->extra_get("type") == "dealer")
			{
				$frm->tpl(
					"td2", 
					"CVR-nr:", 
					$usr->data["vat"]
					);
			}
			$frm->input(
				"Bank - Reg.nr.",
				"bank_regno",
				$usr->data["extra_bank_regno"],
				"^[0-9]{4}$",
				"Ugyldigt reg.nr. - skal være 4 tal"
				);
			$frm->input(
				"Bank - Kontonr.",
				"bank_account",
				$usr->data["extra_bank_account"],
				"^[0-9]{10}$",
				"Ugyldigt kontonr. - skal være 10 tal"
				);
			*/
			
			$frm->hidden("username", $usr->data["username"]);
				
			if ($frm->done())
			{
				$usr->update($usr->user_id, array(
					"name" => $frm->values["name"],
					"address" => trim($frm->values["address"] . "\n" . $frm->values["address2"]),
					"zipcode" => $frm->values["zipcode"],
					"city" => $frm->values["city"],
					"email" => $frm->values["email"],
					"phone" => $frm->values["phone"]
					));
					
				/*
				$usr->extra_set("bank_regno", $frm->values["bank_regno"]);
				$usr->extra_set("bank_account", $frm->values["bank_account"]);
				*/
				
				if ($frm->values["new_password"] != "") $usr->change_password($frm->values["new_password"], $usr->user_id);
				
				$usr->extra_set("profile_saved", "true");
				
				OBA_sync_sql_row($_table_prefix . "_user_" . $module . "_cust", $usr->user_id);
				
				unset($_SESSION[$module . "_gps_" . $usr->user_id]);
				
				header("Location: /LOGGET_IND");
				exit;
			}
			
			$html .= $frm->html();
		}
	}