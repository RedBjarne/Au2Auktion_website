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
		Version:		13-06-2006
		Beskrivelse:	Viser side
	*/
	
	if ($id == 0)
	{
		$sql_where = "frontpage = 1 AND lang_id = '$_lang_id'";
	}
	else
	{
		$sql_where = "id = '$id'";
	}
	
	$db->execute("
		SELECT
			*
		FROM
			" . $_table_prefix . "_pages_
		WHERE
			$sql_where
			AND `public` = '1'
		LIMIT
			1
		");
	if ($res = $db->fetch_array())
	{
		// Forside
		if ($res["frontpage"] == 1 and $_SERVER["REQUEST_URI"] != "/" and substr($_SERVER["REQUEST_URI"], 0, 1) == "/" and cms_setting("frontpage_redir") == 1)
		{
			header($_SERVER["SERVER_PROTOCOL"] . " 301 Moved Permanently");
			header("Location: /");
			exit;
		}
		
		// Viderestil
		if ($res["alt_url"] != "")
		{
			header($_SERVER["SERVER_PROTOCOL"] . " 301 Moved Permanently");
			header("Location: " . $res["alt_url"]);
			exit;
		}
		
		$html .= stripslashes($res["content"]);
		
		if ($res["layout"] <> "")
		{
			if (is_file($_document_root . "/layouts/" . $_settings_["SITE_LAYOUT"] . "/html/" . $res["layout"] . ".html"))
			{
				$tpl = $res["layout"];
			}
		}
		
		if ($res["meta_title"] <> "") $site_title = stripslashes($res["meta_title"]);
		if ($res["meta_description"] <> "") $site_description = stripslashes($res["meta_description"]);
		if ($res["meta_keywords"] <> "") $site_keywords = stripslashes($res["meta_keywords"]);
		
		// Brødkrummer
		$array_breadcrumb = array();
		$array_breadcrumb[] = array(stripslashes($res["title"]), get_smart_url("/site/" . $res["lang_id"] . "////" . $res["id"]));
		
		// Sætter titler
		$_settings_["MENU_TITLE"] = stripslashes($res["title"]);
		$_settings_["MENU_TOP_TITLE"] = $_settings_["MENU_TITLE"];
		$first_parent = true;
		while ($res["sub_id"] > 0)
		{
			if ($first_parent) $_settings_["MENU_SUB_TITLE"] = $_settings_["MENU_TITLE"];
				
			$db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_pages_
				WHERE
					id = '" . $res["sub_id"] . "' AND
					id <> sub_id
				");
			if ($res = $db->fetch_array())
			{
				if ($first_parent) $_settings_["MENU_PARENT_TITLE"] = stripslashes($res["title"]);
				$_settings_["MENU_TOP_TITLE"] = stripslashes($res["title"]);
				
				// Brødkrummer
				$array_breadcrumb[] = array(stripslashes($res["title"]), get_smart_url("/site/" . $res["lang_id"] . "////" . $res["id"]));
			}
			
			$first_parent = false;
		}
		
		$breadcrumb = breadcrumb(array_reverse($array_breadcrumb));
	}
?>