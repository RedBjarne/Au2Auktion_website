<?php
	/*
		Fjerner HTML fra input
	*/
	foreach ($_GET as $key => $val) $_GET[$key] = (strip_tags($val));
	foreach ($_POST as $key => $val) $_POST[$key] = (strip_tags($val));
	
	/*COPYRIGHT*\
		COPYRIGHT STADEL.DK 2006-2009
		
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

	// Start output buffer
	ob_start();
	
	// Nødvendige filer
	require("inc/config.php");
	require("inc/functions.php");

	// Inkluderer alle class'es
	$dir = dir(realpath($_document_root . "/class/"));
	while ($file = $dir->read())
	{
		if (is_file($_document_root . "/class/" . $file))
		{
			if (ereg("\.php$", $file))
			{
				include($_document_root . "/class/" . $file);
			}
		}
	}
	
	// Database objekt
	$db = new db;
	
	// Tjekker forbindelse
	if (!$db->is_connected)
	{
		// Fejl i forbindelse til MySQL
		die("Ingen forbindelse til MySQL-server !");
	}

	// Start session - med Facebook workaround så der ikke startes session når der benyttes Facebook API
	$_cms_user_accept_cookies = false;
	if (isset($_SERVER["HTTP_X_FB_USER_REMOTE_ADDR"])
		or
		(
			cms_setting("site_user_accept_cookies") == "1" and
			(
				!isset($_COOKIE["USER_HAS_ACCEPTED_COOKIES"]) or
				$_COOKIE["USER_HAS_ACCEPTED_COOKIES"] != "TRUE"
			)
		))
	{
		// Bruger har ikke accepteret cookies
		$_cms_allow_cookies = false;
		
		// Angiv om bruger skal give sammentykke til cookies
		if (!isset($_SERVER["HTTP_X_FB_USER_REMOTE_ADDR"]) and !preg_match("/user_hide_accept_cookies=true/i", $_SERVER["REQUEST_URI"])) $_cms_user_accept_cookies = true;
		
		// Sletter evt. headers med cookies
		$arr = headers_list();
		foreach ($arr as $value)
		{
			list($key, $value) = explode(":", $value, 2);
			if (preg_match("/set-cookie/i", $key))
			{
				if (function_exists("header_remove"))
				{
					// Sletter header
					header_remove($key);
				}
				else
				{
					// Sætter tom værdi
					header($key . ":", true);
				}
			}
		}
		
		// Sletter evt. eksisterende cookies
		foreach ($_COOKIE as $key => $value) setcookie($key, "", 1, "/");
	}
	else
	{
		// Bruger har accepteret cookies, eller CMS er sat op til at bruger ikke skal give sammentykke
		$_cms_allow_cookies = true;
		session_start();
	}

	// System-beskeder
	$messages = array();
	
	// Input-variabler
	$_cms_is_smart_url = false;
	$vars = $_SERVER["REQUEST_METHOD"] == "POST" ? $_POST : $_GET;
	if ($_SERVER["REDIRECT_REQUEST_METHOD"] == "GET")
	{
		// Hvis redirect
		list($prefix, $suffix) = split("[?]", $_SERVER["REQUEST_URI"]);
		$params = split("[&]", $suffix);
		for ($i = 0; $i < count($params); $i++)
		{
			list($key, $value) = split("[=]", $params[$i]);
			$vars[$key] = urldecode($value);
		}
		if (substr($prefix, 0, 6) <> "/site/")
		{
			// Smart URL?
			$db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_smart_urls
				WHERE
					smart_url = '" . $db->escape($prefix) . "'
				");
			if ($db->fetch_array())
			{
				// Smart url
				$_cms_is_smart_url = true;
				
				// Skal vi viderestille til rigtig URL?
				if (cms_setting("smart_url_redir") == "real_url")
				{
					// Viderestil til rigtig URL
					header($_SERVER["SERVER_PROTOCOL"] . " 301 Moved Permanently");
					header("Location: " . stripslashes($db->array["real_url"]));
					exit;
				}
								
				$prefix = $db->array["real_url"];
			}
			else
			{
				// Undersøger om systemet er placeret i en undermappe - kan vi ikke lide, men lad os forsøge at redde den
				$sub_dir = str_replace(
					ereg_replace("/index\.php$", "", __FILE__), 
					"", 
					$_document_root
					);
				if ($sub_dir <> "")
				{
					$prefix = ereg_replace("^" . $sub_dir, "", $prefix);
				}
			}
		}
		if (substr($prefix, 0, 6) === "/site/")
		{
			// 200 OK
			header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
			$array = split("[/]", $prefix);
			if ($vars["lang_id"] == "") $vars["lang_id"] = $array[2];
			if ($vars["module"] == "") $vars["module"] = $array[3];
			if ($vars["page"] == "") $vars["page"] = $array[4];
			if ($vars["do"] == "") $vars["do"] = $array[5];
			if ($vars["id"] == "") $vars["id"] = $array[6];
		}
		elseif (eregi("\.[a-z]+$", $prefix))
		{
			// 404 Not found
			header($_SERVER["SERVER_PROTOCOL"] . " 404 Not found");
			$vars["module"] = "";
			$vars["page"] = "404";
			$vars["do"] = "";
			$vars["id"] = "";
		}
		else
		{
			// 200 OK
			header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
		}

		// Skal vi viderestille til smart URL?
		if (!$_cms_is_smart_url and cms_setting("smart_url_redir") == "smart_url" and $suffix == "")
		{
			$tmp_url = stripslashes($db->execute_field("
				SELECT
					smart_url
				FROM
					" . $_table_prefix . "_smart_urls
				WHERE
					real_url = '" . $db->escape($prefix) . "' OR
					real_url = '" . $db->escape(preg_replace("/^\/site\/[a-z]*\//", "/site//", $prefix)) . "'
				"));
			if ($tmp_url != "")
			{
				// Viderestil til smart URL
				header($_SERVER["SERVER_PROTOCOL"] . " 301 Moved Permanently");
				header("Location: " . $tmp_url);
				exit;
			}
		}
		elseif (cms_setting("smart_url_redir") == "param")
		{
			// Viderestil til param URL
			$url = "";
			foreach ($vars as $key => $val)
			{
				if ($key != "" and $val != "")
				{
					if ($url != "")
					{
						$url .= "&";
					}
					else
					{
						$url .= "/?";
					}
					$url .= $key . "=" . urlencode($val);
				}
			}
			if ($url != "")
			{
				header($_SERVER["SERVER_PROTOCOL"] . " 301 Moved Permanently");
				header("Location: $url");
				exit;
			}
		}
	}
	elseif ($_SERVER["REQUEST_METHOD"] == "POST" and count($_GET) > 0)
	{
		// Hent GET parametre ind også
 		reset($_GET);
 		while (list($key, $value) = each($_GET)) if (!isset($vars[$key])) $vars[$key] = $value;
	}
	elseif ($_SERVER["REQUEST_METHOD"] == "GET")
	{
		if (cms_setting("smart_url_redir") == "smart_url")
		{
			// Viderestil til smart-url
			
			// Undersøger om url'en indeholder ekstra variabler
			$tmpvars = $vars;
			unset($tmpvars["module"]);
			unset($tmpvars["page"]);
			unset($tmpvars["do"]);
			unset($tmpvars["id"]);
			unset($tmpvars["lang_id"]);
			
			// Undersøger sprog
			if (isset($vars["lang_id"]))
			{
				$tmplangid = $vars["lang_id"];
			}
			else
			{
				$tmplangid = $_SESSION["_language_id"];
			}
			if (!preg_match("/^[a-z]{2}$/", $tmplangid)) $tmplangid = "";
			
			if (count($tmpvars) == 0)
			{			
				$tmp_url = stripslashes($db->execute_field("
					SELECT
						smart_url
					FROM
						" . $_table_prefix . "_smart_urls
					WHERE
						real_url LIKE '/site/%/" . $db->escape($vars["module"] . "/" . $vars["page"] . "/" . $vars["do"] . "/" . $vars["id"]) . "'
					ORDER BY
						IF(real_url LIKE '/site/$tmplangid/%', 0, 1)
					LIMIT
						0, 1
					"));
				if ($tmp_url != "")
				{
					// Viderestil til smart URL
					header($_SERVER["SERVER_PROTOCOL"] . " 301 Moved Permanently");
					header("Location: " . $tmp_url);
					exit;
				}
			}
		}
		elseif (cms_setting("smart_url_redir") == "real_url")
		{
			// Viderestil til /site/
			$url = "/site/" . $vars["lang_id"] . "/" . $vars["module"] . "/" . $vars["page"] . "/" . $vars["do"] . "/" . $vars["id"];
			unset($vars["module"]);
			unset($vars["page"]);
			unset($vars["do"]);
			unset($vars["id"]);
			unset($vars["lang_id"]);
			foreach ($vars as $key => $val)
			{
				if (strpos($url, "?") === false)
				{
					$url .= "?";
				}
				else
				{
					$url .= "&";
				}
				$url .= $key . "=" . urlencode($val);
			}
			header("Location: $url");
			exit;
		}
	}
	
	$module = $vars["module"];
	$page = $vars["page"];
	$do = $vars["do"];
	$id = $vars["id"];
	
	// Tjekker modul og side
	if (!ereg("^[A-Za-z_0-9-]+$", $module))		$module = "";
	if (!ereg("^[A-Za-z_0-9-]+$", $page))		$page = "default";
	if (!ereg("^[A-Za-z_0-9-]+$", $do))			$do = "";
	if (!ereg("^[0-9]+$", $id))					$id = "";
	if ($module <> "" and !is_file($_document_root . "/modules/" . $module . "/pages/" . $page . ".php")) $module = "";
	if ($module <> "")
	{
		$include_file = $_document_root . "/modules/" . $module . "/pages/" . $page . ".php";
	}
	else
	{
		if (!is_file("pages/" . $page . ".php"))
		{
			$page = "default";
		}
		$include_file = "pages/" . $page . ".php";
	}
	
	// Loader indstillinger
	$set = new settings;
	
	// Henter indstillinger for domæne
	$db->execute("
		SELECT
			*
		FROM
			" . $_table_prefix . "_domains_
		WHERE
			domain = '" . $db->escape($_SERVER["HTTP_HOST"]) . "' OR
			'" . $db->escape($_SERVER["HTTP_HOST"]) . "' LIKE domain
		ORDER BY
			IF(domain = '" . $db->escape($_SERVER["HTTP_HOST"]) . "', 0, 1)
		LIMIT
			0, 1
		");
	if ($db->fetch_array())
	{
		// Viderestil?
		if ($db->array["redirect"] != "")
		{
			header($_SERVER["SERVER_PROTOCOL"] . " 301 Moved Permanently");
			header("Location: " . str_replace("{URI}", $_SERVER["REQUEST_URI"], $db->array["redirect"]));
			exit;
		}

		reset($db->array);
		while (list($key, $value) = each($db->array))
		{
			if (is_string($key))
			{
				if ($key == "lang_id")
				{
					if ($vars["lang_id"] == "") $vars["lang_id"] = $value;
				}
				elseif ($key == "layout")
				{
					if ($value != "") $_settings_["SITE_LAYOUT"] = $value;
				}
				elseif (preg_match("/^(site|email|return)_.+$/", $key))
				{
					if ($value != "") $_settings_[strtoupper($key)] = $value;
				}
			}
		}

		// Bruger variabler
		$user_settings = split("[\n]", str_replace("\r", "", stripslashes($db->array["user_settings"])));
		for ($i = 0; $i < count($user_settings); $i++)
		{
			list($key, $value) = split("[=]", $user_settings[$i]);
			$_settings_["USER_" . strtoupper($key)] = $value;
		}
	}
	
	// HTML og layout for denne side
	$site_title = "";
	$site_description = "";
	$site_keywords = "";
	$breadcrumb = "";
	$html = "";
	$tpl = "default";

	// Hvis modul skal der muligvis anvendes et andet layout
	if ($module <> "")
	{
		if ($_settings_["MODULE_LAYOUT"] <> "")
		{
			$module_layout = unserialize($_settings_["MODULE_LAYOUT"]);
			if ($module_layout[$module] <> "") $tpl = $module_layout[$module];
		}
	}
	
	// Er der angivet et layout i parameter?
	if (ereg("^[a-zA-Z0-9_-]+$", $vars["tpl"]))
	{
		if (is_file($_document_root . "/layouts/" . $_settings_["SITE_LAYOUT"] . "/html/" . $vars["tpl"] . ".html"))
		{
			$tpl = $vars["tpl"];
		}
	}
	
	// Loader sprog
	change_language($vars["lang_id"]);
	
	// Henter alle installerede moduler
	$array_module_installed = admin_module_installed();
	
	// Inkluderer header-filer fra moduler
	for ($int_module_installed = 0; $int_module_installed < count($array_module_installed); $int_module_installed++)
	{
		if (is_file($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/pages/header.php"))
		{
			$tmp_old_module = $module;
			$tmp_old_page = $page;
			$tmp_old_do = $do;
			$tmp_old_id = $id;
			$module = $array_module_installed[$int_module_installed];
			$page = "";
			$do = "";
			$id = 0;
			include($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/pages/header.php");
			$module = $tmp_old_module;
			$page = $tmp_old_page;
			$do = $tmp_old_do;
			$id = $tmp_old_id;
		}
	}
		
	// Inkluderer fil
	require($include_file);
	
	// Inkluderer footer-filer fra moduler
	for ($int_module_installed = 0; $int_module_installed < count($array_module_installed); $int_module_installed++)
	{
		if (is_file($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/pages/footer.php"))
		{
			$tmp_old_module = $module;
			$tmp_old_page = $page;
			$tmp_old_do = $do;
			$tmp_old_id = $id;
			$module = $array_module_installed[$int_module_installed];
			$page = "";
			$do = "";
			$id = 0;
			include($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/pages/footer.php");
			$module = $tmp_old_module;
			$page = $tmp_old_page;
			$do = $tmp_old_do;
			$id = $tmp_old_id;
		}
	}

	// Tjekker om der er angivet titel og beskrivelse
	if ($site_title <> "") $_settings_["SITE_TITLE"] = $site_title;
	if ($site_description <> "") $_settings_["SITE_DESCRIPTION"] = $site_description;
	if ($site_keywords <> "") $_settings_["SITE_KEYWORDS"] = $site_keywords;
	if ($site_title <> "" or $site_description <> "" or $site_keywords <> "")
	{
		// Sikrer at template klasse genindlæser settings
		global $_tpl_settings_count;
		$_tpl_settings_count = 0;
	}
	
	// Fjerner ugyldige tegn i sidetitel mm
	$_settings_["SITE_TITLE"] = str_replace("\"", "", $_settings_["SITE_TITLE"]);
	$_settings_["SITE_TITLE"] = str_replace("'", "", $_settings_["SITE_TITLE"]);
	$_settings_["SITE_TITLE"] = str_replace("\r", "", $_settings_["SITE_TITLE"]);
	$_settings_["SITE_TITLE"] = str_replace("\n", " ", $_settings_["SITE_TITLE"]);
	$_settings_["SITE_TITLE"] = strip_tags($_settings_["SITE_TITLE"]);
	$_settings_["SITE_DESCRIPTION"] = str_replace("\"", "", $_settings_["SITE_DESCRIPTION"]);
	$_settings_["SITE_DESCRIPTION"] = str_replace("'", "", $_settings_["SITE_DESCRIPTION"]);
	$_settings_["SITE_DESCRIPTION"] = str_replace("\r", "", $_settings_["SITE_DESCRIPTION"]);
	$_settings_["SITE_DESCRIPTION"] = str_replace("\n", " ", $_settings_["SITE_DESCRIPTION"]);
	$_settings_["SITE_DESCRIPTION"] = strip_tags($_settings_["SITE_DESCRIPTION"]);
	$_settings_["SITE_KEYWORDS"] = str_replace("\"", "", $_settings_["SITE_KEYWORDS"]);
	$_settings_["SITE_KEYWORDS"] = str_replace("'", "", $_settings_["SITE_KEYWORDS"]);
	$_settings_["SITE_KEYWORDS"] = str_replace("\r", "", $_settings_["SITE_KEYWORDS"]);
	$_settings_["SITE_KEYWORDS"] = str_replace("\n", " ", $_settings_["SITE_KEYWORDS"]);
	$_settings_["SITE_KEYWORDS"] = strip_tags($_settings_["SITE_KEYWORDS"]);

	// Header cache
	if (intval($vars["cache"]) > 0)
	{
		header("Expires: " . date("D, j M Y H:i:s", time() + (86400 * intval($vars["cache"]))) . " CEST");
		header("Cache-Control: Public");
		header("Pragma: Public");
	}

	// Viser side
	$version = "?.?";
	include($_document_root . "/version.php");
	header("X-Powered-By: Stadel.dk CMS version $version (www.stadel.dk)");
	$tpl = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|$tpl");
	$tpl->set("html", $html);
	$tpl->set("breadcrumb", $breadcrumb);
	$html = $tpl->html() . "<!-- Stadel.dk CMS version $version (http://stadel.dk/) -->";

	// Skal bruges give sammentykke til cookies
	if ($_cms_user_accept_cookies)
	{
		$url = stripslashes($_settings_["site_url_policy"]);
		if ($url != "")
		{
			if (strpos($url, "?") > 0)
			{
				$url .= "&user_hide_accept_cookies=true";
			}
			else
			{
				$url .= "?user_hide_accept_cookies=true";
			}
		}
		$tmp = new tpl("_user_accept_cookies");
		$tmp->set("url", $url);
		$_settings_["SITE_HEAD"] .= $tmp->html();
		
		// Sletter evt. headers med cookies
		$arr = headers_list();
		foreach ($arr as $value)
		{
			list($key, $value) = explode(":", $value, 2);
			if (preg_match("/set-cookie/i", $key))
			{
				if (function_exists("header_remove"))
				{
					// Sletter header
					header_remove($key);
				}
				else
				{
					// Sætter tom værdi
					header($key . ":", true);
				}
			}
		}
		
		// Sletter evt. eksisterende cookies
		foreach ($_COOKIE as $key => $value) setcookie($key, "", 1, "/");
	}
	
	// Undersøg om css.php er refereret i <head>
	if (strpos($html, $_site_url . "/css.php") === false) $_settings_["SITE_HEAD"] .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$_site_url/css.php\" />\r\n";
	
	// Undersøg om vi ikke tillader indeksering af denne side
	$allow_index = false;
	if (cms_setting("index_only_smart_urls") == 1)
	{
		// Indekser kun smart-urls
		if ($_cms_is_smart_url) $allow_index = true;
	}
	elseif ($vars["module"] != "")
	{
		// Moduler der må indekseres
		$index_modules = cms_setting("index_modules");
		if ($index_modules == "")
		{
			// Alt må indekseres
			$allow_index = true;
		}
		else
		{
			// Kun specifikke moduler må indekseres
			$index_modules = explode("|", $index_modules);
			if (in_array($vars["module"], $index_modules)) $allow_index = true;
		}
	}
	else
	{
		// Menu må godt indekseres
		$allow_index = true;
	}
	if (!$allow_index) $_settings_["SITE_HEAD"] .= "<meta name=\"robots\" content=\"noindex,follow\">";
	
	// Tilføj til <head>
	if ($_settings_["SITE_HEAD"] != "")
	{
		$pos = strpos($html, "</head>");
		$html = substr($html, 0, $pos) . $_settings_["SITE_HEAD"] . "\r\n" . substr($html, $pos);
	}
	
	// AJAX format?
	if ($vars["format"] == "ajax")
	{
		$a = new ajax;
		$a->response(array(
			"state" => "ok",
			"html" => $html
			));
	}
	
	// Omskriver links
	$url_rewrite = cms_setting("url_rewrite");
	if ($url_rewrite != "")
	{
		// Finder alle links
		$newhtml = "";
		while (preg_match("/href=((\"|'){0,1}([^>^ ^\"^']+)(\"|'){0,1})/i", $html, $array))
		{
			$fullstr = $array[0];
			$encap = $array[2];
			$url = $array[3];
			
			if (preg_match("/\.[a-z]+$/i", $array[3]) == 0 and preg_match("/^(mailto|javascript)\:/i", $array[3]) == 0)
			{
				// Finder URL
				$rewrite_done = false;
	
				// Parse URL			
				$parse_url = parse_url($url);
				if (!$parse_url["path"] or $parse_url["path"] == "") $parse_url["path"] = "/";
				if (!isset($parse_url["host"]) or $parse_url["host"] == $_SERVER["HTTP_HOST"] or preg_replace("/^www\./", "", $parse_url["host"]) == preg_replace("/^www\./", "", $_SERVER["HTTP_HOST"]))
				{
					// Eget domæne

					// Smart-url?
					if ($tmpurl = $db->execute_field("
						SELECT
							real_url
						FROM
							" . $_table_prefix . "_smart_urls
						WHERE
							smart_url = '" . $db->escape($url) . "'
						"))
					{
						// Smart-url
						if ($url_rewrite == "smart_url")
						{
							// Smart-url -> smart-url
							$rewrite_done = true;
						}
						else
						{
							$url = $tmpurl;
							$parse_url = parse_url($url);
							if (!$parse_url["path"] or $parse_url["path"] == "") $parse_url["path"] = "/";
						}
					}

					// Undersøger om det er /site/da
					if (!$rewrite_done)
					{
						if (preg_match("/\/site\/([^\/]*)(\/([^\/]*)(\/([^\/]*)(\/([^\/]*)(\/([^\/]*)){0,1}){0,1}){0,1}){0,1}/", $parse_url["path"], $arr))
						{
							// /site/
							if ($url_rewrite == "site")
							{			
								// /site/ -> /site/	
								$rewrite_done = true;
							}
							else
							{	
								if ($url_rewrite == "smart_url")
								{
									// /site/ -> smart-url
									$url = get_smart_url($parse_url["path"]);
									$rewrite_done = true;
								}
								elseif ($url_rewrite == "param")
								{
									// Parse querystring
									parse_str(html_entity_decode($parse_url["query"]), $param);
									
									if ($arr[1] != "") $param["lang_id"] = $arr[1];
									if ($arr[3] != "") $param["module"] = $arr[3];
									if ($arr[5] != "") $param["page"] = $arr[5];
									if ($arr[7] != "") $param["do"] = $arr[7];
									if ($arr[9] != "") $param["id"] = $arr[9];
		
									$url = "";
									foreach ($param as $key => $val)
									{
										if ($key != "" and $val != "")
										{
											if ($url != "")
											{
												$url .= "&";
											}
											else
											{
												$url .= "/?";
											}
											$url .= $key . "=" . urlencode($val);
										}
									}
									$rewrite_done = true;
								}
							}
						}
						else
						{
							// /?module=xxx
							if ($url_rewrite == "param")
							{
								// /?module=xxx -> /?module=xxx
								$rewrite_done = true;
							}
							else
							{
								// Parse querystring
								parse_str(html_entity_decode($parse_url["query"]), $param);
								
								$url = "/site/" . (isset($param["lang_id"]) ? $param["lang_id"] : $_lang_id) . "/" . $param["module"] . "/" . $param["page"] . "/" . $param["do"] . "/" . $param["id"];
								unset($param["lang_id"]);
								unset($param["module"]);
								unset($param["page"]);
								unset($param["do"]);
								unset($param["id"]);
								
								ksort($param);
								foreach ($param as $key => $val)
								{
									if ($key != "" and $val != "")
									{
										if ($url != "")
										{
											$url .= "&";
										}
										else
										{
											$url .= "/?";
										}
										$url .= $key . "=" . urlencode($val);
									}
								}
								
								if ($url_rewrite == "smart_url")
								{
									// /?module=xxx -> smart-url
									$url = get_smart_url($url);
									$rewrite_done = true;
								}
								elseif ($url_rewrite == "site")
								{
									// /?module=xxx -> /site/
									$rewrite_done = true;
								}
							}
						}
					}
				}
			}
			
			// Erstatter i HTML
			$pos = strpos($html, $fullstr);
			$newhtml .= substr($html, 0, $pos) . "href=" . $encap . $url . $encap;
			$html = substr($html, $pos + strlen($fullstr));
		}
		$html = $newhtml . $html;
	}
	
	// Alm visning
	echo($html);
?>