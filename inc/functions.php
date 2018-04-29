<?php
	/*COPYRIGHT*\
		COPYRIGHT STADEL.DK 2006-2013
		
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

	// Printer fil
	function print_file($filename)
	{
		if (!is_file($filename))
		{
			add_log_message("Could not print: $filename does not exist");
			return false;
		}
		if (!is_readable($filename))
		{
			add_log_message("Could not print: $filename is not readable");
			return false;
		}
		if (cms_setting("printer_method") == "email")
		{
			// Print til e-mail
			if (cms_setting("printer_email") == "")
			{
				add_log_message("Could not print: missing printer e-mail address");
				return false;
			}
			$e = new email;
			$e->to(cms_setting("printer_email"));
			$e->subject("Print: " . basename($filename));
			$e->plain();
			$e->body("");
			$e->attach($filename);
			if (!$e->send())
			{
				add_log_message("Could not print: could not send e-mail to " . cms_setting("printer_email"));
				return false;
			}
			return true;
		}
		elseif (cms_setting("printer_method") == "lpr")
		{
			// Print via Linux LPR
			exec("lpr '$filename'");
			return true;
		}
		else
		{
			// Print ingen indstillinger
			add_log_message("Could not print: missing settings");
			return false;
		}
	}
	
	// Gemmer fil via FTP
	function ftp_put_contents($filename, $contents)
	{
		global $_cms_ftp_server, $_cms_ftp_username, $_cms_ftp_password, $_cms_ftp_root, $_tmp_dir, $_document_root;
		
		// FTP
		$ftp = new ftp;
		
		// Forbinder
		$ftp->connect($_cms_ftp_server) or die("Kunne ikke forbinde til FTP-server (" . $_cms_ftp_server . ")");
		
		// Logger ind
		$ftp->login($_cms_ftp_username, $_cms_ftp_password) or die("Kunne ikke logge på FTP-server (" . $_cms_ftp_username . ")");
		
		// Skifter til rod-mappe
		if ($_cms_ftp_root <> "")
		{
			$ftp->chdir($_cms_ftp_root) or die("Kunne ikke skifte til rod-mappe (" . $_cms_ftp_root . ")");
		}
		
		// Tjekker om mapper findes
		$folders = explode("/", $filename);
		if (count($folders) > 1)
		{
			$folder = "";
			for ($i = 0; $i < count($folders) - 1; $i++)
			{
				if ($folder != "") $folder .= "/";
				$folder .= $folders[$i];
				if (!is_dir($_document_root . "/" . $folder))
				{
					if (!$ftp->mkdir($folder))
					{
						add_log_message("ftp_put_contents: could not create folder ($folder)");
						$ftp->close();
						return false;
					}
				}
			}
		}
		
		// Sletter evt. gammel fil
		if (is_file($_document_root . "/" . $filename))
		{
			if (!$ftp->delete($filename))
			{
				add_log_message("ftp_put_contents: could not delete existing file");
				$ftp->close();
				return false;
			}
		}
		
		// Opretter midlertidig fil
		$tmpfile = $_tmp_dir . "." . uniqid(time());
		if (!file_put_contents($tmpfile, $contents))
		{
			add_log_message("ftp_put_contents: could not create temporary file ($tmpfile)");
			$ftp->close();
			return false;
		}
		
		// Uploader ny fil
		if (!$ftp->upload($tmpfile, $filename))
		{
			add_log_message("ftp_put_contents: could not upload file");
			$ftp->close();
			unlink($tmpfile);
			return false;
		}
		
		// Afbryder
		$ftp->close();
		
		// Sletter midlertidig fil
		unlink($tmpfile);
		
		// Ok
		return true;
	}
	
	// Sletter fil via FTP
	function ftp_delete_file($filename)
	{
		return false;
	}
	
	// Opdaterer sprog-fil for CMS eller modul
	function update_lang_file($tmpmodule = false)
	{
		global $_document_root;
		$file = new file;
		$files = array();
		if (!$tmpmodule)
		{
			// CMS
			$lang_file = "lang/lang.php";
			$folders = $file->find_folders($_document_root);
			for ($i = 0; $i < count($folders); $i++)
			{
				if (preg_match("/^(modules)$/", $folders[$i]) == 0)
				{
					$tmpfiles = $file->find_files($_document_root . "/" . $folders[$i], true);
					for ($j = 0; $j < count($tmpfiles); $j++)
					{
						if (preg_match("/\.(php|html)$/", $tmpfiles[$j])) $files[] = $_document_root . "/" . $folders[$i] . "/" . $tmpfiles[$j];
					}
				}
			}
		}
		else
		{
			// Modul
			$lang_file = "modules/$tmpmodule/lang/lang.php";
			$tmpfiles = $file->find_files($_document_root . "/modules/$tmpmodule/", true);
			for ($j = 0; $j < count($tmpfiles); $j++)
			{
				if (preg_match("/\.(php|html)$/", $tmpfiles[$j])) $files[] = $_document_root . "/modules/$tmpmodule/" . $tmpfiles[$j];
			}
		}
		
		// Genererer sprog-fil
		$array = array();
		for ($i = 0; $i < count($files); $i++)
		{
			$contents = file_get_contents($files[$i]);
			while (preg_match("/\{LANG\|([^\r^\n^\}^\{^\"]+)\}/", $contents, $array1))
			{
				// Fjerner fra contents
				$contents = str_replace($array1[0], "", $contents);
				if (!in_array($array1[1], $array)) $array[] = $array1[1];
			}
		}
		sort($array);
		
		$php = '';
		for ($i = 0; $i < count($array); $i++)
		{
			// Tilføjer til opdatering
			if ($php != "") $php .= ',';
			$php .= '"' . $array[$i] . '"';
		}
		$php = '<?php $lang = array(' . $php . '); ?>';

		// Gemmer
		return file_put_contents($_document_root . "/" . $lang_file, $php);			
	}
	
	// Formatterer dato med convert klasse
	function formatdate($date)
	{
		$cnv = new convert;
		return $cnv->formatdate($date);
	}
	
	// Gemmer eller returnerer indstilling for cms
	function cms_setting($id, $value = -1)
	{
		$set = new settings;
		
		if ($value != -1)
		{
			// Gemmer værdi
			$set->set($id, $value);
		}
		
		// Henter værdi
		return $set->get($id);
	}
	
	// Opretter brødkrummer
	function breadcrumb($array)
	{
		global $_settings_;
		$breadcrumb = "";
		for ($i = 0; $i < count($array); $i++)
		{
			list($title, $url) = $array[$i];
			if ($breadcrumb != "")
			{
				$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|breadcrumb_separator");
				$breadcrumb .= $tmp->html();
			}
			if ($i == count($array) - 1)
			{
				$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|breadcrumb_active");
			}
			else
			{
				$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|breadcrumb_element");
			}
			$tmp->set("title", $title);
			$tmp->set("url", get_smart_url($url));
			$breadcrumb .= $tmp->html();
		}
		return $breadcrumb;
	}

	// Returnerer array med bounced mail
	function get_return_mail($page = "", $do = "", $id = "", $delete = true)
	{
		global $_table_prefix, $module;
		
		$db = new db;
		
		$bounced = array();
		
		$sql = "";
		if ($page != "") $sql .= " AND `page` = '" . $db->escape($page) . "' ";
		if ($do != "") $sql .= " AND `do` = '" . $db->escape($do) . "' ";
		if ($id != "") $sql .= " AND `id` = '" . $db->escape($id) . "' ";
		
		$ress = $db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_return_mail
			WHERE
				module = '" . $db->escape($module) . "'
				$sql
			LIMIT
				100
			");
			
		while ($res = $db->fetch_array($ress))
		{
			$bounced[count($bounced)] = array(
				"page" => stripslashes($res["page"]),
				"do" => stripslashes($res["do"]),
				"id" => stripslashes($res["id"]),
				"message_id" => stripslashes($res["message_id"]),
				"email" => stripslashes($res["email"])
				);
		}
		
		if ($delete)
		{
			$db->execute("
				DELETE FROM
					" . $_table_prefix . "_return_mail
				WHERE
					module = '" . $db->escape($module) . "'
					$sql
				LIMIT
					100
				");
		}
				
		return $bounced;
	}
	
	// Sender SMS - mobile kan være et enkelt mobilnr eller array
	function send_sms($mobile, $message, $sender = "")
	{
		if (!is_array($mobile)) $mobile = array($mobile);
		$sms = new sms;
		$sms->sms_mobile = $mobile;
		$sms->message($message);
		$sms->sender($sender);
		return $sms->send();
	}
	
	// Gemmer log besked
	function add_log_message($message)
	{
		global $_table_prefix;
		if (trim($message) == "") return false;
		$db = new db;
		$db->disable_log(true);
		$db->execute("
			INSERT INTO
				" . $_table_prefix . "_log_messages
			(
				`time`,
				`message`
			)
			VALUES
			(
				NOW(),
				'" . $db->escape($message) . "'
			)
			");
		$id = $db->insert_id();
		$db->execute("
			DELETE FROM
				" . $_table_prefix . "_log_messages
			WHERE
				id <= '" . ($id - 100) . "'
			");
		return true;
	}
	
	// Laver smart url
	function create_smart_url($title, $real_url)
	{
		// Er real_url ok?
		if (!ereg("/site/", $real_url)) return $real_url;
		
		// Global
		global $_table_prefix, $_document_root;
		
		// DB objekt
		$db = new db;
		
		// Findes den i forvejen?
		$smart_url = $db->execute_field("
			SELECT
				smart_url
			FROM
				" . $_table_prefix . "_smart_urls
			WHERE
				real_url = '" . $db->escape($real_url) . "'
			");
		if ($smart_url <> "") return $smart_url;
		
		// Tegn der skal erstattes
		$array_replace = array(
			"//" => "/",
			"__" => "_",
			"--" => "-",
			"æ" => "ae",
			"ø" => "oe",
			"å" => "aa",
			"Æ" => "Ae",
			"Ø" => "Oe",
			"Å" => "Aa"
			);
			
		// Fjerner evt. HTML-tags fra URL
		$title = strip_tags($title);
				
		// Tilføjer url
		$base_url = "/" . $title;
		reset($array_replace);
		while (list($from, $to) = each($array_replace))
		{
			$base_url = str_replace($from, $to, $base_url);
		}
		$base_url = eregi_replace("[^a-z^/^_^0-9^-]", "", str_replace(" ", "_", $base_url));
		$smart_url = $base_url;
		$count = 0;
		while ($smart_url == "/site" or is_file($_document_root . $smart_url) or is_dir($_document_root . $smart_url))
		{
			$count++;
			$smart_url = $base_url . "_" . $count;
		}
		$table_exists = false;
		while (!$db->execute("
			INSERT INTO
				" . $_table_prefix . "_smart_urls
			(
				real_url,
				smart_url
			)
			VALUES
			(
				'" . $db->escape($real_url) . "',
				'" . $db->escape($smart_url) . "'
			)
			"))
		{
			if (!$table_exists)
			{
				// Tjekker om tabellen findes
				if (!$db->execute("SELECT real_url FROM " . $_table_prefix . "_smart_urls LIMIT 1"))
				{
					// Retur
					return $real_url;
				}
				$table_exists = true;
			}
			$count++;
			$smart_url = $base_url . "_" . $count;
		}
		
		// Retur
		return $smart_url;
	}
	
	// Henter smart url
	function get_smart_url($real_url)
	{
		// Global
		global $_table_prefix, $_document_root, $_site_url;
		
		// Er real_url ok?
		$real_url = str_replace($_site_url, "", $real_url);
		if (preg_match("/^\/site/", $real_url) == 0) return $real_url;

		// DB objekt
		$db = new db;
		
		// Findes den i forvejen?
		$smart_url = $db->execute_field("
			SELECT
				smart_url
			FROM
				" . $_table_prefix . "_smart_urls
			WHERE
				real_url = '" . $db->escape($real_url) . "' OR
				real_url = '" . $db->escape(preg_replace("/^\/site\/[a-z]*\//", "/site//", $real_url)) . "'
			");
		if ($smart_url <> "") return $smart_url;
		
		// Retur
		return $real_url;
	}
	
	// Sletter smart url
	function delete_smart_url($real_url)
	{
		// Global
		global $_table_prefix;
		
		// DB objekt
		$db = new db;

		// Sletter
		$db->execute("
			DELETE FROM
				" . $_table_prefix . "_smart_urls
			WHERE
				real_url = '" . $db->escape($real_url) . "'
			");		
	}
	
	// Skifter sprog
	function change_language($new_lang_id = false)
	{
		// Global
		global $db, $_table_prefix, $_lang_, $_lang_id, $_document_root;
		
		// Tjekker new_lang_id
		if (!ereg("^[a-z]{2}$", $new_lang_id)) $new_lang_id = $_SESSION["_language_id"];
		
		// Henter sprog
		$_lang_id = $db->execute_field("
			SELECT
				id
			FROM
				" . $_table_prefix . "_languages_
			WHERE
				id = '" . $db->escape($new_lang_id) . "' OR
				'" . $db->escape($_SERVER["HTTP_ACCEPT_LANGUAGE"]) . "' LIKE CONCAT(id, '%') OR
				`default` = '1'
			ORDER BY
				IF(id = '" . $db->escape($new_lang_id) . "', 0, 1),
				IF('" . $db->escape($_SERVER["HTTP_ACCEPT_LANGUAGE"]) . "' LIKE CONCAT(id, '%'), 0, 1)
			");
		$_SESSION["_language_id"] = $_lang_id;
		
		// Tømmer _lang_ variabel
		$_lang_ = array();
		
		// Inkluderer grundsystem sprog-fil
		if (is_file($_document_root . "/lang/" . $_lang_id . ".php"))
		{
			$lang = array();
			include($_document_root . "/lang/" . $_lang_id . ".php");
			$_lang_[""] = $lang;
		}

		// Henter alle installerede moduler
		$array_module_installed = admin_module_installed();
		for ($int_module_installed = 0; $int_module_installed < count($array_module_installed); $int_module_installed++)
		{
			if (is_file($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/lang/" . $_lang_id . ".php"))
			{
				$lang = array();
				include($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/lang/" . $_lang_id . ".php");
				$_lang_[$array_module_installed[$int_module_installed]] = $lang;
			}
		}
	}
		
	
	// Returnerer alle sprog
	function languages_array()
	{
		// Global
		global $db, $_table_prefix;
		// Henter sprog
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_languages_
			ORDER BY
				`default` DESC,
				title
			");
		$array = array();
		while ($db->fetch_array())
		{
			$array[$db->array["id"]] = stripslashes($db->array["title"]);
		}			
		return $array;
	}
	
	// Funktion der viser menu fra modul
	function module_pages_show($sub_id = false, $moduledo = false, $sitemenu = false)
	{
		global $_document_root, $_table_prefix, $_lang_id, $module, $page, $do, $id, $vars, $_settings_;
		
		if ($moduledo)
		{
			// Indlæser data fra modul
			list($tmpmodule, $tmpdo) = explode("|", $moduledo);
			$sitemenu = array();
			if (has_module($tmpmodule))
			{
				if (is_file($_document_root . "/modules/$tmpmodule/pages/sitemenu.php"))
				{
					// Database
					$db = new db;
					
					$tmpmodule2 = $module;
					$tmppage2 = $page;
					$tmpdo2 = $do;
					$tmpid2 = $id;
					$module = $tmpmodule;
					$page = "";
					$do = $tmpdo;
					$id = 0;
					@include($_document_root . "/modules/$tmpmodule/pages/sitemenu.php");
					$module = $tmpmodule2;
					$page = $tmppage2;
					$do = $tmpdo2;
					$id = $tmpid2;
				}
			}
		 }
		 
		 $menu = "";
		 if (is_array($sitemenu))
		 {
			// Skal submenuer indsættes i html eller tilføjes efterfølgende
			$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|menu_sub");
			$tmp->set("sub_menu", "ApPeNdTeSt");
			$append_subs = (strpos($tmp->html(), "ApPeNdTeSt") === false);
			
			for ($i = 0; $i < count($sitemenu); $i++)
			{
				$sub_menu = "";
				$sub_append = "";
				if ($append_subs)
				{
					$sub_append = module_pages_show($sub_id . "_" . $i, false, $sitemenu[$i]["sub_menu"]);
				}
				else
				{
					$sub_menu = module_pages_show($sub_id . "_" . $i, false, $sitemenu[$i]["sub_menu"]);
				}
				
				if ($sub_menu == "" and is_file($_document_root . "/layouts/" . $_settings_["SITE_LAYOUT"] . "/html/menu_sub_last_level.html"))
				{
					$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|menu_sub_last_level");
				}
				else
				{
					$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|menu_sub");
				}
				$tmp->set("id", $sub_id . "_" . $i);
				$tmp->set("smart_url", $sitemenu[$i]["url"]);
				$tmp->set("title", $sitemenu[$i]["title"]);
				$tmp->set("sub_title", $sitemenu[$i]["sub_title"]);
				$tmp->set("sub_id", $sub_id);
				$tmp->set("sub_menu", $sub_menu);
				$menu .= $tmp->html() . $sub_append;
			}
		 }
		 
		 return $menu;
	}
	
	// Funktion der viser menu på siden	
	function pages_show($sub_id = 0, $show_sub = true)
	{
		global $_table_prefix, $_settings_, $vars, $_lang_id, $_document_root;
		
		$db = new db;
		$menu = "";
		
		// Finder aktuel side
		if ($vars["module"] == "" and ($vars["page"] == "default" or $vars["page"] == "")) 
		{
			$db->execute("
				SELECT
					id,
					sub_id
				FROM
					" . $_table_prefix . "_pages_
				WHERE
					id = '" . intval($vars["id"]) . "' OR
					frontpage = '1'
				ORDER BY
					IF(id = '" . intval($vars["id"]) . "', 0, 1)
				");
			if ($db->fetch_array())
			{
				if ($db->array["sub_id"] > 0)
				{
					$active_top_menu_id = $db->array["sub_id"];
				}
				else
				{
					$active_top_menu_id = $db->array["id"];
				}
				$active_menu_id = $db->array["id"];
			}
			else
			{
				$active_top_menu_id = -1;
				$active_menu_id = -1;
			}
		}
		else
		{
			$active_top_menu_id = -1;
			$active_menu_id = -1;
		}		
		
		// Viser evt. parent menu
		if ($sub_id > 0)
		{
			$db->execute("
				SELECT
					title,
					sub_menu
				FROM
					" . $_table_prefix . "_pages_
				WHERE
					id = '$sub_id'
				");
			if ($db->fetch_array())
			{			
				$title_prefix = stripslashes($db->array["title"]) . "/";
				$module_sub_menu = $db->array["sub_menu"];
			}
			else
			{
				$title_prefix = "";
				$module_sub_menu = "";
			}
		}
		else
		{
			$title_prefix = "";
			$module_sub_menu = "";
		}
		
		// Finder menuer
		$ress = $db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_pages_
			WHERE
				sub_id = '" . intval($sub_id) . "' AND
				lang_id = '$_lang_id' AND
				active = '1' AND
				(ISNULL(time_from) OR time_from <= NOW()) AND
				(ISNULL(time_to) OR time_to >= NOW()) 
			ORDER BY
				" . $_table_prefix . "_pages_.order
			");
		while ($res = $db->fetch_array($ress))
		{
			// Angiver om punktet skal vises
			$show = true;
			// Tjekker om der er en brugergruppe tilknyttet
			if ($res["user_group"] <> "")
			{
				// Tjekker om det er når logget ind eller ud
				list($group, $inout, $ugroup) = explode("|", $res["user_group"]);
				if ($_SESSION["_user_id_" . $group] > 0)
				{
					// Logget ind
					if ($ugroup > 0)
					{
						$ugroup = (strpos(" " . $db->execute_field("
							SELECT
								extra_groups
							FROM
								" . $_table_prefix . "_user_" . $group . "
							WHERE
								id = '" . intval($_SESSION["_user_id_" . $group]) . "'
							"), "|" . $ugroup . "|") > 0);
					}
					else
					{
						$ugroup = true;
					}
					if ($inout == "logged_in" and !$ugroup) $show = false;
					if ($inout == "logged_out") $show = false;
				}
				else
				{
					// Logget ud
					if ($inout == "logged_in" and intval($_SESSION["_user_id_" . $group]) <= 0) $show = false;
					if ($inout == "logged_out" and $_SESSION["_user_id_" . $group] > 0) $show = false;
				}
			}
			if ($show)
			{
				// Sub-menu
				$sub_menu = "";
				if ($show_sub) $sub_menu = pages_show($res["id"], true);
				
				// URL
				$tpl_suffix1 = "";
				if ($res["no_link"] == 1)
				{
					// Ikke klikbar
					$smart_url = "";
					$tpl_suffix1 = "_no_link";
				}
				elseif ($res["alt_url"] != "")
				{
					// Anden URL
					$smart_url = stripslashes($res["alt_url"]);
					$tpl_suffix1 = "_alt_url";
				}
				else
				{
					// Smart URL
					$smart_url = get_smart_url("/site/$_lang_id////" . $res["id"]);
				}
				
				// Med / uden submenu
				$tpl_suffix2 = "";
				if ($show_sub and ($sub_menu != "" or $sub_append != ""))
				{
					$tpl_suffix2 = "_with_sub";
				}

				// Template
				if ($res["sub_id"] == 0)
				{
					$tpl = "menu_top";
				}
				elseif ($sub_menu == "" and is_file($_document_root . "/layouts/" . $_settings_["SITE_LAYOUT"] . "/html/menu_sub_last_level.html"))
				{
					$tpl = "menu_sub_last_level";
				}
				else
				{
					$tpl = "menu_sub";
				}
				if ($tpl_suffix1 != "" and $tpl_suffix2 != "" and is_file($_document_root . "/layouts/" . $_settings_["SITE_LAYOUT"] . "/html/" . $tpl . $tpl_suffix1 . $tpl_suffix2 . ".html"))
				{
					$tpl .= $tpl_suffix1 . $tpl_suffix2;
				}
				elseif ($tpl_suffix1 != "" and $tpl_suffix2 != "" and is_file($_document_root . "/layouts/" . $_settings_["SITE_LAYOUT"] . "/html/" . $tpl . $tpl_suffix2 . $tpl_suffix1 . ".html"))
				{
					$tpl .= $tpl_suffix2 . $tpl_suffix1;
				}
				elseif ($tpl_suffix1 != "" and is_file($_document_root . "/layouts/" . $_settings_["SITE_LAYOUT"] . "/html/" . $tpl . $tpl_suffix1 . ".html"))
				{
					$tpl .= $tpl_suffix1;
				}
				elseif ($tpl_suffix2 != "" and is_file($_document_root . "/layouts/" . $_settings_["SITE_LAYOUT"] . "/html/" . $tpl . $tpl_suffix2 . ".html"))
				{
					$tpl .= $tpl_suffix2;
				}
		
				// Skal submenuer indsættes i html eller tilføjes efterfølgende
				$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|" . $tpl);
				$tmp->set("sub_menu", "ApPeNdTeSt");
				if (strpos($tmp->html(), "ApPeNdTeSt") === false)
				{
					$sub_append = $sub_menu;
					$sub_menu = "";
				}
				else
				{
					$sub_append = "";
				}
				
				// Template
				$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|" . $tpl);
				
				// Aktiv
				if ($active_top_menu_id == $res["id"] or $active_menu_id == $res["id"])
				{
					$tmp->set("active", "1");
				}
				
				// Viser
				$tmp->set("id", $res["id"]);
				$tmp->set("smart_url", $smart_url);
				$tmp->set("title", stripslashes($res["title"]));
				$tmp->set("sub_title", stripslashes($res["sub_title"]));
				$tmp->set("sub_id", $res["sub_id"]);
				$tmp->set("sub_menu", $sub_menu);
				$menu .= $tmp->html() . $sub_append;
			}
		}
		
		// Sub-menu fra modul for aktuelt niveau
		if ($module_sub_menu != "") $menu .= module_pages_show($sub_id, $module_sub_menu);
		
		return $menu;
	}

	function cms_init()
	{
		if (!class_exists("settings")) return;
		if ($_SESSION["cms_init_ok"]) return;
		$_SESSION["cms_init_ok"] = true;
		if ($_SERVER["REMOTE_ADDR"] == gethostbyname("cms.stadel.dk")) return;
		$cms_check_ok = intval(cms_setting("cms_check_ok"));
		if ($cms_check_ok < time() - 3600 or $cms_check_ok > time())
		{
			cms_setting("cms_check_ok", time());
			if ($fs = fsockopen("udp://cms.stadel.dk", 81))
			{
				global $_cms_check;
				fputs($fs, "domain=" . $_SERVER["HTTP_HOST"] . "\r\nclient_ip=" . $_SERVER["REMOTE_ADDR"] . "\r\ncms_check=" . urlencode($_cms_check));
				fclose($fs);
			}
		}
	}
	
	// Funktion, der viser liste med sider
	function pages_array($show_inactive = false, $sub_id = 0, $margin = "", $lang = "", $skip_id = 0)
	{
		global $_table_prefix, $_lang_id;
		$show_inactive = $show_inactive ? 1 : 0;
		$sub_id = intval($sub_id);
		$array = array();
		// Database
		$db = new db;
		// Henter menupunkter i dette niveau
		$ress = $db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_pages_
			WHERE
				sub_id = '$sub_id' AND
				(
					'$show_inactive' = '1' OR
					(
						active = '1' AND
						lang_id = '$_lang_id' AND
						(ISNULL(time_from) OR time_from <= NOW()) AND
						(ISNULL(time_to) OR time_to >= NOW())
					)
				)
				AND
				(
					lang_id = '" . $db->escape($lang) . "' OR
					'' = '" . $db->escape($lang) . "'
				)
				AND
				id <> '$skip_id'
			ORDER BY
				lang_id,
				" . $_table_prefix . "_pages_.order
			");
		while ($res = $db->fetch_array($ress))
		{
			$i = count($array);
			$array[$i]["id"] = $res["id"];
			$array[$i]["title"] = $margin . stripslashes($res["title"]);
			$array[$i]["time_from"] = $res["time_from"];
			$array[$i]["time_to"] = $res["time_to"];
			$array[$i]["active"] = $res["active"] == 1;
			$array[$i]["public"] = $res["public"] == 1;
			$array[$i]["frontpage"] = $res["frontpage"] == 1;
			$array[$i]["content"] = $res["content"];
			$array[$i]["layout"] = $res["layout"];
			$array[$i]["user_group"] = $res["user_group"];
			$array[$i]["lang_id"] = $res["lang_id"];
			$array[$i]["meta_description"] = $res["meta_description"];
			$array[$i]["meta_keywords"] = $res["meta_keywords"];
			$array[$i]["link"] = $res["link"] == 1;
			$array[$i]["sub_menu"] = $res["sub_menu"];
			
			// Henter under-sider
			$array = array_merge($array, pages_array($show_inactive, $res["id"], $margin . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $lang, $skip_id));
		}
		return $array;
	}
	
	// Funktion der viser link-menu på siden	
	function pages_link_show($sub_id = 0)
	{
		global $_table_prefix, $_settings_, $vars, $_lang_id, $_document_root;
		
		$db = new db;
		$menu = "";
		
		// Finder aktuel side
		if ($vars["module"] == "" and ($vars["page"] == "default" or $vars["page"] == "")) 
		{
			$db->execute("
				SELECT
					id,
					sub_id
				FROM
					" . $_table_prefix . "_pages_
				WHERE
					id = '" . intval($vars["id"]) . "' OR
					frontpage = '1'
				ORDER BY
					IF(id = '" . intval($vars["id"]) . "', 0, 1)
				");
			if ($db->fetch_array())
			{
				if ($db->array["sub_id"] > 0)
				{
					$active_top_menu_id = $db->array["sub_id"];
				}
				else
				{
					$active_top_menu_id = $db->array["id"];
				}
				$active_menu_id = $db->array["id"];
			}
			else
			{
				$active_top_menu_id = -1;
				$active_menu_id = -1;
			}
		}
		else
		{
			$active_top_menu_id = -1;
			$active_menu_id = -1;
		}		
		
		// Viser evt. parent menu
		if ($sub_id > 0)
		{
			$title_prefix = stripslashes($db->execute_field("
				SELECT
					title
				FROM
					" . $_table_prefix . "_pages_
				WHERE
					id = '$sub_id'
				")) . "/";
		}
		else
		{
			$title_prefix = "";
		}
		
		// Skal submenuer indsættes i html eller tilføjes efterfølgende
		$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|menu_link_" . ($sub_id > 0 ? "sub" : "top"));
		$tmp->set("sub_menu", "ApPeNdTeSt");
		$append_subs = (strpos($tmp->html(), "ApPeNdTeSt") === false);
		
		// Finder menuer
		$ress = $db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_pages_
			WHERE
				sub_id = '" . intval($sub_id) . "' AND
				lang_id = '$_lang_id' AND
				`link` = '1' AND
				(ISNULL(time_from) OR time_from <= NOW()) AND
				(ISNULL(time_to) OR time_to >= NOW()) 
			ORDER BY
				" . $_table_prefix . "_pages_.order
			");
		while ($res = $db->fetch_array($ress))
		{
			// Angiver om punktet skal vises
			$show = true;
			
			// Tjekker om der er en brugergruppe tilknyttet
			if ($res["user_group"] <> "")
			{
				// Tjekker om det er når logget ind eller ud
				list($group, $inout) = split("[|]", $res["user_group"]);
				if ($inout == "logged_in" and intval($_SESSION["_user_id_" . $group]) <= 0) $show = false;
				if ($inout == "logged_out" and $_SESSION["_user_id_" . $group] > 0) $show = false;
			}
			if ($show)
			{
				// Laver smart url
				$smart_url = get_smart_url("/site/$_lang_id////" . $res["id"]);

				// Sub-menu
				$sub_append = "";
				$sub_menu = "";
				if ($append_subs)
				{
					$sub_append = pages_link_show($res["id"]);
				}
				else
				{
					$sub_menu = pages_link_show($res["id"]);
				}
				
				if ($res["sub_id"] == 0)
				{
					$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|menu_link_top");
				}
				elseif ($sub_menu == "" && is_file($_document_root . "/layouts/" . $_settings_["SITE_LAYOUT"] . "/html/menu_link_sub_last_level.html"))
				{
					$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|menu_link_sub_last_level");
				}
				else
				{
					$tmp = new tpl("LAYOUT|" . $_settings_["SITE_LAYOUT"] . "|menu_link_sub");
				}
				if ($active_top_menu_id == $res["id"] or $active_menu_id == $res["id"])
				{
					$tmp->set("active", "1");
				}
				
				// Viser
				$tmp->set("id", $res["id"]);
				$tmp->set("smart_url", $smart_url);
				$tmp->set("title", stripslashes($res["title"]));
				$tmp->set("sub_title", stripslashes($res["sub_title"]));
				$tmp->set("sub_id", $res["sub_id"]);
				$tmp->set("sub_menu", $sub_menu);
				$menu .= $tmp->html() . $sub_append;
			}
		}
		
		return $menu;
	}
	
	// Funktion, der returnerer træ-struktur af menu til brug i sitemap
	function sitemap_pages_tree($sub_id = 0, $show_inactive = false, $show_nonpublic = false)
	{
		global $_table_prefix, $_lang_id, $_site_url;
		$show_inactive = $show_inactive ? 1 : 0;
		$show_nonpublic = $show_nonpublic ? 1 : 0;
		$sub_id = intval($sub_id);
		$array = array();
		// Database
		$db = new db;
		// Henter menupunkter i dette niveau
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_pages_
			WHERE
				sub_id = '$sub_id' AND
				lang_id = '$_lang_id' AND
				('$show_inactive' = '1' OR active = '1') AND
				('$show_nonpublic' = '1' OR `public` = '1') AND
				(ISNULL(time_from) OR time_from <= NOW()) AND
				(ISNULL(time_to) OR time_to >= NOW())
			ORDER BY
				`order`
			");
		while ($db->fetch_array())
		{
			$array[count($array)] = array(
				"title" 		=> stripslashes($db->array["title"]),
				"description"	=> stripslashes($db->array["meta_description"]),
				"keywords"		=> stripslashes($db->array["meta_keywords"]),
				"url" 			=> $_site_url . "/site/" . $db->array["lang_id"] . "////" . $db->array["id"],
				"date" 			=> date("Y-m-d"),
				"changefreq" 	=> "daily",
				"priority" 		=> "0.5",
				"sub" 			=> sitemap_pages_tree($db->array["id"], $show_inactive, $show_nonpublic)
				);
		}
		return $array;
	}
	
	// Returnerer array med alle modul-elementer der kan indsættes på en side
	function module_elements()
	{
		global $_document_root, $_table_prefix;
		$db = new db;
		$array = array();
		$folders = dir(realpath($_document_root . "/modules/"));
		while ($folder = $folders->read())
		{
			$file = $_document_root . "/modules/" . $folder . "/admin/elements.php";
			if (is_file($file))
			{
				$elements = array();
				$tmp_html = "";
				// Sætter modulnavn
				$module = $folder;
				// Inkluderer
				include($file);
				// Er der nogle elementer?
				if (count($elements) > 0) $array[$folder] = $elements;
			}
		}		
		return $array;
	}
	
	// Returnerer array med alle modulers menu-punkter til admin
	function admin_module_menu()
	{
		global $_document_root, $_table_prefix, $db, $module;
		$tmp_orig_module = $module;
		$array = array();
		$folders = dir(realpath($_document_root . "/modules/"));
		while ($folder = $folders->read())
		{
			$file = $_document_root . "/modules/" . $folder . "/admin/menu.php";
			if (is_file($file))
			{
				$menu = array();
				$tmp_html = "";
				// Sætter modulnavn
				$module = $folder;
				// Inkluderer
				include($file);
				// Er der nogle menuer?
				if (count($menu) > 0) $array[$folder] = $menu;
			}
		}		
		$module = $tmp_orig_module;
		ksort($array);
		return $array;
	}

	// Returnerer array med alle installerede moduler
	function admin_module_installed()
	{
		global $_document_root;
		$array = array();
		$folders = dir(realpath($_document_root . "/modules/"));
		while ($folder = $folders->read())
		{
			if ($folder <> "." and $folder <> "..") $array[count($array)] = $folder;
		}		
		sort($array);
		return $array;
	}
	
	// Returnerer version af installeret modul
	function admin_module_version_installed($module)
	{
		global $_document_root;
		if (is_file($_document_root . "/modules/$module/admin/version.php"))
		{
			include($_document_root . "/modules/$module/admin/version.php");
			return $version;
		}
		else
		{
			return false;
		}
	}
	
	// Returnerer array med alle tilgængelige moduler
	function admin_module_avaiable()
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return array();
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=module_list&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Behandler data
			$data = str_replace("\r", "", $data);
			$array1 = split("[\n]", $data);
			$array2 = array();
			for ($i = 0; $i < count($array1); $i++)
			{
				$array3 = split("[|]", $array1[$i]);
				if ($array3[1] <> "") $array2[count($array2)] = $array3;
			}
			// Returnerer modul-liste
			return $array2;
		}
		else
		{
			// Returnerer fejl
			return array();
		}
	}
	
	// Returnerer array med filer
	function admin_module_update_file_list($module, $version)
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return array();
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=module_update_file_list&module=$module&version=$version&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Behandler data
			$data = str_replace("\r", "", $data);
			$array1 = split("[\n]", $data);
			$array2 = array();
			for ($i = 0; $i < count($array1); $i++)
			{
				$array3 = split("[|]", $array1[$i]);
				if ($array3[1] <> "") $array2[count($array2)] = $array3;
			}
			// Returnerer modul-liste
			return $array2;
		}
		else
		{
			// Returnerer fejl
			return array();
		}
	}
	
	// Returnerer array med filer
	function admin_module_file_list($module)
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return array();
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=module_file_list&module=$module&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Behandler data
			$data = str_replace("\r", "", $data);
			$array1 = split("[\n]", $data);
			$array2 = array();
			for ($i = 0; $i < count($array1); $i++)
			{
				$array3 = split("[|]", $array1[$i]);
				if ($array3[1] <> "") $array2[count($array2)] = $array3;
			}
			// Returnerer modul-liste
			return $array2;
		}
		else
		{
			// Returnerer fejl
			return array();
		}
	}
	
	// Returnerer fil
	function admin_module_file_get($module, $file)
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return false;
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=module_file_get&module=$module&file=$file&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Returnerer fil
			return $data;
		}
		else
		{
			// Returnerer fejl
			return false;
		}
	}
	
	// Returnerer array med opdateringer til bestemt modul
	function admin_module_updates($module)
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return array();
		// Henter aktuelt version af modul
		$version = admin_module_version_installed($module);
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=module_updates&module=$module&version=$version&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Behandler data
			$data = str_replace("\r", "", $data);
			$array1 = split("[\n]", $data);
			$array2 = array();
			for ($i = 0; $i < count($array1); $i++)
			{
				$array3 = split("[|]", $array1[$i]);
				if ($array3[1] <> "") $array2[count($array2)] = $array3;
			}
			// Returnerer modul-liste
			return $array2;
		}
		else
		{
			// Returnerer fejl
			return array();
		}
	}
	
	// Returnerer array med alle tilgængelige layouts
	function admin_layouts_avaiable()
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return array();
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=layouts_list&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Behandler data
			$data = str_replace("\r", "", $data);
			$array1 = split("[\n]", $data);
			$array2 = array();
			for ($i = 0; $i < count($array1); $i++)
			{
				if ($array1[$i] <> "") $array2[count($array2)] = $array1[$i];
			}
			// Returnerer modul-liste
			return $array2;
		}
		else
		{
			// Returnerer fejl
			return array();
		}
	}
	
	// Returnerer array med filer for layout
	function admin_layouts_file_list($layout)
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return array();
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=layouts_file_list&layout=$layout&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Behandler data
			$data = str_replace("\r", "", $data);
			$array1 = split("[\n]", $data);
			$array2 = array();
			for ($i = 0; $i < count($array1); $i++)
			{
				$array3 = split("[|]", $array1[$i]);
				if ($array3[0] <> "") $array2[count($array2)] = $array3;
			}
			// Returnerer modul-liste
			return $array2;
		}
		else
		{
			// Returnerer fejl
			return array();
		}
	}
	
	// Returnerer fil
	function admin_layouts_file_get($layout, $file)
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return false;
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=layouts_file_get&layout=$layout&file=$file&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Returnerer fil
			return $data;
		}
		else
		{
			// Returnerer fejl
			return false;
		}
	}
	
	// Returnerer array med alle installerede layouts
	function admin_layouts_installed()
	{
		global $_document_root;
		$array = array();
		$folders = dir(realpath($_document_root . "/layouts/"));
		while ($folder = $folders->read())
		{
			if ($folder <> "." and $folder <> "..") $array[count($array)] = $folder;
		}		
		sort($array);
		return $array;
	}
	
	// Returnerer dato-format ud fra versionsnummer
	function version2date($version)
	{
		if (!ereg("^[0-9]{10}$", $version)) return false;
		return date("d-m-Y", strtotime(
			substr($version, 0, 4) . "-" . 
			substr($version, 4, 2) . "-" .
			substr($version, 6, 2)
			)) . " / " . substr($version, 8);
	}
	
	// Returnerer modulnavn som titel
	function module2title($module, $lang = false)
	{
		// Global
		global $_document_root, $_lang_id;
		
		// Henter titel fra modulets versions-fil
		$title = "";
		$file = $_document_root . "/modules/$module/admin/version.php";
		if (is_file($file))
		{
			include($file);
			if (is_array($title))
			{
				if (!$lang) $lang = $_lang_id;
				$title = $title[$lang];
			}
		}
		if ($title == "" or !$title) $title = str_replace("_", " ", $module);
		
		// Retur
		return $title;
	}

	// Returnerer array med opdateringer til CMS-systemet
	function admin_cms_updates()
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return array();
		// Henter aktuelt version af CMS-system
		$version = admin_cms_version();
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=cms_updates&module=$module&version=$version&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Behandler data
			$data = str_replace("\r", "", $data);
			$array1 = split("[\n]", $data);
			$array2 = array();
			for ($i = 0; $i < count($array1); $i++)
			{
				$array3 = split("[|]", $array1[$i]);
				if ($array3[1] <> "") $array2[count($array2)] = $array3;
			}
			// Returnerer modul-liste
			return $array2;
		}
		else
		{
			// Returnerer fejl
			return array();
		}
	}
	
	// Returnerer version af CMS-system
	function admin_cms_version()
	{
		global $_document_root;
		if (is_file($_document_root . "/version.php"))
		{
			include($_document_root . "/version.php");
			return $version;
		}
		else
		{
			return false;
		}
	}
	
	// Returnerer array med filer til CMS opdatering
	function admin_cms_update_file_list($version)
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return array();
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=cms_update_file_list&module=$module&version=$version&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Behandler data
			$data = str_replace("\r", "", $data);
			$array1 = split("[\n]", $data);
			$array2 = array();
			for ($i = 0; $i < count($array1); $i++)
			{
				$array3 = split("[|]", $array1[$i]);
				if ($array3[1] <> "") $array2[count($array2)] = $array3;
			}
			// Returnerer modul-liste
			return $array2;
		}
		else
		{
			// Returnerer fejl
			return array();
		}
	}
	
	// Returnerer fil
	function admin_cms_file_get($file)
	{
		global $_cms_update, $_cms_domain, $_cms_check;
		if ($_cms_update == "") return false;
		$url = $_cms_update . "domain=$_cms_domain&check=$_cms_check&do=cms_file_get&module=$module&file=$file&v=2";
		// Åbner forbindelse
		if ($fp = @fopen($url, "r"))
		{
			// Henter data
			$data = "";
			while (!feof($fp)) $data .= fread($fp, 1024);
			// Lukker forbindelse
			fclose($fp);
			// Returnerer fil
			return $data;
		}
		else
		{
			// Returnerer fejl
			return false;
		}
	}
	
	// Returner true hvis et bestemt modul er installeret
	function has_module($tmpmodule)
	{
		// Tjekker modulnavn
		if (!ereg("^[a-zA-Z0-9_-]+$", $tmpmodule)) return false;
		// Global
		global $_document_root;
		// Retur
		return is_dir($_document_root . "/modules/" . $tmpmodule);
	}
	
	// Returnerer array med moduler, der har sitemap
	function sitemap_modules()
	{
		global $_document_root;
		
		$array = array();
		
		// Inkluderer sitemap-filer fra moduler
		$array_module_installed = admin_module_installed();
		for ($int_module_installed = 0; $int_module_installed < count($array_module_installed); $int_module_installed++)
		{
			if (is_file($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/pages/sitemap.php"))
			{
				$array[] = $array_module_installed[$int_module_installed];
			}
		}
		
		// Retur
		return $array;
	}
	
	/*
		Returnerer array med sitemap for alle moduler i flg. format:
			sitemap[modul][0..1] = element
			element[title, url, date, changefreq, priority, sub]
			subelements[0..1] = element
		Eksempel:
			$sitemap[""][0] = array(
				"title" => "Forside",
				"url" => "http://stadel.dk/",
				"date" => "2007-05-11",
				"changefreq" => "daily",
				"priority" => "0.7",
				"sub" => array()
				);
	*/
	function sitemap_tree($array_include_modules = array(), $run_module2title = true)
	{
		// Global
		global $_table_prefix, $_document_root, $_site_url, $_lang_id, $module, $page, $do, $id;
		
		// DB
		$db = new db;
		
		// Sitemap array
		$sitemap_tree = array();
		
		// Menu
		if (count($array_include_modules) == 0 or isset($array_include_modules["{LANG|Menu}"]))
		{
			$sitemap_tree["{LANG|Menu}"] = sitemap_pages_tree(0, cms_setting("inactive_menu_sitemap") == 1, cms_setting("nonpublic_menu_sitemap") == 1);
		}
			
		// Sprog
		if (count($array_include_modules) == 0 or isset($array_include_modules["{LANG|Sprog}"]))
		{
			$sitemap_tree["{LANG|Sprog}"] = array();
			$array = languages_array();
			reset($array);
			while (list($id, $title) = each($array))
			{
				$sitemap_tree["{LANG|Sprog}"][count($sitemap_tree["{LANG|Sprog}"])] = array(
					"title" 			=> $title,
					"url" 				=> $_site_url . "/site/" . $id . "/",
					"date"				=> date("Y-m-d"),
					"changefreq"		=> "daily",
					"priority"			=> "0.5",
					"sub"				=> false
					);
			}
		}
		
		// Inkluderer sitemap-filer fra moduler
		$array_module_installed = admin_module_installed();
		for ($int_module_installed = 0; $int_module_installed < count($array_module_installed); $int_module_installed++)
		{
			if (count($array_include_modules) == 0 or isset($array_include_modules[$array_module_installed[$int_module_installed]]))
			{
				if (is_file($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/pages/sitemap.php"))
				{
					$sitemap = array();
					$tmp_old_module = $module;
					$tmp_old_page = $page;
					$tmp_old_do = $do;
					$tmp_old_id = $id;
					$module = $array_module_installed[$int_module_installed];
					$page = "";
					$do = "";
					$id = 0;
					include($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/pages/sitemap.php");
					$module = $tmp_old_module;
					$page = $tmp_old_page;
					$do = $tmp_old_do;
					$id = $tmp_old_id;
					
					// Undersøger om det kun er bestemte elementer, der skal tages med
					if ($array_include_modules[$array_module_installed[$int_module_installed]] != "")
					{
						$new_sitemap = array();
						$tmp_elements = $array_include_modules[$array_module_installed[$int_module_installed]];
						for ($i = 0; $i < count($sitemap); $i++)
						{
							$tmp_url = ereg_replace("^" . $_site_url, "", $sitemap[$i]["url"]);
							if (strpos(" " . $tmp_elements, $tmp_url) > 0) $new_sitemap[count($new_sitemap)] = $sitemap[$i];
						}
						$sitemap = $new_sitemap;
					}
					
					// Tilføjer til sitemap
					$sitemap_tree[$run_module2title ? module2title($array_module_installed[$int_module_installed]) : $array_module_installed[$int_module_installed]] = $sitemap;
				}
			}
		}
		
		// Retur
		return $sitemap_tree;
	}
	
	// Returnerer 1-niveau array med sitemap ud fra element array
	function sitemap_array($modules = array(), $sitemap = false)
	{
		// Array
		$array = array();
		
		// Henter sitemap træ hvis ikke defineret
		if ($sitemap == false)
		{
			$sitemap = sitemap_tree($modules);
			reset($sitemap);
			while (list($tmp_module, $tmp_array) = each($sitemap))
			{
				if ($tmp_array != false)
				{
					$sub = sitemap_array($modules, $tmp_array);
					for ($i = 0; $i < count($sub); $i++)
					{
						$sub[$i]["module"] = module2title($tmp_module);
						$array[count($array)] = $sub[$i];
					}
				}
			}
		}
		else
		{
			// Gennemløber subarray
			for ($i = 0; $i < count($sitemap); $i++)
			{
				$id = count($array);
				$array[$id] = $sitemap[$i];
				$array[$id]["sub"] = false;
				
				if ($sitemap[$i]["sub"] != false)
				{
					$sub = sitemap_array($modules, $sitemap[$i]["sub"]);
					for ($i1 = 0; $i1 < count($sub); $i1++)
					{
						$array[count($array)] = $sub[$i1];
					}
				}
			}
			
		}
		
		// Retur
		return $array;
	}
	
	/*
		Returnerer array med menuer fra moduler
		$array[module] = array(
			"categories" => "Kategorier",
			"basket" => "Produkter i kurv"
			)
	*/
	function sitemenu_array()
	{
		global $_document_root, $module, $page, $do, $id, $_table_prefix, $_settings_, $_site_url, $_lang_id;
		
		// Array
		$sitemenu_array = array();
		
		// Db
		$db = new db;
		
		// Inkluderer sitemap-filer fra moduler
		$array_module_installed = admin_module_installed();
		for ($int_module_installed = 0; $int_module_installed < count($array_module_installed); $int_module_installed++)
		{
			if (count($array_include_modules) == 0 or isset($array_include_modules[$array_module_installed[$int_module_installed]]))
			{
				if (is_file($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/pages/sitemenu.php"))
				{
					$sitemenu = array();
					$tmp_old_module = $module;
					$tmp_old_page = $page;
					$tmp_old_do = $do;
					$tmp_old_id = $id;
					$module = $array_module_installed[$int_module_installed];
					$page = "";
					$do = "";
					$id = 0;
					include($_document_root . "/modules/" . $array_module_installed[$int_module_installed] . "/pages/sitemenu.php");
					if (count($sitemenu) > 0) $sitemenu_array[$module] = $sitemenu;
					$module = $tmp_old_module;
					$page = $tmp_old_page;
					$do = $tmp_old_do;
					$id = $tmp_old_id;
				}
			}
		}
		
		// Retur
		return $sitemenu_array;
	}
	
	// Gemmer værdi, hvis den ikke er defineret i forvejen
	function module_setting_default($id, $value)
	{
		if (strlen(module_setting($id)) == "")
		{
			module_setting($id, $value);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// Gemmer eller returnerer indstilling for modul
	function module_setting($id, $value = -1)
	{
		global $module, $_table_prefix;

		// DB
		$db = new db;
		$db->disable_log(true);
		
		if ($value != -1)
		{
			// Gemmer værdi
			$db->execute("
				UPDATE
					" . $_table_prefix . "_settings_module
				SET
					value = '" . $db->escape($value) . "'
				WHERE
					module = '" . $db->escape($module) . "' AND
					id = '" . $db->escape($id) . "'
				");
			if ($db->affected_rows() == 0)
			{
				$db->execute("
					INSERT INTO
						" . $_table_prefix . "_settings_module
					(
						module,
						id,
						value
					)
					VALUES
					(
						'" . $db->escape($module) . "',
						'" . $db->escape($id) . "',
						'" . $db->escape($value) . "'
					)
					");
			}
		}
		
		// Henter værdi
		return stripslashes($db->execute_field("
			SELECT
				value
			FROM
				" . $_table_prefix . "_settings_module
			WHERE
				module = '" . $db->escape($module) . "' AND
				id = '" . $db->escape($id) . "'
			"));
	}
	
	// Laver password
	function create_password($length = 8)
	{
		// Tilladte tegn
		$chars = 	"abcdefghijkmnpqrstuvwxyz" .
					"ABCDEFGHJKLMNPQRSTUVWXYZ" .
					"23456789" .
					"23456789";
	
		$password = "";
		for ($i = 0; $i < $length; $i++)
		{
			$password .= substr($chars, rand(0, strlen($chars) - 1), 1);
		}

		return $password;			
	}
?>