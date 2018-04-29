<?
	/*COPYRIGHT*\
		COPYRIGHT STADEL.DK 2006
		
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
		Beskrivelse:	Styring af kontaktformularer
	*/
	
	// Overskrift
	$msg = new message;
	$msg->title(module2title($module));
	$msg->message("Herunder kan du administrere kontaktformularer.");
	$html .= $msg->html();
	
	// Laver oversigt over layouts
	$array_layouts = array();
	$file = new file;
	$files = $file->find_files($_document_root . "/modules/$module/html/", false);
	for ($i = 0; $i < count($files); $i++)
	{
		if (!ereg("_send\.html$", $files[$i]))
		{
			$array_layouts[count($array_layouts)] = array(
				str_replace(".html", "", $files[$i]),
				str_replace(".html", "", $files[$i])
				);
		}
	}
	
	if ($do == "delete_form")
	{
		//
		// Slet kontaktformular
		//
		
		// Sletter
		$db->execute("
			DELETE FROM
				" . $_table_prefix . "_module_" . $module . "_forms
			WHERE
				id = '$id'
			");
			
		// Tilbage
		header("Location: ?module=$module&page=$page");
		exit;
		
	}
	elseif ($do == "add_form" or $do == "edit_form")
	{
		//
		// Tilføj kontaktformular
		//
		
		// Links
		$links = new links;
		$links->link("Tilbage");
		$html .= $links->html();
		
		// Henter info om formular hvis redigering
		if ($do == "edit_form")
		{
			$db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_forms
				WHERE
					id = '$id'
				");
			if (!$res = $db->fetch_array())
			{
				// Ikke fundet - tilbage
				header("Location: ?module=$module&page=$page");
				exit;
			}
		}
		else
		{
			$res = array();
		}
		
		// Formular
		$frm = new form;
		$frm->tpl("th", $do == "add_form" ? "Tilføj kontaktformular" : "Rediger kontaktformular");
		$frm->input(	
			"Navn",
			"title",
			stripslashes($res["title"]),
			"^.+$",
			"Skal udfyldes"
			);
		$frm->textarea(
			"Send e-mail til (én per linie)",
			"emails",
			stripslashes($res["emails"]),
			"^.+$",
			"Skal udfyldes"
			);
		$frm->select(
			"Layout",
			"layout",
			$res["layout"],
			"^[a-zA-Z0-9_-]+$",
			"Vælg et layout til formularen",
			"",
			$array_layouts
			);
		$frm->tpl("th", "Bekræftelses mail (undlad hvis ingen bekræftelse)");
		$frm->input(
			"Navn på felt med e-mail",
			"email_field",
			$res["email_field"]
			);
		$frm->input(
			"Emne",
			"email_subject",
			stripslashes($res["email_subject"]),
			"",
			"",
			'
				if ($this->values["email_field"] <> "" and $this->values["email_subject"] == "")
				{
					$error = "Skal udfyldes hvis der ønskes bekræftelses mail";
				}
			'
			);
		$frm->textarea(
			"Besked",
			"email_body",
			stripslashes($res["email_body"]),
			"",
			"",
			'
				if ($this->values["email_field"] <> "" and $this->values["email_body"] == "")
				{
					$error = "Skal udfyldes hvis der ønskes bekræftelses mail";
				}
			'
			);
			
		if ($frm->done())
		{
			if ($do == "add_form")
			{
				// Tilføjer formular
				$db->execute("
					INSERT INTO
						" . $_table_prefix . "_module_" . $module . "_forms
					(
						title,
						emails,
						layout,
						email_field,
						email_subject,
						email_body
					)
					VALUES
					(
						'" . addslashes($frm->values["title"]) . "',
						'" . addslashes($frm->values["emails"]) . "',
						'" . addslashes($frm->values["layout"]) . "',
						'" . addslashes($frm->values["email_field"]) . "',
						'" . addslashes($frm->values["email_subject"]) . "',
						'" . addslashes($frm->values["email_body"]) . "'
					)
					");
			}
			else
			{
				// Opdaterer formular
				$db->execute("
					UPDATE
						" . $_table_prefix . "_module_" . $module . "_forms
					SET
						title = '" . addslashes($frm->values["title"]) . "',
						emails = '" . addslashes($frm->values["emails"]) . "',
						layout = '" . addslashes($frm->values["layout"]) . "',
						email_field = '" . addslashes($frm->values["email_field"]) . "',
						email_subject = '" . addslashes($frm->values["email_subject"]) . "',
						email_body = '" . addslashes($frm->values["email_body"]) . "'
					WHERE
						id = '$id'
					");
			}
			// Tilbage
			header("Location: ?module=$module&page=$page");
			exit;
		}
			
		$html .= $frm->html();
		
	}
	else
	{
		//
		// Oversigt over kontaktformularer
		//
		
		// Links
		$links = new links;
		$links->link("Tilføj kontaktformular", "add_form");
		$html .= $links->html();
		
		// Tabel
		$tbl = new table;
		$tbl->th("Navn");
		$tbl->th("Layout");
		$tbl->th("Bekræftelses mail");
		$tbl->th("Valg", 2);
		$tbl->endrow();
		
		// Finder formularer
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_forms
			ORDER BY
				title
			");
		while ($db->fetch_array())
		{
			$tbl->td(stripslashes($db->array["title"]));
			$tbl->td($db->array["layout"]);
			$tbl->td($db->array["email_field"] <> "" ? "Ja" : "-", 1, 1, "center");
			$tbl->choise("Ret", "edit_form", $db->array["id"]);
			$tbl->choise("Slet", "delete_form", $db->array["id"], "Slet denne formular?");
			$tbl->endrow();
		}
		
		if ($db->num_rows() == 0)
		{
			$tbl->td("Ingen...", 4);
			$tbl->endrow();
		}
		
		// Viser oversigt
		$html .= $tbl->html();
	}
?>