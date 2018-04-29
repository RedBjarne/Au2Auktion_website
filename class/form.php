<?php
	// Copyright thomas@stadel.dk 2006-2013
	
	class form
	{
		// Template prefix
		var $tplprefix = "";
		
		// Array med alle input-værdier for felter
		var $values = array();
		// Array med alle felttyper
		var $fields = array();
		// Angiver om formularen er submit'et
		var $submitted = false;
		var $edit = false;
		// Tekst, der skal stå på submit-knap
		var $submit_text = "{LANG|Fortsæt}";
		// Tekst, der skal stå på bekræft-knap
		var $confirm_text = "{LANG|Bekræft}";
		// Tekst, der skal stå på ret-knap
		var $edit_text = "{LANG|Ret}";
		// Form-id hvis der er mere end en formular på en side
		var $form_id = "";
		// Angiver om textareas skal være WYSIWYG
		var $wysiwyg = false;
		var $has_wysiwyg = false;
		var $wysiwyg_css = "";
		var $wysiwyg_dir = "";
		var $wysiwyg_upload = false;
		var $wysiwyg_upload_shared = false;
		// Klasser der anvendes til tabel felter
		var $td_classes = array("td1", "td2");
		// Tab
		var $default_tab = false;
		var $tabs = array();
		var $current_tab_id = 0;
		// Target frame
		var $target = "";
		// Metode GET / POST
		var $method = "POST";
		var $enctype = "";
		// Iframe save
		var $iframe_save = false;
		// Skal data bekræftes? Virker ikke sammen med iframe_save = true
		var $confirm_form = false;
		var $confirmed = false;
		
		function tplprefix($tplprefix)
		{
			$this->tplprefix = $tplprefix;
		}
		
		// Init
		function form($form_id = "")
		{
			global $_site_url, $_settings_;
			// Sætter standard CSS til WYSIWYG-editor
			$this->wysiwyg_css = "/layouts/" . $_settings_["SITE_LAYOUT"] . "/css/default.css";
			// Gemmer form-id
			$this->form_id = $form_id;
			// Tjekker om formularen er POST'et
			if ($_SERVER["REQUEST_METHOD"] == "POST")
			{
				$vars = $_POST;
			}
			else
			{
				$vars = $_GET;
			}
			if ($vars["_form_" . $this->form_id] == "submit")
			{
				$this->submitted = true;
			}
			elseif ($vars["_form_" . $this->form_id] == "edit")
			{
				$this->submitted = true;
				$this->edit = true;
			}
			elseif ($vars["_form_" . $this->form_id] == "confirm")
			{
				$this->submitted = true;
				$this->confirmed = true;
			}				
			if ($this->submitted)
			{
				// Henter POST'ede variabler
				$this->values = $vars;
			}
		}
		
		// Metode
		function method($method)
		{
			$method = strtoupper($method);
			if ($method <> "POST" and $method <> "GET") $method = "POST";
			$this->method = $method;
		}
		
		// Skal der gemmes via iframe ?
		function iframe_save($iframe_save = true)
		{
			$this->confirm_form = false;
			$this->iframe_save = $iframe_save;
		}
		
		// Skal data bekræftes ?
		function confirm_form($confirm_form = true)
		{
			$this->iframe_save = false;
			$this->confirm_form = $confirm_form;
		}
		
		// Target frame
		function target($target)
		{
			$this->target = $target;
		}
		
		// Viser template
		function tpl($name, $value = "", $value2 = "")
		{
			$id = count($this->fields);
			$this->fields[$id]["type"] = "tpl";
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			$this->fields[$id]["value2"] = $value2;
		}
		
		// Ny kolonne
		function new_column()
		{
			$id = count($this->fields);
			$this->fields[$id]["type"] = "new_column";
		}
		
		// Hidden-felt
		function hidden($name, $value)
		{
			$id = count($this->fields);
			$this->fields[$id]["type"] = "hidden";
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
		}
		
		// Image-felt
		function image($text, $name, $required = false, $error = "")
		{
			global $_document_root, $_tmp_dir;
			$this->enctype = "multipart/form-data";
			$value = "";
			// URL og filnavn til midlertidige billede
			$tmp_file = session_id() . "_" . $this->form_id . "_" . $name;
			$tmp_url = substr($_tmp_dir, strlen($_document_root)) . $tmp_file;
			$tmp_filename = $_tmp_dir . $tmp_file;
			if ($this->submitted)
			{
				// Tjekker om der er POST'et et gyldigt billede
				if (is_uploaded_file($_FILES[$name]["tmp_name"]))
				{
					// Filtype
					$realext = strtolower(preg_replace("/^.*\.([a-z]+)$/i", "\\1", $_FILES[$name]["name"]));
					
					// Flytter til tmp-mappe
					move_uploaded_file($_FILES[$name]["tmp_name"], $tmp_filename);
					
					// Indlæser billede
					if ($realext == "jpg" or $realext == "jpeg")
					{
						$image = @imagecreatefromjpeg($tmp_filename);
					}
					elseif ($realext == "png")
					{
						$image = @imagecreatefrompng($tmp_filename);
					}
					elseif ($realext == "gif")
					{
						$image = @imagecreatefromgif($tmp_filename);
					}
					else
					{
						$image = false;
					}
					if ($image)
					{
						// Gemmer miniature
						if (is_file($tmp_filename . ".jpg")) @unlink($tmp_filename . ".jpg");
						$img = new image;
						imagejpeg($img->imagemaxsize($image, 150, 75), $tmp_filename . ".jpg");
					}
					else
					{
						@unlink($tmp_filename);
					}
				}
				// Tjekker om nuværende billede skal slettes
				if ($this->values[$name . "_delete"] <> "")
				{
					if (is_file($tmp_filename))
					{
						@unlink($tmp_filename);
						@unlink($tmp_filename . ".jpg");
					}
				}
			}
			// Tjekker om der er uploadet et gyldigt billede
			if (is_file($tmp_filename))
			{
				$value = $tmp_url;
				$this->values[$name] = $value;
			}
			$id = count($this->fields);
			$this->fields[$id]["tab_id"] = $this->current_tab_id;
			$this->fields[$id]["type"] = "image";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			if ($required)
			{
				$this->fields[$id]["ereg"] = "^.+$";
				$this->fields[$id]["ereg_error"] = $error;
			}
		}
		
		// File-felt
		function file($text, $name, $required = false, $error = "", $extentions = array())
		{
			global $_document_root, $_tmp_dir;
			$this->enctype = "multipart/form-data";
			$value = "";
			$extention_error = "";
			// URL og filnavn til midlertidige fil
			$tmp_file = session_id() . "_" . $this->form_id . "_" . $name;
			$tmp_url = substr($_tmp_dir, strlen($_document_root)) . $tmp_file;
			$tmp_filename = $_tmp_dir . $tmp_file;
			if ($this->submitted)
			{
				// Tjekker om der er POST'et en fil
				if (is_uploaded_file($_FILES[$name]["tmp_name"]))
				{
					// Tjekker fil-type
					$realname = $_FILES[$name]["name"];
					$realext = strtolower(substr($realname, strrpos($realname, ".") + 1));
					if (!in_array($realext, $extentions) and count($extentions) > 0)
					{
						// Fejl i fil-type
						for ($i = 0; $i < count($extentions); $i++)
						{
							if ($extention_error <> "")
							{
								$extention_error .= "/";
							}
							$extention_error .= $extentions[$i];
						}
						$extention_error = "Ugyldig fil-type - kun " .
							$extention_error . " er tilladt";
					}
					else
					{
						// Gemmer i tmp-mappe
						if (is_file($tmp_filename))
						{
							@unlink($tmp_filename);
						}
						move_uploaded_file($_FILES[$name]["tmp_name"], $tmp_filename);
						// Gemmer fil-endelse i session
						$_SESSION["_form_" . $this->form_id . "_" . $name . "_realname"] = $realname;
						$_SESSION["_form_" . $this->form_id . "_" . $name . "_realext"] = $realext;
						// Gemmer fil-endelse i this->values
						$this->values[$name . "_realname"] = $realname;
						$this->values[$name . "_realext"] = $realext;
					}
				}
				// Tjekker om nuværende fil skal slettes
				if ($this->values[$name . "_delete"] <> "")
				{
					if (is_file($tmp_filename))
					{
						@unlink($tmp_filename);
					}
				}
			}
			// Tjekker om der er uploadet en gyldig fil
			if (is_file($tmp_filename))
			{
				$value = $tmp_url;
				$this->values[$name] = $value;
			}
			$id = count($this->fields);
			$this->fields[$id]["tab_id"] = $this->current_tab_id;
			$this->fields[$id]["type"] = "file";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			if ($required)
			{
				$this->fields[$id]["ereg"] = "^.+$";
				$this->fields[$id]["ereg_error"] = $error;
			}
			$this->fields[$id]["error"] = $extention_error;
			// Gemmer fil-endelse i $this->values
			if ($this->values[$name] <> "")
			{
				$this->values[$name . "_realname"] = $_SESSION["_form_" . $this->form_id . "_" . $name . "_realname"];
				$this->values[$name . "_realext"] = $_SESSION["_form_" . $this->form_id . "_" . $name . "_realext"];
			}
		}
		
		// Checkbox-felt
		function checkbox($text, $name, $value = "", $required = false, $error = "", $disabled = false)
		{
			if ($this->submitted)
			{
				$value = $this->values[$name] <> "";
			}
			$id = count($this->fields);
			$this->fields[$id]["tab_id"] = $this->current_tab_id;
			$this->fields[$id]["type"] = "checkbox";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			$this->fields[$id]["ereg"] = $required ? "^.+$" : "";
			$this->fields[$id]["ereg_error"] = $error;
			$this->fields[$id]["disabled"] = $disabled;
		}
		
		// Textarea-felt
		function textarea($text, $name, $value = "", $ereg = "", $ereg_error = "", $php = "", $cols = 35, $rows = 8, $popup_editor = false)
		{
			if ($this->submitted)
			{
				$value = stripslashes($this->values[$name]);
			}
			$id = count($this->fields);
			$this->fields[$id]["tab_id"] = $this->current_tab_id;
			$this->fields[$id]["type"] = "textarea";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			$this->fields[$id]["ereg"] = $ereg;
			$this->fields[$id]["ereg_error"] = $ereg_error;
			$this->fields[$id]["php"] = $php;
			$this->fields[$id]["cols"] = $cols;
			$this->fields[$id]["rows"] = $rows;
			$this->fields[$id]["wysiwyg"] = ($this->wysiwyg && !$popup_editor);
			$this->fields[$id]["popup_editor"] = $popup_editor;
			if ($this->wysiwyg) $this->has_wysiwyg = true;
		}
		
		// Input-felt
		function input($text, $name, $value = "", $ereg = "", $ereg_error = "", $php = "", $info = "", $size = "")
		{
			if ($this->submitted)
			{
				$value = stripslashes($this->values[$name]);
			}
			$id = count($this->fields);
			$this->fields[$id]["tab_id"] = $this->current_tab_id;
			$this->fields[$id]["type"] = "input";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			$this->fields[$id]["ereg"] = $ereg;
			$this->fields[$id]["ereg_error"] = $ereg_error;
			$this->fields[$id]["php"] = $php;
			$this->fields[$id]["info"] = $info;
			$this->fields[$id]["size"] = $size;
		}
		
		// Input-felt
		function input2($text, $name, $value = "", $ereg = "", $ereg_error = "", $php = "", $info = "", $size = "")
		{
			if ($this->submitted)
			{
				$value = stripslashes($this->values[$name]);
			}
			$id = count($this->fields);
			$this->fields[$id]["tab_id"] = $this->current_tab_id;
			$this->fields[$id]["type"] = "input2";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			$this->fields[$id]["ereg"] = $ereg;
			$this->fields[$id]["ereg_error"] = $ereg_error;
			$this->fields[$id]["php"] = $php;
			$this->fields[$id]["info"] = $info;
			$this->fields[$id]["size"] = $size;
		}
		
		// Readonly-felt
		function readonly($text, $value)
		{
			$id = count($this->fields);
			$this->fields[$id]["type"] = "readonly";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["value"] = $value;
		}
		
		// Password-felt
		function password($text, $name, $value = "", $ereg = "", $ereg_error = "", $php = "", $info = "", $size = "")
		{
			if ($this->submitted)
			{
				$value = stripslashes($this->values[$name]);
			}
			$id = count($this->fields);
			$this->fields[$id]["tab_id"] = $this->current_tab_id;
			$this->fields[$id]["type"] = "password";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			$this->fields[$id]["ereg"] = $ereg;
			$this->fields[$id]["ereg_error"] = $ereg_error;
			$this->fields[$id]["php"] = $php;
			$this->fields[$id]["info"] = $info;
			$this->fields[$id]["size"] = $size;
		}
		
		// Radio-liste
		function radio($text, $name, $value = "", $ereg = "", $ereg_error = "", $php = "", $options = "")
		{
			if ($this->submitted)
			{
				$value = stripslashes($this->values[$name]);
			}
			$id = count($this->fields);
			$this->fields[$id]["tab_id"] = $this->current_tab_id;
			$this->fields[$id]["type"] = "radio";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			$this->fields[$id]["ereg"] = $ereg;
			$this->fields[$id]["ereg_error"] = $ereg_error;
			$this->fields[$id]["php"] = $php;
			$this->fields[$id]["options"] = $options;
		}
		
		// Select-liste
		function select($text, $name, $value = "", $ereg = "", $ereg_error = "", $php = "", $options = "")
		{
			if ($this->submitted)
			{
				$value = stripslashes($this->values[$name]);
			}
			$id = count($this->fields);
			$this->fields[$id]["tab_id"] = $this->current_tab_id;
			$this->fields[$id]["type"] = "select";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			$this->fields[$id]["ereg"] = $ereg;
			$this->fields[$id]["ereg_error"] = $ereg_error;
			$this->fields[$id]["php"] = $php;
			$this->fields[$id]["options"] = $options;
		}
		
		// Combo-liste
		function combo($text, $name, $value = "", $ereg = "", $ereg_error = "", $php = "", $options = "")
		{
			if ($this->submitted)
			{
				$value = stripslashes($this->values[$name]);
			}
			$id = count($this->fields);
			$this->fields[$id]["tab_id"] = $this->current_tab_id;
			$this->fields[$id]["type"] = "combo";
			$this->fields[$id]["text"] = $text;
			$this->fields[$id]["name"] = $name;
			$this->fields[$id]["value"] = $value;
			$this->fields[$id]["ereg"] = $ereg;
			$this->fields[$id]["ereg_error"] = $ereg_error;
			$this->fields[$id]["php"] = $php;
			$this->fields[$id]["options"] = $options;
		}
		
		// Indsæt en tab
		function tab($text, $default = false)
		{
			// Gemmer tab - så kan vi lave alle sammen på én gang øverst
			$tab_id = count($this->tabs);
			$this->tabs[$tab_id]["text"] = $text;
			$this->current_tab_id = $tab_id;
			if ($this->submitted)
			{
				$this->tabs[$tab_id]["default"] = 
					($this->values["_form_" . $this->form_id . "_active_tab"] == $tab_id ? true : false);
			}
			elseif ($this->default_tab == $tab_id)
			{
				$this->tabs[$tab_id]["default"] = true;
			}
			else
			{
				$this->tabs[$tab_id]["default"] = $default;
			}
			if ($this->tabs[$tab_id]["default"]) $this->default_tab = $tab_id;
			// Felt - så ved vi hvornår vi skal starte på den nye tab
			$id = count($this->fields);
			$this->fields[$id]["type"] = "tab";
			$this->fields[$id]["value"] = $tab_id;
		}
		
		// Viser formular
		function html()
		{
			// Laver tjek af indtastninger
			$show_confirm = ($this->check_form() and $this->confirm_form and !$this->edit);
			// Globale variabler
			global $module, $page, $_document_root, $do, $id, $_lang_id, $_site_url, $messages;
			// HTML
			$html = "";
			// Aktuel CSS-klasse til tabel felter
			$td_class = 0;
			// Aktuel tab
			$active_tab = 0;
			// Gemmes der i iframe?
			if ($this->iframe_save)
			{
				$this->target = "_form_" . $this->form_id . "_iframe_save";
			}
			// Sætter parametre for wysiwyg-editor
			$_SESSION["sess_spaw_imglibs"] = ($this->wysiwyg_dir <> "" ? ($this->wysiwyg_dir) : "/X/");
			$_SESSION["sess_spaw_upload_allowed"] = $this->wysiwyg_upload;
			$_SESSION["sess_spaw_upload_shared_allowed"] = ($this->wysiwyg_upload and $this->wysiwyg_upload_shared);
			$_SESSION["sess_spaw_css"] = $this->wysiwyg_css;
			// Er det en WYSIWYG-editor?
			if ($this->has_wysiwyg)
			{
				$form_editor = cms_setting("form_editor");
				if (!preg_match("/^(spaw|tinymce)$/", $form_editor)) $form_editor = "spaw";
				
				if ($form_editor == "tinymce")
				{
					// TinyMCE
					
				}	
				else
				{
					// SPAW
					
					// Initialiserer wysiwyg
					global $spaw_root, $spaw_dir, $spaw_base_url, $spaw_default_toolbars,
						$spaw_default_theme, $spaw_default_lang, $spaw_default_css_stylesheet,
						$spaw_inline_js, $spaw_active_toolbar, $spaw_dropdown_data,
						$spaw_valid_imgs, $spaw_upload_allowed, $spaw_img_delete_allowed,
						$spaw_imglibs, $spaw_a_targets, $spaw_img_popup_url,
						$spaw_internal_link_script, $spaw_disable_style_controls;
					$spaw_root = $_document_root . "/js/spaw/";
					include($spaw_root . "spaw_control.class.php");
					
					// Admin?
					$usradmin = new user("admin");
					if ($usradmin->logged_in)
					{
						// Sætter array med cms elementer
						$_SESSION["sess_spaw_cmselements"] = array();
						
						// Henter elementer
						$elements = module_elements();
						
						// Laver liste med elementer
						reset($elements);
						while (list($tmp_module, $tmp_array) = each($elements))
						{
							reset($tmp_array);
							while (list($key, $value) = each($tmp_array))
							{
								list($tmp_page, $tmp_do, $tmp_id) = split("[\|]", $value);
								$_SESSION["sess_spaw_cmselements"]["MODULE|$tmp_module|$tmp_page|$tmp_do|$tmp_id"] = module2title($tmp_module) . " - " . $key;
							}
						}
					}
					else
					{
						unset($_SESSION["sess_spaw_cmselements"]);
					}
					
					// Gemmer session i /js/spaw/
					$sess = $_SESSION;
					session_write_close();
					session_set_cookie_params(0, "/js/spaw/");
					session_start();
					foreach ($sess as $key => $val)
					{
						if (preg_match("/^sess_spaw_/", $key)) $_SESSION[$key] = $val;
					}
					session_write_close();
					session_set_cookie_params(0, $action);
					session_start();
				}
			}
			
			// Konverter
			$cnv = new convert;
			
			// Henter header
			$tmp = new tpl($this->tplprefix . "_form_header");

			// Felter
			for ($i = 0; $i < count($this->fields); $i++)
			{
				$tmp->set($this->fields[$i]["name"], $cnv->tagentities(htmlspecialchars($this->fields[$i]["value"])));
			}
						
			$tmp->set("form_do", $show_confirm ? "confirm" : "submit");
			$tmp->set("action", eregi("^" . eregi_replace("^(http|https)://", "", $_site_url) . "/Admin/", $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]) ? ($_site_url . "/Admin/") : $_site_url);
			$tmp->set("target", $this->target);
			$tmp->set("method", $this->enctype <> "" ? "POST" : $this->method);
			$tmp->set("enctype", $this->enctype);
			$tmp->set("page", $page);
			$tmp->set("module", $module);
			$tmp->set("do", $do);
			$tmp->set("id", $id);
			$tmp->set("form_id", $this->form_id);
			
			// Tjekker om vi benytter tabs
			if (count($this->tabs) > 0)
			{
				// Tab header 1
				$tmp1 = new tpl($this->tplprefix . "_form_tab_header_1");
				$tmp1->set("form_id", $this->form_id);
				$tmp->set("tab", $tmp1->html());
				// Gennemløber alle tabs
				for ($i = 0; $i < count($this->tabs); $i++)
				{
					if ($this->tabs[$i]["default"]) $active_tab = $i;
					if ($this->tabs[$i]["error"])
					{
						$tmp1 = new tpl($this->tplprefix . "_form_tab_header_tab_error");
					}
					else
					{
						$tmp1 = new tpl($this->tplprefix . "_form_tab_header_tab");
					}
					$tmp1->set("id", $i);
					$tmp1->set("text", $this->tabs[$i]["text"]);
					$tmp1->set("form_id", $this->form_id);
					$tmp->add("tab", $tmp1->html());
				}
				// Tab header 2
				$tmp1 = new tpl($this->tplprefix . "_form_tab_header_2");
				$tmp1->set("form_id", $this->form_id);
				$tmp->add("tab", $tmp1->html());
			}
			
			$html .= $tmp->html();
			
			// Definerer om vi er i gang med en tab
			$has_tab = false;
			
			// Gennemløber felter
			for ($i = 0; $i < count($this->fields); $i++)
			{
				// Finder felt-type
				if ($this->fields[$i]["type"] == "input")
				{
					// Input
					if ($show_confirm)
					{
						$tmp = new tpl($this->tplprefix . "_form_confirm");
					}
					else
					{
						$tmp = new tpl($this->tplprefix . "_form_input");
					}
				}
				elseif ($this->fields[$i]["type"] == "input2")
				{
					// Input
					if ($show_confirm)
					{
						$tmp = new tpl($this->tplprefix . "_form_confirm");
					}
					else
					{
						$tmp = new tpl($this->tplprefix . "_form_input2");
					}
				}
				elseif ($this->fields[$i]["type"] == "readonly")
				{
					// Readonly
					$tmp = new tpl($this->tplprefix . "_form_readonly");
				}
				elseif ($this->fields[$i]["type"] == "textarea")
				{
					// Textarea
					if ($show_confirm)
					{
						$tmp = new tpl($this->tplprefix . "_form_confirm");
					}
					else
					{
						// Hvis det er WYSIWYG, vises denne
						if ($this->fields[$i]["wysiwyg"])
						{
							if ($form_editor == "tinymce")
							{
								// TinyMCE
								$tmp = new tpl($this->tplprefix . "_form_textarea_wysiwyg_tinymce");
								$tmp->set("css", $this->wysiwyg_css);
							}
							else
							{
								// SPAW
								$spaw_lang = $_lang_id;
								if ($spaw_lang == "da") $spaw_lang = "dk";
								if (!is_file($_document_root . "/js/spaw/plugins/core/lib/lang/" .
									$spaw_lang . ".lang.inc.php")) $spaw_lang = "en";
								$SPAW_Wysiwyg = new SpawEditor(
									$this->fields[$i]["name"],
									$cnv->tagentities($this->fields[$i]["value"]),
									$spaw_lang,
									'all',
									'spaw2lite',
									'100%',
									'400px',
									$this->wysiwyg_css,
									$this->fields[$i]["name"]
									);
								$tmp = new tpl($this->tplprefix . "_form_textarea_wysiwyg_spaw");
								$tmp->set("wysiwyg", $SPAW_Wysiwyg->getHtml());
							}
						}
						elseif ($this->fields[$i]["popup_editor"] != false)
						{
							// Så skal der vises et ikon til popup-editor
							if ($this->fields[$i]["popup_editor"] === "rules")
							{
								// Regel editor
								$tmp = new tpl($this->tplprefix . "_form_textarea_popup_rules");
							}
							elseif ($this->fields[$i]["popup_editor"] === "adminrules")
							{
								// Regel editor
								$tmp = new tpl($this->tplprefix . "_form_textarea_popup_rules");
								$tmp->set("rule_type", "admin");
							}
							else
							{
								// Wysiwyg editor
								$tmp = new tpl($this->tplprefix . "_form_textarea_popup_wysiwyg");
								$tmp->set("css", $this->wysiwyg_css);
							}
						}
						else
						{
							$tmp = new tpl($this->tplprefix . "_form_textarea");
						}
					}
				}
				elseif ($this->fields[$i]["type"] == "password")
				{
					// Password
					if ($show_confirm)
					{
						$tmp = new tpl($this->tplprefix . "_form_confirm_password");
						$tmp->set("value_star", str_repeat("*", strlen($this->fields[$i]["value"])));
					}
					else
					{
						$tmp = new tpl($this->tplprefix . "_form_password");
					}
				}
				elseif ($this->fields[$i]["type"] == "radio")
				{
					// Select
					if ($show_confirm)
					{
						$tmp = new tpl($this->tplprefix . "_form_confirm");
						for ($i2 = 0; $i2 < count($this->fields[$i]["options"]); $i2++)
						{
							list($value, $option) = $this->fields[$i]["options"][$i2];
							if ($value == $this->fields[$i]["value"])
							{
								$tmp->set("value_visible", $option);
								$i2 = count($this->fields[$i]["options"]);
							}
						}
					}
					else
					{
						$options = "";
						for ($i2 = 0; $i2 < count($this->fields[$i]["options"]); $i2++)
						{
							list($value, $option) = $this->fields[$i]["options"][$i2];
							if ($value == $this->fields[$i]["value"])
							{
								$tmp = new tpl($this->tplprefix . "_form_radio_option_selected");
							}
							else
							{
								$tmp = new tpl($this->tplprefix . "_form_radio_option");
							}
							$tmp->set("i", $i2);
							$tmp->set("name", $this->fields[$i]["name"]);
							$tmp->set("value", $value);
							$tmp->set("option", $option);
							$options .= $tmp->html();
						}
						$tmp = new tpl($this->tplprefix . "_form_radio");
						$tmp->set("options", $options);
					}
				}
				elseif ($this->fields[$i]["type"] == "select")
				{
					// Select
					if ($show_confirm)
					{
						$tmp = new tpl($this->tplprefix . "_form_confirm");
						for ($i2 = 0; $i2 < count($this->fields[$i]["options"]); $i2++)
						{
							list($value, $option) = $this->fields[$i]["options"][$i2];
							if ($value == $this->fields[$i]["value"])
							{
								$tmp->set("value_visible", $option);
								$i2 = count($this->fields[$i]["options"]);
							}
						}
					}
					else
					{
						$options = "";
						for ($i2 = 0; $i2 < count($this->fields[$i]["options"]); $i2++)
						{
							list($value, $option) = $this->fields[$i]["options"][$i2];
							if ($value == $this->fields[$i]["value"])
							{
								$tmp = new tpl($this->tplprefix . "_form_select_option_selected");
							}
							else
							{
								$tmp = new tpl($this->tplprefix . "_form_select_option");
							}
							$tmp->set("value", $value);
							$tmp->set("option", $option);
							$options .= $tmp->html();
						}
						$tmp = new tpl($this->tplprefix . "_form_select");
						$tmp->set("options", $options);
					}
				}
				elseif ($this->fields[$i]["type"] == "combo")
				{
					// Combo
					if ($show_confirm)
					{
						$tmp = new tpl($this->tplprefix . "_form_confirm");
					}
					else
					{
						$options = "";
						for ($i2 = 0; $i2 < count($this->fields[$i]["options"]); $i2++)
						{
							list($value, $option) = $this->fields[$i]["options"][$i2];
							$tmp = new tpl($this->tplprefix . "_form_combo_option");
							$tmp->set("value", $value);
							$tmp->set("option", $option);
							$options .= $tmp->html();
						}
						$tmp = new tpl($this->tplprefix . "_form_combo");
						$tmp->set("options", $options);
					}
				}
				elseif ($this->fields[$i]["type"] == "checkbox")
				{
					// Checkbox
					if ($show_confirm)
					{
						if ($this->fields[$i]["value"] == 1 or $this->fields[$i]["value"])
						{
							$tmp = new tpl($this->tplprefix . "_form_confirm_true");
						}
						else
						{
							$tmp = new tpl($this->tplprefix . "_form_confirm_false");
						}
					}
					else
					{
						if ($this->fields[$i]["value"] == 1 or $this->fields[$i]["value"])
						{
							$tmp = new tpl($this->tplprefix . "_form_checkbox_checked");
						}
						else
						{
							$tmp = new tpl($this->tplprefix . "_form_checkbox");
						}
					}
				}
				elseif ($this->fields[$i]["type"] == "tpl")
				{
					// Template-visning
					$tmp = new tpl($this->tplprefix . "_form_tpl_" . $this->fields[$i]["name"]);
					$tmp->set("value2", $this->fields[$i]["value2"]);
				}
				elseif ($this->fields[$i]["type"] == "image")
				{
					// Image
					if ($show_confirm)
					{
						if (is_file($_document_root . $this->fields[$i]["value"]))
						{
							$tmp = new tpl($this->tplprefix . "_form_confirm_image");
							$tmp->set("time", time());
						}
						else
						{
							$tmp = new tpl($this->tplprefix . "_form_confirm_false");
						}
					}
					else
					{
						if (is_file($_document_root . $this->fields[$i]["value"]))
						{
							// Viser billede-eksempel
							$tmp = new tpl($this->tplprefix . "_form_image_example");
							$tmp->set("time", time());
						}
						else
						{
							// Viser upload-boks
							$tmp = new tpl($this->tplprefix . "_form_image");
						}					
					}
				}
				elseif ($this->fields[$i]["type"] == "file")
				{
					// File
					if ($show_confirm)
					{
						if (is_file($_document_root . $this->fields[$i]["value"]))
						{
							$tmp = new tpl($this->tplprefix . "_form_confirm_file");
							$tmp->set("size", number_format(filesize($_document_root . $this->fields[$i]["value"]) / 1024, 1, ",", "."));
							$tmp->set("realname", $this->values[$this->fields[$i]["name"] . "_realname"]);
						}
						else
						{
							$tmp = new tpl($this->tplprefix . "_form_confirm_false");
						}
					}
					else
					{
						if (is_file($_document_root . $this->fields[$i]["value"]))
						{
							// Viser fil
							$tmp = new tpl($this->tplprefix . "_form_file_example");
							$tmp->set("size", number_format(filesize($_document_root . $this->fields[$i]["value"]) / 1024, 1, ",", "."));
							$tmp->set("realname", $this->values[$this->fields[$i]["name"] . "_realname"]);
						}
						else
						{
							// Viser upload-boks
							$tmp = new tpl($this->tplprefix . "_form_file");
						}					
					}
				}
				elseif ($this->fields[$i]["type"] == "tab")
				{
					// Tab / faneblad
					if ($has_tab)
					{
						// Afslutter tidligere tab
						$tmp = new tpl($this->tplprefix . "_form_tab_div_footer");
						$html .= $tmp->html();
					}
					$tmp = new tpl($this->tplprefix . "_form_tab_div_header");
					$tmp->set("form_id", $this->form_id);
					$has_tab = true;
				}
				elseif ($this->fields[$i]["type"] == "new_column")
				{
					// Ny kolonne
					$tmp = new tpl($this->tplprefix . "_form_new_column");
				}
				else
				{
					// Hidden
					$tmp = new tpl($this->tplprefix . "_form_hidden");
				}
				// Skifter CSS-klasse til tabel felter
				$td_class++;
				if ($td_class >= count($this->td_classes)) $td_class = 0;
				$tmp->set("class", $this->td_classes[$td_class]);
				// Er feltet deaktiveret ?
				if (isset($this->fields[$i]["disabled"]) and $this->fields[$i]["disabled"])
				{
					$tmp->set("disabled", "disabled");
				}
				if (isset($this->fields[$i]["info"])) $tmp->set("info", $this->fields[$i]["info"]);
				if (isset($this->fields[$i]["size"])) $tmp->set("size", $this->fields[$i]["size"]);
				if (isset($this->fields[$i]["text"])) $tmp->set("text", $this->fields[$i]["text"]);
				if (isset($this->fields[$i]["name"])) $tmp->set("name", $this->fields[$i]["name"]);
				if ($this->fields[$i]["type"] <> "tpl")
				{
					if (!$show_confirm and $this->fields[$i]["type"] <> "textarea" or
						$show_confirm and ($this->fields[$i]["type"] == "wysiwyg" or $this->fields[$i]["type"] == "textarea")) // if ($this->fields[$i]["wysiwyg"] and !$show_confirm)
					{
						$tmp->set("value", $cnv->tagentities(htmlspecialchars($this->fields[$i]["value"])));
					}
					else
					{
						$tmp->set("value", $cnv->tagentities($this->fields[$i]["value"]));
					}
					if ($show_confirm)
					{
						if ($this->fields[$i]["wysiwyg"])
						{
							$tmp->set("value_visible", $cnv->tagentities($this->fields[$i]["value"]));
						}
						elseif ($this->fields[$i]["type"] != "select")
						{
							$tmp->set("value_visible", nl2br($cnv->tagentities(htmlspecialchars($this->fields[$i]["value"]))));
						}
					}
				}
				else
				{
					$tmp->set("value", $this->fields[$i]["value"]);
				}
				if (!$this->edit)
				{
					if (isset($this->fields[$i]["error"]))
					{
						$tmptpl = new tpl($this->fields[$i]["error"]);
						$tmperr = $tmptpl->html();
						
						$tmp->set("error", $tmperr);
						$messages["ERROR_FORM_" . strtoupper($this->fields[$i]["name"])] = $tmperr;
						if (isset($messages["ERROR_FORM"]) and $messages["ERROR_FORM"] != "") $messages["ERROR_FORM"] .= "\r\n";
						$messages["ERROR_FORM"] .= $tmperr;
					}
				}
				$tmp->set("form_id", $this->form_id);
				$tmp->set("cols", $this->fields[$i]["cols"]);
				$tmp->set("rows", $this->fields[$i]["rows"]);
				$html .= $tmp->html();
			}
			// Tjekker om vi har gangi en tab
			if ($has_tab)
			{
				// Afslutter tidligere tab
				$tmp = new tpl($this->tplprefix . "_form_tab_div_footer");
				$html .= $tmp->html();
			}
			// Henter footer
			if ($show_confirm)
			{
				$tmp = new tpl($this->tplprefix . "_form_footer_confirm");
			}
			else
			{
				$tmp = new tpl($this->tplprefix . "_form_footer");
			}
			$tmp->set("submit_text", $this->submit_text);
			$tmp->set("confirm_text", $this->confirm_text);
			$tmp->set("edit_text", $this->edit_text);
			$tmp->set("form_id", $this->form_id);
			
			// Tjekker om vi benytter tabs
			if (count($this->tabs) > 0)
			{
				// Viser alle tabs
				$tmp1 = new tpl($this->tplprefix . "_form_tab_footer");
				$tmp1->set("form_id", $this->form_id);
				$tmp1->set("active_tab", $active_tab);
				$tmp->set("tab", $tmp1->html());
			}
			
			// Gemmes der via iframe ?
			$tmp->set("iframe_width", $this->iframe_save ? "100%" : 0);
			$tmp->set("iframe_height", $this->iframe_save ? 20 : 0);
			$html .= $tmp->html();
			
			// Returnerer HTML
			return $html;
		}
		
		// Tjekker om formularen er korrekt udfyldt
		function check_form()
		{
			if (!$this->submitted)
			{
				return false;
			}
			// Indtastede værdier
			$values = $this->values;
			// Gennemløber felter og finder evt. fejl i indtastninger
			$no_errors = true;
			for ($i = 0; $i < count($this->fields); $i++)
			{
				// Tjekker om der allerede er en fejl
				if ($this->fields[$i]["error"] <> "")
				{
					$no_errors = false;
					if ($this->fields[$i]["tab_id"] > 0) $this->tabs[$this->fields[$i]["tab_id"]]["error"] = true;
				}
				// Laver ereg-tjek
				if ($this->fields[$i]["ereg"] <> "" and $this->fields[$i]["error"] == "")
				{
					if (!ereg($this->fields[$i]["ereg"], $values[$this->fields[$i]["name"]]))
					{
						// Fejl
						$this->fields[$i]["error"] = $this->fields[$i]["ereg_error"];
						$no_errors = false;
						if ($this->fields[$i]["tab_id"] > 0) $this->tabs[$this->fields[$i]["tab_id"]]["error"] = true;
					}
				}
				// Laver PHP-tjek
				if ($this->fields[$i]["php"] <> "" and $this->fields[$i]["error"] == "")
				{
					$error = "";
					eval($this->fields[$i]["php"]);
					if ($error <> "")
					{
						// Fejl
						$this->fields[$i]["error"] = $error;
						$no_errors = false;
						if ($this->fields[$i]["tab_id"] > 0) $this->tabs[$this->fields[$i]["tab_id"]]["error"] = true;
					}
				}
			}
			return $no_errors;
		}
		
		// Returnerer om formularen er udfyldt korrekt
		function done()
		{
			return $this->check_form() and ($this->confirmed or !$this->confirm_form);
		}
		
		// Sletter midlertidige filer
		function cleanup()
		{
			// Globale variabler
			global $_document_root;
			for ($i = 0; $i < count($this->fields); $i++)
			{
				// Tjekker om det er et billede
				if ($this->fields[$i]["type"] == "image")
				{
					@unlink($_document_root . $this->fields[$i]["value"]);
				}
				if ($this->fields[$i]["type"] == "file")
				{
					@unlink($_document_root . $this->fields[$i]["value"]);
				}
				if (in_array($this->fields[$i]["type"], array("image", "textarea", "input", "password")))
				{
					$this->fields[$i]["value"] = "";
				}
			}
			$this->submitted = false;
			clearstatcache();
		}
		
		// Angiver om textarea skal være WYSIWYG
		function wysiwyg($wysiwyg = false)
		{
			$this->wysiwyg = $wysiwyg;
		}
		
		// Angiver CSS til wysiwyg-editor
		function wysiwyg_css($css = "default")
		{
			$this->wysiwyg_css = $css;
		}
		
		// Angiver dir til WYSIWYG
		function wysiwyg_dir($wysiwyg_dir = "/X/")
		{
			$this->wysiwyg_dir = $wysiwyg_dir;
		}
		
		// Angiver CSS til wysiwyg-editor
		function wysiwyg_prefix($wysiwyg_prefix = "")
		{
			$this->wysiwyg_prefix = $wysiwyg_prefix;
		}
		
		// Angiver om textarea skal være WYSIWYG
		function wysiwyg_upload($wysiwyg_upload = false, $wysiwyg_upload_shared = true)
		{
			$this->wysiwyg_upload = $wysiwyg_upload;
			$this->wysiwyg_upload_shared = $wysiwyg_upload_shared;
		}
	}
?>