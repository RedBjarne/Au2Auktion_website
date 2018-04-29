<?php
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
		Version:		19-01-2012
		Beskrivelse:	Editor til HTML-sider
	*/
	
	// Overskrift
	$msg = new message;
	$msg->title(module2title($module));
	$html .= $msg->html();
	
	if ($do == "add" or $do == "edit" or $do == "edit_keywords")
	{
		//
		// Tilføj / Rediger side
		//
		
		// Henter sprog
		$lang_array = languages_array();
		
		// Sprog der skal redigeres
		$lang = $vars["lang"];
		if (!$lang_array[$lang]) $lang = $_lang_id;
		$lang_title = $lang_array[$lang];
		
		if ($do == "edit" or $do == "edit_keywords")
		{
			// Henter side
			$db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_pages
				WHERE
					id = '$id'
				");
			if (!$res = $db->fetch_array())
			{
				// Tilbage
				header("Location: ?module=$module");
				exit;
			}
		}
		else
		{
			if ($id > 0)
			{
				// Henter side
				$db->execute("
					SELECT
						*
					FROM
						" . $_table_prefix . "_module_" . $module . "_pages
					WHERE
						id = '$id'
					");
				$res = $db->fetch_array();
			}
		}
		
		// Links
		$links = new links;
		if ($vars["return_url"] <> "")
		{
			$links->link("{LANG|Tilbage}", $vars["return_url"]);
		}
		else
		{
			$links->link("{LANG|Tilbage}", "", "&group=" . urlencode($vars["group"] != "" ? $vars["group"] : $res["group"]));
		}
		if ($do == "edit" or $do == "edit_keywords")
		{
			reset($lang_array);
			while (list($key, $value) = each($lang_array))
			{
				// Lidt hard-coded HTML for at lave ekstra link
				$links->link($value . "</a> | <a href=\"./?module=$module&page=$page&do=edit_keywords&id=$id&lang=$key\">{LANG|SEO}</a>", "edit", $id . "&lang=" . $key);
			}
		}
		$html .= $links->html();
		
		// Formular
		$frm = new form;
		$frm->submit_text = "{LANG|Gem}";
		$frm->hidden("return_url", $vars["return_url"]);
		$frm->hidden("lang", $lang);
		
		if ($do == "edit_keywords")
		{
			// Rediger søgeord
			$frm->tpl("th", "{LANG|Rediger søgeord} ($lang_title)");
			$frm->tpl("td2", "{LANG|Titel}:", stripslashes($res["title"]));
			$frm->textarea(
				"{LANG|Søgeord - et pr. linie}",
				"keywords",
				str_replace(",", "\r\n", stripslashes($res["keywords_" . $lang])),
				"",
				"",
				"",
				50,
				5
				);
		}
		else
		{			
			$select_groups = array();
			$groups = $db->execute("
				SELECT
					DISTINCT(`group`) AS x
				FROM
					" . $_table_prefix . "_module_" . $module . "_pages
				WHERE
					`group` <> ''
				ORDER BY
					`group`
				");
			while ($group = $db->fetch_array($groups)) $select_groups[] = array(stripslashes($group["x"]), stripslashes($group["x"]));
			
			// CSS klasser
			$select_class = array(array("", ""));
			$tmparr = explode("\n", str_replace("\r", "", module_setting("classes")));
			for ($i = 0; $i < count($tmparr); $i++)
			{
				$tmp = trim($tmparr[$i]);
				if ($tmp != "") $select_class[] = array($tmp, $tmp);
			}
			
			$frm->wysiwyg(true);
			$frm->wysiwyg_upload(true);
			$frm->wysiwyg_dir("modules/$module/upl/");
			$frm->tpl("th", $do == "add" ? "{LANG|Tilføj} ($lang_title)" : "{LANG|Rediger} ($lang_title)");
			$frm->checkbox(
				"{LANG|Publiceret}",
				"public",
				$do == "add" or $res["public"] == 1
				);
			$frm->input(
				"{LANG|Titel}",
				"title",
				stripslashes($res["title"]),
				"^.+$",
				"{LANG|Skal udfyldes}"
				);
			$frm->textarea(
				"{LANG|HTML} ($lang_title)",
				"html",
				stripslashes($res["html_" . $lang]),
				"^.+$",
				"{LANG|Skal udfyldes}"
				);
			$frm->combo(
				"{LANG|Indtast gruppe eller vælg på liste}",
				"group",
				$res["group"] != "" ? stripslashes($res["group"]) : $vars["group"],
				"",
				"",
				"",
				$select_groups
				);
			if (count($select_class) > 1)
			{
				$frm->select(
					"{LANG|CSS klasse}",
					"class",
					$res["class"],
					"",
					"",
					"",
					$select_class
					);
			}
		}
			
		if ($frm->done())
		{
			// Tjekker felter
			if (!$db->execute("
				SELECT
					html_" . $lang . "
				FROM
					" . $_table_prefix . "_module_" . $module . "_pages
				LIMIT
					1
				"))
			{
				// Tilføjer felt
				$db->execute("
					ALTER TABLE
						" . $_table_prefix . "_module_" . $module . "_pages
					ADD
						html_" . $lang . " TEXT
					");					
			}
			if (!$db->execute("
				SELECT
					keywords_" . $lang . "
				FROM
					" . $_table_prefix . "_module_" . $module . "_pages
				LIMIT
					1
				"))
			{
				// Tilføjer felt
				$db->execute("
					ALTER TABLE
						" . $_table_prefix . "_module_" . $module . "_pages
					ADD
						keywords_" . $lang . " TEXT
					");					
			}
			
			if ($do == "add")
			{
				// Tilføjet
				$db->execute("
					INSERT INTO
						" . $_table_prefix . "_module_" . $module . "_pages
					(
						title,
						html_" . $lang . ",
						public,
						`group`,
						`class`
					)
					VALUES
					(
						'" . addslashes($frm->values["title"]) . "',
						'" . addslashes($frm->values["html"]) . "',
						'" . ($frm->values["public"] != "" ? 1 : 0) . "',
						'" . trim(addslashes($frm->values["group"])) . "',
						'" . $db->escape($frm->values["class"]) . "'
					)
					");
				$id = $db->insert_id();
			}
			elseif ($do == "edit_keywords")
			{
				// Opdater søgeord
				$db->execute("
					UPDATE
						" . $_table_prefix . "_module_" . $module . "_pages
					SET
						keywords_" . $lang . " = '" . addslashes(str_replace("\n", ",", str_replace("\r", "", trim($frm->values["keywords"])))) . "'
					WHERE
						id = '$id'
					");
			}
			else
			{
				// Opdaterer
				$db->execute("
					UPDATE
						" . $_table_prefix . "_module_" . $module . "_pages
					SET
						title = '" . addslashes($frm->values["title"]) . "',
						html_" . $lang . " = '" . addslashes($frm->values["html"]) . "',
						public = '" . ($frm->values["public"] != "" ? 1 : 0) . "',
						`group` = '" . trim(addslashes($frm->values["group"])) . "',
						`class` = '" . $db->escape($frm->values["class"]) . "'
					WHERE
						id = '$id'
					");
			}
					
			// Reload
			header("Location: ./?module=$module&page=$page&do=" . ($do == "add" ? "edit" : $do) . "&id=$id&lang=$lang");
			exit;
		}
			
		$html .= $frm->html();
		
	}
	else
	{
		//
		// Oversigt
		//
		
		// Links
		$links = new links;
		$links->link("{LANG|Opret WYSIWYG-side}", "add", "&group=" . urlencode($vars["group"]));
		$html .= $links->html();

		// Grupper		
		$links = new links;		
		$links->link("{LANG|Ikke grupperede}");
		
		$groups = $db->execute("
			SELECT
				DISTINCT(`group`) AS x
			FROM
				" . $_table_prefix . "_module_" . $module . "_pages
			WHERE
				`group` <> ''
			ORDER BY
				`group`
			");
		while ($group = $db->fetch_array($groups)) $links->link(stripslashes($group["x"]), "", "&group=" . urlencode(stripslashes($group["x"])));
		
		$html .= "<table><tr><td valign=top>{LANG|Grupper}:&nbsp;&nbsp;&nbsp;</td><td>" . $links->html() . "</td></tr></table>";
		
		if ($do == "delete")
		{
			//
			// Slet
			//
			
			$msg = new message;
			$msg->type("section");
			
			$db->execute("
				DELETE FROM
					" . $_table_prefix . "_module_" . $module . "_pages
				WHERE
					id = '$id'
				");
			if ($db->affected_rows() > 0)
			{
				$msg->title("{LANG|WYSIWYG-siden er nu slettet}");
			}
			else
			{
				$msg->title("{LANG|WYSIWYG-siden blev ikke fundet}");
			}
			$html .= $msg->html();
		}
		
		$tmp = new tpl("admin_icon_active");
		$icon_active = $tmp->html();
				
		$tmp = new tpl("admin_icon_inactive");
		$icon_inactive = $tmp->html();
		
		// Henter oversigt
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_pages
			WHERE
				`group` = '" . addslashes($vars["group"]) . "'
			ORDER BY
				title
			");
		
		// Viser oversigt
		$tbl = new table;
		$tbl->th("{LANG|Gruppe}");
		$tbl->th("{LANG|Titel}");
		$tbl->th("{LANG|Publiceret}");
		$tbl->th("{LANG|Valg}", 3);
		$tbl->endrow();
		
		while ($db->fetch_array())
		{
			$tbl->td($db->array["group"] != "" ? stripslashes($db->array["group"]) : "-");
			$tbl->td(stripslashes($db->array["title"]));
			$tbl->td($db->array["public"] == 1 ? $icon_active : $icon_inactive, 1, 1, "center");
			$tbl->choise("{LANG|Ret}", "edit", $db->array["id"] . "&group=" . urlencode($vars["group"]));
			$tbl->choise("{LANG|Slet}", "delete", $db->array["id"] . "&group=" . urlencode($vars["group"]), "{LANG|Slet denne WYSIWYG-side}?");
			$tbl->choise("{LANG|Kopier}", "add", $db->array["id"] . "&group=" . urlencode($vars["group"]));
			$tbl->endrow();
		}
		
		if ($db->num_rows() == 0)
		{
			$tbl->td("{LANG|Ingen}...", 3);
			$tbl->endrow();
		}
		
		$html .= $tbl->html();
		
	}
?>