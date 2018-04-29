<?php
	/*COPYRIGHT*\
		COPYRIGHT STADEL.DK 2011
		
		AL KODE I DENNE FIL TILHØRER STADEL.DK, THOMAS@STADEL.DK.
		KODEN MÅ UNDER INGEN  OMSTÆNDIGHEDER  BENYTTES  TIL ANDET
		FORMÅL END  DET DEN ER KØB TIL.  KODEN MÅ IKKE  ÆNDRES AF
		ANDRE   END   STADEL.DK.   KODEN  MÅ  IKKE  SÆLGES  ELLER
		VIDEREDISTRIBUERES  HELT, DELVIS ELLER SOM EN KOPI AF DET
		SYSTEM   DET  OPRINDELIGT  ER  KØBT  SAMMEN  MED.  ENHVER
		OVERTRÆDELSE  AF EN ELLER FLERE AF DE NÆVNTE  BETINGELSER
		VIL RESULTERE I RETSFORFØLGELSE OG ERSTATNING FOR BRUD PÅ
		OPHAVSRETTEN AF KODEN, IFLG.  DANSK  OPHAVSRETSLOV. DENNE
		COPYRIGHT    MEDDELELSE    MÅ    DESUDEN    UNDER   INGEN
		OMSTÆNDIGHEDER FJERNES FRA DENNE FIL.
	
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
		Version:		09-01-2006
		Beskrivelse:	Visning af kontaktformular
	*/
	
	// Tjekker ID for kontaktformular
	$db->execute("
		SELECT
			*
		FROM
			" . $_table_prefix . "_module_" . $module . "_forms
		WHERE
			id = '$id'
		");
	if (!$form = $db->fetch_array())
	{
		//
		// Formular ikke fundet
		//
		
		$html .= "{LANG|Kontaktformular} #$id {LANG|findes ikke}";
		
	}
	else
	{
		//
		// Viser kontaktformular
		//
		
		// Værdier til tpl
		$values = array();
		$values["id"] = $form["id"];
		$values["title"] = stripslashes($form["title"]);
		
		// Henter alle felter
		$customer_email = "";
		$subject = "";
		$array_fields = array();
		reset($vars);
		$array_files = array();
		while (list($key, $value) = each($vars))
		{
			if (ereg("(required|optional)_(number|date|time|email|phone|free|file)_(.+)$", $key, $tmp_array))
			{
				if ($tmp_array[2] == "file")
				{
					$array_files[$key] = $value;
				}
				else
				{
					// Gemmer felt
					$array_fields[count($array_fields)] = array(
						"required" => ($tmp_array[1] == "required" ? true : false),
						"format" => $tmp_array[2],
						"name" => $tmp_array[3],
						"value" => $value
						);
					if ($tmp_array[3] == $form["email_field"] and ereg("^[a-zA-Z0-9\._-]+@[a-zA-Z0-9\.-]+\.[a-zA-Z]+$", $value)) $customer_email = $value;
					if ($tmp_array[3] == 'subject') $subject = $value;
					$values["value_" . $tmp_array[3]] = $value;
				}
			}
		}
		
		// Filer
		reset($_FILES);
		while (list($key, $value) = each($_FILES)) $array_files[$key] = $value;
		$file = new file;
		reset($array_files);
		while (list($key, $value) = each($array_files))
		{
			if (ereg("(required|optional)_file_(.+)$", $key, $tmp_array))
			{
				// Filnavn
				$filename = $_document_root . "/tmp/" . $module . "_" . $form["id"] . "_" . md5($_SERVER["REMOTE_ADDR"]) . "_" . md5($tmp_array[2]);
				
				// Slet fil?
				if ($vars["delete_file_" . $tmp_array[2]] != "")
				{
					@unlink($filename . ".tmp");
					@unlink($filename . ".txt");
				}
				
				// Upload fil?
				if (is_uploaded_file($value["tmp_name"]))
				{
					if (move_uploaded_file($value["tmp_name"], $filename . ".tmp"))
					{
						file_put_contents($filename . ".txt", $value["name"]);
					}
				}
				
				if (is_file($filename . ".tmp") and is_file($filename . ".txt"))
				{
					$tmp = new tpl("MODULE|$module|" . $form["layout"] . ($do != "" ? ("_" . $do) : "") . "_file");
					$tmp->set("filename", file_get_contents($filename . ".txt"));
					$tmp->set("filesize", $file->size_formatted($filename . ".tmp"));
					$values["value_" . $tmp_array[2]] = $tmp->html();
					$value = $filename;
				}
				else
				{
					$values["value_" . $tmp_array[2]] = "";
					$value = "";
				}
				
				// Gemmer felt
				$array_fields[count($array_fields)] = array(
					"required" => ($tmp_array[1] == "required" ? true : false),
					"format" => "file",
					"name" => $tmp_array[2],
					"value" => $value
					);
			}
		}
			
		if ($do == "send" or $do == "confirm")
		{
			if ($do == "confirm" and !is_file($_document_root . "/modules/$module/html/" . $form["layout"] . "_confirm.html")) $do = "send";
			
			// Tjekker felter og laver e-mail body
			$count_errors = 0;
			$email_body = "";
			$email_attach = array();
			$email_fields = array();
			$data = "";
			for ($i = 0; $i < count($array_fields); $i++)
			{
				$field = $array_fields[$i];
				$error = "";
				if ($field["value"] == "")
				{
					if ($field["required"])
					{
						$error = "{LANG|Skal udfyldes}";
					}
				}
				elseif ($field["format"] == "number" and !ereg("^[0-9]*$", $field["value"]))
				{
					$error = "{LANG|Må kun indeholde tal}";
				}
				elseif ($field["format"] == "date" and !ereg("^[0-9]{1,2}[/-]{1}[0-9]{1,2}[/-]{1}[0-9]{2,4}$", $field["value"]))
				{
					$error = "{LANG|Ugyldigt format - format: dd-mm-åååå}";
				}
				elseif ($field["format"] == "time" and !ereg("^([0-1]{0,1}[0-9]{1}|2[0-4]{1})[:\.]{1}[0-5]{1}[0-9]{1}$", $field["value"]))
				{
					$error = "{LANG|Ugyldigt format - format: tt:mm}";
				}
				elseif ($field["format"] == "email" and !ereg("^[a-zA-Z0-9\._-]+@[a-zA-Z0-9\.-]+\.[a-zA-Z]+$", $field["value"]))
				{
					$error = "{LANG|Ugyldig e-mail}";
				}
				elseif ($field["format"] == "phone" and !ereg("^([0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}|[0-9]{8}){0,1}$", $field["value"]))
				{
					$error = "{LANG|Ugyldigt telefonnummer - format: AB CD EF GH}";
				}
				if ($error <> "")
				{
					$count_errors++;
					$values["error_" . $field["name"]] = $error;
				}
				elseif ($field["format"] == "file")
				{
					if (is_file($field["value"] . ".tmp") and is_file($field["value"] . ".txt"))
					{
						$filename = file_get_contents($field["value"] . ".txt");
						$filesize = $file->size_formatted($field["value"] . ".tmp");
						
						$tmp = new tpl("MODULE|$module|email_field");
						$tmp->set("field", str_replace("_", " ", $field["name"]));
						$tmp->set("value", $filename . " " . $filesize);
						$email_body .= $tmp->html();
						
						$data .= str_replace("_", " ", $field["name"]) . ": $filename $filesize\r\n";
						$email_attach[] = array($field["value"], $filename);
					}
					else
					{
						$tmp = new tpl("MODULE|$module|email_field");
						$tmp->set("field", str_replace("_", " ", $field["name"]));
						$tmp->set("value", "{LANG|Ingen fil vedhæftet}");
						$email_body .= $tmp->html();
						
						$email_fields[$field["name"]] = "{LANG|Ingen fil vedhæftet}";
						
						$data .= str_replace("_", " ", $field["name"]) . ": Ingen fil vedhæftet\r\n";
					}
				}
				else
				{
					$tmp = new tpl("MODULE|$module|email_field");
					$tmp->set("field", str_replace("_", " ", $field["name"]));
					$tmp->set("value", nl2br(trim($field["value"])));
					$email_body .= $tmp->html();
					
					$email_fields[$field["name"]] = nl2br(trim($field["value"]));
						
					$data .= str_replace("_", " ", $field["name"]) . ": " . trim($field["value"]) . "\r\n";
				}
			}
			// Er alt OK ?
			if ($count_errors == 0 and $do == "send")
			{
				// Afslutter mail
				if (is_file($_document_root . "/modules/$module/html/" . $form["layout"] . "_email.html"))
				{
					$tmp = new tpl("MODULE|$module|" . $form["layout"] . "_email");
				}
				else
				{
					$tmp = new tpl("MODULE|$module|email");
				}
				reset($email_fields);
				while (list($key, $value) = each($email_fields)) $tmp->set($key, $value);
				$tmp->set("title", stripslashes($form["title"]));
				$tmp->set("time", date("d-m-Y H:i:s"));
				$tmp->set("ip", $_SERVER["REMOTE_ADDR"]);
				$tmp->set("fields", $email_body);
				$email_body = $tmp->html();
				
				// Mail objekt
				$mail = new email;
				$mail->subject($subject != "" ? $subject : stripslashes($form["title"]));
				$mail->body($email_body);

				// Sætter bruger som afsender					
				if ($customer_email != "") $mail->from($customer_email, $customer_email);
				
				// Vedhæfter filer
				for ($i = 0; $i < count($email_attach); $i++) $mail->attach($email_attach[$i][0] . ".tmp", $email_attach[$i][1]);
				
				// Finder e-mails
				$array_emails = split("\n", str_replace("\r", "", stripslashes($form["emails"])));
				for ($i = 0; $i < count($array_emails); $i++)
				{
					$mail->to($array_emails[$i], $array_emails[$i]);
				}
				// Sender e-mail
				$mail->send();

				// Sender evt. bekræftelse til kunden
				if ($customer_email != "")
				{
					if (is_file($_document_root . "/modules/$module/html/" . $form["layout"] . "_customer_email.html"))
					{
						$tmp = new tpl("MODULE|$module|" . $form["layout"] . "_customer_email");
					}
					else
					{
						$tmp = new tpl(nl2br(stripslashes($form["email_body"])));
					}
					reset($email_fields);
					while (list($key, $value) = each($email_fields)) $tmp->set($key, $value);
					$email_body = $tmp->html();

					$mail = new email;
					$mail->subject(stripslashes($form["email_subject"]));
					$mail->body($email_body);
					$mail->from($_settings_["SITE_TITLE"], $_settings_["SITE_EMAIL"]);
					$mail->to($customer_email, $customer_email);
					$mail->send();
				}
				
				// Sletter filer
				for ($i = 0; $i < count($email_attach); $i++)
				{
					@unlink($email_attach[$i][0] . ".tmp");
					@unlink($email_attach[$i][0] . ".txt");
				}
				
				// Gemmer i databasen
				$db->execute("
					INSERT INTO
						" . $_table_prefix . "_module_" . $module . "_data
					(
						time,
						title,
						`data`,
						email,
						ip
					)
					VALUES
					(
						'" . date("Y-m-d H:i:s") . "',
						'" . addslashes($form["title"]) . "',
						'" . addslashes($data) . "',
						'" . addslashes($customer_email) . "',
						'" . $_SERVER["REMOTE_ADDR"] . "'
					)
					");
			}
			elseif ($count_errors > 0)
			{
				$do = "";
			}
		}
		else
		{
			$do = "";
		}
		
		$tmp = new tpl("MODULE|$module|" . $form["layout"] . ($do != "" ? ("_" . $do) : ""));
		reset($values);
		while (list($key, $value) = each($values)) $tmp->set($key, $value);
		$html .= $tmp->html();
	}
?>