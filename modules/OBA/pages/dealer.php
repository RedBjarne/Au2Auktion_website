<?php

	// Forhandler
	require_once($_document_root . "/modules/$module/inc/functions.php");

	// ID må gerne indehold et bogstav
	if (!preg_match("/^[A-Z]{1}[0-9]+$/", $vars["id"]))
	{
		$id = "";
	}
	else
	{
		$id = $vars["id"];
	}
	
	$usr = new user($module . "_cust");
	
	if (!$usr->logged_in)
	{
		if (!isset($vars["when_done"])) $vars["when_done"] = $_SERVER["REQUEST_URI"];
		$do = "login";
	}
	if ($usr->logged_in and in_array($do, "login", "")) $do = "auctions_overview";
	if ($usr->logged_in)
	{
		$tmp = new tpl("MODULE|$module|dealer_menu");
		$html .= $tmp->html();
	}
	
	if ($do == "login")
	{
		$html .= "{MODULE|$module|user|login}";
		
		/*
		$msg = new message;
		$msg->title("Log ind");
		$html .= $msg->html();
		
		$frm = new form;
		$frm->hidden("when_done", $vars["when_done"]);
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
				if ($vars["when_done"] != "") 
				{
					header("Location: " . $vars["when_done"]);
				}
				else
				{
					header("Location: /site/$_lang_id/$module/$page/auctions_overview");
				}
				exit;
			}
			$msg = new message;
			$msg->type("error");
			$msg->title("Fejl i brugernavn eller password");
			$html .= $msg->html();
			$frm->cleanup();
		}
		
		$html .= $frm->html();
		*/
	}
	elseif ($do == "logout")
	{
		$usr->logout();
		
		header("Location: /site/$_lang_id/$module/$page");
		exit;
	}
	elseif ($do == "auctions_add" or $do == "auctions_edit")
	{
		// Opret auktion
		
		$ajax = new ajax;
		if ($ajax->do == "lookup_regno")
		{
			$response = OBA_regno_lookup($ajax->values["regno"]);
			$response["state"] = "ok";
			$ajax->response($response);
		}
		
		if ($do == "auctions_edit")
		{
			$db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_auctions
				WHERE
					id = '$id' AND
					seller_id = '" . $usr->user_id . "' AND
					(
						auction_date > '" . date("Y-m-d") . "'
						OR
						NOT ISNULL(start_time) AND
						start_time > '" . date("Y-m-d H:i:s") . "'
						OR
						cancel = 1
						OR
						NOT ISNULL(end_time) AND
						end_time < '" . date("Y-m-d H:i:s") . "' AND
						(
							cur_price = 0 OR
							cur_price < min_price
						)
					)
				");
			if (!$res = $db->fetch_array())
			{
				header("Location: /site/$_lang_id/$module/$page/auctions_overview");
				exit;
			}
		}
		
		{
			// Rediger
			
			$frm = new form;
			
			$frm->tplprefix("MODULE|$module|dealer_auction_edit"); // Prefiks til alle formular felter
			
			$frm->tpl("th", $do == "auctions_add" ? "Vælg auktionstype" : "Rediger auktion");
			
			$dates = "";
			if ($do == "auctions_add")
			{
				// Auktions-type
				$frm->select(
					"Auktions-type",
					"auction_type",
					"live",
					"^(live|online)$",
					"Påkrævet",
					"",
					array(
						/*array("live", "Fysisk auktion"),*/
						array("online", "Online auktion")
						)
					);
				
				// Henter alle gyldige datoer for fysisk auktion
				$date = "";
				/*
				$db->execute("
					SELECT
						`date`
					FROM
						" . $_table_prefix . "_module_" . $module . "_dates
					WHERE
						`date` > '" . date("Y-m-d") . "'
					ORDER BY
						`date`
					");
				while ($db->fetch_array())
				{
					if ($dates != "")
					{
						$dates .= ",";
					}
					else
					{
						$date = date("d-m-Y", strtotime($db->array["date"]));
					}
					$dates .= $db->array["date"];
				}
				
				$frm->input(
					"Auktionsdato",
					"auction_date_live",
					$date,
					$vars["auction_type"] == "live" ? "^[0-9]{2}-[0-9]{2}-[0-9]{4}$" : "",
					"Ugyldig dato - format: dd-mm-åååå",
					$vars["auction_type"] == "live" ? '
						$db = new db;
						$cnv = new convert;
						if (!$db->execute_field("
							SELECT
								`date`
							FROM
								' . $_table_prefix . '_module_' . $module . '_dates
							WHERE
								`date` = \'" . $db->escape($cnv->date_dk2uk($this->values["auction_date_live"])) . "\'
							"))
						{
							$error = "Auktionsdato findes ikke";
						}
					' : ""
					);
				*/
					
				// Henter mulige auktionsdatoer for online
				$sel_online_days = array();
				for ($i = 1; $i <= 7; $i++)
				{
					$ts = strtotime("+" . $i . " day");
					$db->execute("
						SELECT
							time,
							duration
						FROM
							" . $_table_prefix . "_module_" . $module . "_online_days
						WHERE
							weekday = '" . date("w", $ts) . "'
							" . ($i == 0 ? " AND time > '" . date("H:i", $ts + 3600) . "' " : "") . "
						ORDER BY
							time
						");
					while ($db->fetch_array())
					{
						$ts_from = strtotime(date("Y-m-d", $ts) . " " . substr($db->array["time"], 0, 5));
						$ts_to = strtotime(date("Y-m-d H:i", $ts_from) . " +" . $db->array["duration"] . " hour");
						$sel_online_days[] = array($ts_from . "-" . $ts_to, date("d-m-Y H:i", $ts_from) . " til " . date("d-m-Y H:i", $ts_to));
					}
				}
				$frm->select(
					"Auktionsdato",
					"auction_date_online",
					$date,
					$vars["auction_type"] == "online" ? "^[0-9]+-[0-9]+$" : "",
					"Påkrævet",
					$vars["auction_type"] == "online" ? '
						list($ts_from, $ts_to) = explode("-", $this->values["auction_date_online"]);
						$ts_from = intval($ts_from);
						$ts_to = intval($ts_to);
						if ($ts_from >= $ts_to) $error = "Påkrævet";
						if (date("Y-m-d", $ts_from) <= date("Y-m-d")) $error = "Påkrævet";
					' : "",
					$sel_online_days
					);
			}
			else
			{
				$frm->hidden("auction_type", $res["auction_type"]);
				if ($res["auction_no"] == "")
				{
					$frm->tpl("td2", "Auktionsnr:", "<i>Endnu ikke tildelt</i>");
				}
				else
				{
					$frm->tpl("td2", "Auktionsnr:", $res["auction_no"]);
				}
				if ($res["auction_type"] == "online")
				{
					// Online
					$frm->tpl("td2", "Auktionsstart:", date("d-m-Y H:i", strtotime($res["start_time"])));
					$frm->tpl("td2", "Auktionsslut:", date("d-m-Y H:i", strtotime($res["end_time"])));
				}
				else
				{
					// Fysisk
					$frm->tpl("td2", "Auktionsdato:", date("d-m-Y", strtotime($res["auction_date"])));
				}
			}

			// ONLINE
			if ($res["auction_type"] == "online" or $do == "auctions_add")
			{
				// Grupper
				$select_groups = array();
				$db->execute("
					SELECT
						*
					FROM
						" . $_table_prefix . "_module_" . $module . "_groups
					WHERE
						CONCAT(',', types, ',') LIKE '%," . $db->escape($usr->data["extra_type"]) . ",%'
					ORDER BY
						title
					");
				while ($db->fetch_array())
				{
					$select_groups[] = array($db->array["id"], $db->array["title"]);
				}
			}
			
			// FYSISK
			if ($res["auction_type"] == "live" or $do == "auctions_add")
			{
				// Kategorier
				$select_categories = array();
				$db->execute("
					SELECT
						*
					FROM
						" . $_table_prefix . "_module_" . $module . "_categories
					ORDER BY
						`type`
					");
				while ($db->fetch_array())
				{
					$select_categories[] = array($db->array["id"], $db->array["type"] . " - " . $db->array["title"]);
				}
				
				// Typer
				$select_types = array();
				$db->execute("
					SELECT
						*
					FROM
						" . $_table_prefix . "_module_" . $module . "_types
					ORDER BY
						`title`
					");
				while ($db->fetch_array())
				{
					$select_types[] = array($db->array["id"], $db->array["title"]);
				}
				
				$frm->select(
					"Gruppe",
					"group_id",
					$res["group_id"],
					"",
					"",
					"",
					$select_groups
					);
				$frm->select(
					"Kategori",
					"category_id",
					$res["category_id"],
					"",
					"",
					"",
					$select_categories
					);
				$frm->select(
					"Auktionstype",
					"type_id",
					$res["type_id"],
					"",
					"",
					"",
					$select_types
					);
			}
			
				
			// KØRETØJSDETALJER
			$frm->tpl("th", "Køretøjsdetaljer");

			// Nummerplade			
			$tmp = new tpl("MODULE|$module|dealer_auction_edit_regno");
			$tmp->set("regno", $res["regno"]);
			$frm->tpl("td", $tmp->html());
			$frm->input(
				"Mærke",
				"brand",
				stripslashes($res["brand"]),
				"^.+$",
				"Påkrævet"
				);
			$frm->input(
				"Sidste syn",
				"newly_tested_date",
				$res["newly_tested_date"] != "" ? date("d-m-Y", strtotime($res["newly_tested_date"])) : "",
				"^([0-9]{2}-[0-9]{2}-[0-9]{4}){0,1}$",
				"Ugyldig dato - format: dd-mm-åååå"
				);
			$frm->select(
				"Døre",
				"doors",
				$res["doors"],
				"",
				"",
				"",
				OBA_list2select(module_setting("vars_doors"), $res["doors"])
				);
			$frm->input(
				"Model",
				"model",
				stripslashes($res["model"]),
				"^.+$",
				"Påkrævet"
				);
			$frm->select(
				"Geartype",
				"geartype",
				$res["geartype"],
				"",
				"",
				"",
				OBA_list2select(module_setting("vars_geartype"), $res["geartype"])
				);
			$frm->select(
				"Moms",
				"no_vat",
				$res["no_vat"],
				"",
				"",
				"",
				array(
					array("", "Vælg"),
					array("1", "Ekskl. Moms"),
					array("0", "Momsfri")
					)
				);
			$frm->input(
				"Variant",
				"variant",
				stripslashes($res["variant"])
				);
			$frm->select(
				"Gear",
				"gearcount",
				$res["gearcount"],
				"",
				"",
				"",
				OBA_list2select(module_setting("vars_gearcount"), $res["gearcount"])
				);
			$frm->select(
				"Afgift",
				"no_tax",
				$res["no_tax"],
				"",
				"",
				"",
				array(
					array("", "Vælg"),
					array("0", "Ja"),
					array("1", "Nej")
					)
				);
			$frm->select(
				"Årgang",
				"year",
				stripslashes($res["year"]),
				"^[0-9]{4}$",
				"Påkrævet",
				"",
				OBA_numberselect(date("Y"), 1950, $res["year"], 1, false)
				);
			$frm->input(
				"Hestekræfter",
				"hp",
				$res["hp"],
				"^[0-9]*$",
				"Skal være et tal"
				);
			$frm->checkbox(
				"Dokumentation for kilometerstand haves",
				"km_doc",
				$res["km_doc"] == 1
				);
			$frm->select(
				"Motorstørrelse",
				"motorsize",
				$res["motorsize"],
				"",
				"",
				"",
				OBA_list2select(module_setting("vars_motorsize"), $res["motorsize"])
				);
				$frm->input(
				"Km stand",
				"km",
			stripslashes($res["km"]),
				"^[0-9]*$",
				"Kun tal i dette felt"
				);
			$frm->select(
				"Hjultræk",
				"wheel_drive",
				$res["wheel_drive"],
				"",
				"",
				"",
				OBA_list2select(module_setting("vars_wheel_drive"), $res["wheel_drive"])
				);	
			$frm->select(
				"Brændstof",
				"fuel",
				stripslashes($res["fuel"]),
				"",
				"",
				"",
				OBA_list2select(module_setting("vars_fuel"), $res["fuel"])
				);
			$frm->select(
				"Type",
				"type",
				stripslashes($res["type"]),
				"",
				"",
				"",
				OBA_list2select(module_setting("vars_type"), $res["type"])
				);	
			$frm->select(
				"Farve",
				"color",
				$res["color"],
				"",
				"",
				"",
				OBA_list2select(module_setting("vars_color"), $res["color"])
				);
			$frm->input(
				"Stelnr.",
				"chasno",
				stripslashes($res["chasno"]),
				"^.+$",
				"Påkrævet"
				);
			$frm->select(
				"Status",
				"is_regged",
				$res["is_regged"],
				"",
				"",
				"",
				array(
					array("", "Vælg"),
					array("1", "Indregistreret"),
					array("0", "Afmeldt")
					)
				);
			$frm->input(
				"Nypris",
				"new_price",
				$res["new_price"],
				"^[0-9]*$",
				"Skal være et tal"
				);
			$frm->input(
				"1. Reg.",
				"first_reg_date",
				$res["first_reg_date"] != "" ? date("d-m-Y", strtotime($res["first_reg_date"])) : "",
				"^([0-9]{2}-[0-9]{2}-[0-9]{4}){0,1}$",
				"Ugyldig dato - format: dd-mm-åååå"
				);
			$frm->select(
				"Restgæld",
				"unpaid_debt",
				$res["unpaid_debt"],
				"",
				"",
				"",
				array(
					array("", "Vælg"),
					array("1", "Ja"),
					array("0", "Nej")
					)
				);
			$frm->input(
				"Mindstepris",
				"min_price",
				$res["min_price"],
				"^[0-9]*$",
				"Tal påkrævet"
				);

			
			/*
				Felter der ikke indgår i nyt design 08-05-2014 !?!
			
			$frm->checkbox(
				"Gul-plade",
				"yellow_plate",
				$res["yellow_plate"] == 1
				);
			$frm->checkbox(
				"Nysynet",
				"newly_tested",
				$res["newly_tested"] == 1
				);
			$frm->textarea(
				"Beskrivelse",
				"description",
				stripslashes($res["description"])
				);
			*/

			/*
				UDSTYR
			*/
			$frm->tpl("th", "Udstyr standard/ekstra");
			$html_equipment = "";
			$lines = explode("\n", module_setting("vars_equipment"));
			$count = 0;
			$res_equipment = unserialize($res["equipment"]);
			$vars_equipment = array(
				"equipment" => array(),
				"airbags" => $vars["equipment_airbags"],
				"comment" => $vars["equipment_comment"]
				);
			if ($frm->submitted) $res_equipment = $vars_equipment;
			for ($i = 0; $i < count($lines); $i++)
			{
				$line = trim($lines[$i]);
				if ($line != "")
				{
					$tmp = new tpl("MODULE|$module|dealer_auction_edit_equipment_checkbox");
					$tmp->set("title", $line);
					$tmp->set("name", "equipment_" . md5($line));
					$tmp->set("checked", ((in_array($line, $res_equipment["equipment"]) or $vars["equipment_" . $line] != "") ? "checked" : ""));
					$html_equipment .= $tmp->html();
						
					if ($vars["equipment_" . md5($line)] != "") $vars_equipment["equipment"][] = $line;
					
					$count++;
				}
			}
			$tmp = new tpl("MODULE|$module|dealer_auction_edit_equipment");
			$tmp->set("fields", $html_equipment);
			$frm->tpl("td", $tmp->html());
			$frm->select(
				"Antal airbags",
				"equipment_airbags",
				$res_equipment["airbags"],
				"",
				"",
				"",
				OBA_list2select(module_setting("vars_equipment_airbags"), $res_equipment["airbags"])
				);
			$frm->textarea(
				"Evt. bemærkninger",
				"equipment_comment",
				$res_equipment["comment"]
				);
				
				
				
			/*
				DÆK
			*/
			
			$frm->tpl("th", "Monteret dæk og fælge");
			$res_tires = unserialize($res["tires"]);
			$vars_tires = array(
				"type" => $vars["tires_type"],
				"rim" => $vars["tires_rim"],
				"depth_front" => $vars["tires_depth_front"],
				"depth_back" => $vars["tires_depth_back"],
				"type_extra" => $vars["tires_type_extra"],
				"rim_extra" => $vars["tires_rim_extra"],
				"depth_front_extra" => $vars["tires_depth_front_extra"],
				"depth_back_extra" => $vars["tires_depth_back_extra"]
				);
			if ($frm->submitted) $res_tires = $vars_tires;
			$frm->tpl("td", "
				<div class=\"createcar_page_content_tiresandrims_left_tiretype\"><div class=\"createcar_page_content_tiresandrims_left_tiretype_label\">Monteret dæk </div><select name=\"tires_type\" class=\"createcar_page_content_tiresandrims_left_tiretype_field\">" . OBA_list2select_html(module_setting("vars_tires_type"), $res_tires["type"]) . "</select></div>
<div class=\"createcar_page_content_tiresandrims_left_rims\"><div class=\"createcar_page_content_tiresandrims_left_rims_label\">Monteret fælge </div><select name=\"tires_rim\" class=\"createcar_page_content_tiresandrims_left_rims_field\">" . OBA_list2select_html(module_setting("vars_tires_rim"), $res_tires["rim"]) . "</select></div>
<div class=\"createcar_page_content_tiresandreartires_left_fronttires\"><div class=\"createcar_page_content_tiresandreartires_left_fronttires_label\">Mønster fordæk </div><select name=\"tires_depth_front\" class=\"createcar_page_content_tiresandreartires_left_fronttires_field\">" . OBA_list2select_html(module_setting("vars_tires_depth"), $res_tires["depth_front"]) . "</select></div>
<div class=\"createcar_page_content_tiresandreartires_left_reartires\"><div class=\"createcar_page_content_tiresandreartires_left_reartires_label\">Mønster bagdæk </div><select name=\"tires_depth_back\" class=\"createcar_page_content_tiresandreartires_left_reartires_field\">" . OBA_list2select_html(module_setting("vars_tires_depth"), $res_tires["depth_back"]) . "</select></div>
<div class=\"createcar_page_content_tiresandsparetires_spacer\"></div>
<div class=\"createcar_page_content_tiresandrims_right_extratiretype\"><div class=\"createcar_page_content_tiresandrims_right_extratiretype_label\">Ekstra dæk </div><select name=\"tires_type_extra\" class=\"createcar_page_content_tiresandrims_right_extratiretype_field\">" . OBA_list2select_html(module_setting("vars_tires_type"), $res_tires["type_extra"]) . "</select></div>
<div class=\"createcar_page_content_tiresandrims_right_extrarims\"><div class=\"createcar_page_content_tiresandrims_right_extrarims_label\">Ekstra fælge </div><select name=\"tires_rim_extra\" class=\"createcar_page_content_tiresandrims_right_extrarims_field\">" . OBA_list2select_html(module_setting("vars_tires_rim"), $res_tires["rim_extra"]) . "</select></div>
<div class=\"createcar_page_content_tiresandreartires_right_extrafronttires\"><div class=\"createcar_page_content_tiresandreartires_right_extrafronttires_label\">Mønster fordæk</div> <select name=\"tires_depth_front_extra\" class=\"createcar_page_content_tiresandreartires_right_extrafronttires_field\">" . OBA_list2select_html(module_setting("vars_tires_depth"), $res_tires["depth_front_extra"]) . "</select></div>
<div class=\"createcar_page_content_tiresandreartires_right_extrareartires\"><div class=\"createcar_page_content_tiresandreartires_right_extrareartires_label\">Mønster bagdæk</div><select name=\"tires_depth_back_extra\" class=\"createcar_page_content_tiresandreartires_right_extrareartires_field\">" . OBA_list2select_html(module_setting("vars_tires_depth"), $res_tires["depth_back_extra"]) . "</select></div>
				");
			
			
			
			
			/*
				STAND
			*/
			$frm->tpl("th", "Stand");
			$res_condition = unserialize($res["condition"]);
			$vars_condition = array(
				"inside" => $vars["condition_inside"],
				"mecanical" => $vars["condition_mecanical"],
				"lacquer" => $vars["condition_lacquer"],
				"light_front" => $vars["condition_light_front"],
				"light_back" => $vars["condition_light_back"],
				"light_fog" => $vars["condition_light_fog"],
				"damage" => $vars["condition_damage"],
				"electric" => $vars["condition_electric"]
				);
			if ($frm->submitted) $res_condition = $vars_condition;
			$frm->tpl("td", "
				<div class=\"createcar_page_content_condition_form_conditions\"><div class=\"createcar_page_content_condition_form_conditions_label\">Indvendig stand</div> <select name=\"condition_inside\" class=\"createcar_page_content_condition_form_conditions_field\">" . OBA_list2select_html(module_setting("vars_condition_inside"), $res_condition["inside"]) . "</select></div>
				<div class=\"createcar_page_content_condition_form_conditions\"><div class=\"createcar_page_content_condition_form_conditions_label\">Mekanisk stand</div> <select name=\"condition_mecanical\" class=\"createcar_page_content_condition_form_conditions_field\">" . OBA_list2select_html(module_setting("vars_condition_mecanical"), $res_condition["mecanical"]) . "</select></div>
				<div class=\"createcar_page_content_condition_form_conditions\"><div class=\"createcar_page_content_condition_form_conditions_label\">Lak stand</div> <select name=\"condition_lacquer\" class=\"createcar_page_content_condition_form_conditions_field\">" . OBA_list2select_html(module_setting("vars_condition_lacquer"), $res_condition["lacquer"]) . "</select></div>
				<div class=\"createcar_page_content_condition_form_conditions\"><div class=\"createcar_page_content_condition_form_conditions_label\">Forlygter</div> <select name=\"condition_light_front\" class=\"createcar_page_content_condition_form_conditions_field\">" . OBA_list2select_html(module_setting("vars_condition_light_front"), $res_condition["light_front"]) . "</select></div>
				<div class=\"createcar_page_content_condition_form_conditions\"><div class=\"createcar_page_content_condition_form_conditions_label\">Baglygter</div> <select name=\"condition_light_back\" class=\"createcar_page_content_condition_form_conditions_field\">" . OBA_list2select_html(module_setting("vars_condition_light_back"), $res_condition["light_back"]) . "</select></div>
				<div class=\"createcar_page_content_condition_form_conditions\"><div class=\"createcar_page_content_condition_form_conditions_label\">Tågelygter</div> <select name=\"condition_light_fog\" class=\"createcar_page_content_condition_form_conditions_field\">" . OBA_list2select_html(module_setting("vars_condition_light_fog"), $res_condition["light_fog"]) . "</select></div>
				<div class=\"createcar_page_content_condition_form_conditions\"><div class=\"createcar_page_content_condition_form_conditions_label\">Tidligere skadet</div> <select name=\"condition_damage\" class=\"createcar_page_content_condition_form_conditions_field\">" . OBA_list2select_html(module_setting("vars_condition_damage"), $res_condition["damage"]) . "</select></div>
				<div class=\"createcar_page_content_condition_form_conditions\"><div class=\"createcar_page_content_condition_form_conditions_label\">Elektrisk stand</div> <select name=\"condition_electric\" class=\"createcar_page_content_condition_form_conditions_field\">" . OBA_list2select_html(module_setting("vars_condition_electric"), $res_condition["electric"]) . "</select></div>
				");
			
			
			/*
				VEDLIGEHOLDELSE
			*/
			$frm->tpl("th", "Vedligeholdelse");
			$res_maintain = unserialize($res["maintain"]);
			$vars_maintain = array(
				"book" => $vars["maintain_book"],
				"service_ok" => $vars["maintain_service_ok"],
				"rust_treat" => $vars["maintain_rust_treat"],
				"brake" => $vars["maintain_brake"],
				"last_service" => $vars["maintain_last_service"],
				"next_service" => $vars["maintain_next_service"],
				"timing_belt" => $vars["maintain_timing_belt"],
				"oil" => $vars["maintain_oil"],
				"comment" => $vars["maintain_comment"]
				);
			if ($frm->submitted) $res_maintain = $vars_maintain;
			$frm->tpl("td", "");
			$frm->tpl("td", "
				<div class=\"createcar_page_content_maintenance_form_maintenances\"><div class=\"createcar_page_content_maintenance_form_maintenances_label\">Medfølger servicebog</div> <select name=\"maintain_book\" class=\"createcar_page_content_maintenance_form_maintenances_field\">" . OBA_list2select_html(module_setting("vars_maintain_book"), $res_maintain["book"]) . "</select></div>
				<div class=\"createcar_page_content_maintenance_form_maintenances\"><div class=\"createcar_page_content_maintenance_form_maintenances_label\">Service overholdt</div> <select name=\"maintain_service_ok\" class=\"createcar_page_content_maintenance_form_maintenances_field\">" . OBA_list2select_html(module_setting("vars_maintain_service_ok"), $res_maintain["service_ok"]) . "</select></div>
				<div class=\"createcar_page_content_maintenance_form_maintenances\"><div class=\"createcar_page_content_maintenance_form_maintenances_label\">Undervognsbehandlet</div> <select name=\"maintain_rust_treat\" class=\"createcar_page_content_maintenance_form_maintenances_field\">" . OBA_list2select_html(module_setting("vars_maintain_rust_treat"), $res_maintain["rust_treat"]) . "</select></div>
				<div class=\"createcar_page_content_maintenance_form_maintenances\"><div class=\"createcar_page_content_maintenance_form_maintenances_label\">Bremseklodser skiftet</div> <select name=\"maintain_brake\" class=\"createcar_page_content_maintenance_form_maintenances_field\">" . 
					OBA_list2select_html(module_setting("vars_maintain_brake"), $res_maintain["brake"]) .
					OBA_numberselect_html(1000, 1000000, $res_maintain["brake"], 1000, true, " km", true) . 
					"</select></div>
				<div class=\"createcar_page_content_maintenance_form_maintenances\"><div class=\"createcar_page_content_maintenance_form_maintenances_label\">Sidste service</div> <select name=\"maintain_last_service\" class=\"createcar_page_content_maintenance_form_maintenances_field\">" . 
					OBA_list2select_html(module_setting("vars_maintain_last_service"), $res_maintain["last_service"]) .
					OBA_numberselect_html(1000, 1000000, $res_maintain["last_service"], 1000, true, " km", true) . 
					"</select></div>
				<div class=\"createcar_page_content_maintenance_form_maintenances\"><div class=\"createcar_page_content_maintenance_form_maintenances_label\">Næste service</div> <select name=\"maintain_next_service\" class=\"createcar_page_content_maintenance_form_maintenances_field\">" . 
					OBA_list2select_html(module_setting("vars_maintain_next_service"), $res_maintain["next_service"]) .
					OBA_numberselect_html(1000, 1000000, $res_maintain["next_service"], 1000, true, " km", true) . 
					"</select></div>
				<div class=\"createcar_page_content_maintenance_form_maintenances\"><div class=\"createcar_page_content_maintenance_form_maintenances_label\">Tandrem skiftet</div> <select name=\"maintain_timing_belt\" class=\"createcar_page_content_maintenance_form_maintenances_field\">" . 
					OBA_list2select_html(module_setting("vars_maintain_timing_belt"), $res_maintain["timing_belt"]) .
					OBA_numberselect_html(1000, 1000000, $res_maintain["timing_belt"], 1000, true, " km", true) . 
					"</select></div>
				<div class=\"createcar_page_content_maintenance_form_maintenances\"><div class=\"createcar_page_content_maintenance_form_maintenances_label\">Olie skiftet</div> <select name=\"maintain_oil\" class=\"createcar_page_content_maintenance_form_maintenances_field\">" . 
					OBA_list2select_html(module_setting("vars_maintain_oil"), $res_maintain["oil"]) .
					OBA_numberselect_html(1000, 1000000, $res_maintain["oil"], 1000, true, " km", true) . 
					"</select></div>
				");
			$frm->textarea(
				"Evt. bemærkninger",
				"maintain_comment",
				$res_maintain["comment"]
				);
			
			
			
			
			/*
				UDVENDIG STAND
			*/
			$frm->tpl("th", "Udvendig stand");
			$res_exterior = unserialize($res["exterior"]);
			$arr_fields = array(
			
				/* FORAN */
			
				array(
					"windshield",
					"Forrude",
					array(
						array("windshield", "Stand", "windshield")
						)
					),
					
				array(
					"hood",
					"Motorhjelm",
					array(
						array("hood_dent", "Buler", "dent"),
						array("hood_scratch", "Ridser", "scratch"),
						array("hood_rust", "Rust", "rust"),
						array("hood_stone", "Stenslag", "stone")
						)
					),
						
				array(
					"bumper_front",
					"Forkofanger",
					array(
						array("bumper_front", "Stand", "condition")
						)
					),
					
				/* HØJRE */
						
				array(
					"fender_front_right",
					"Højre forskærm",
					array(
						array("fender_front_right_dent", "Buler", "dent"),
						array("fender_front_right_scratch", "Ridser", "scratch"),
						array("fender_front_right_rust", "Rust", "rust"),
						array("fender_front_right_stone", "Stenslag", "stone")
						)
					),
						
				array(
					"door_front_right",
					"Højre fordør",
					array(
						array("door_front_right_dent", "Buler", "dent"),
						array("door_front_right_scratch", "Ridser", "scratch"),
						array("door_front_right_rust", "Rust", "rust"),
						array("door_front_right_stone", "Stenslag", "stone")
						)
					),
						
				array(
					"door_back_right",
					"Højre bagdør",
					array(
						array("door_back_right_dent", "Buler", "dent"),
						array("door_back_right_scratch", "Ridser", "scratch"),
						array("door_back_right_rust", "Rust", "rust"),
						array("door_back_right_stone", "Stenslag", "stone")
						)
					),
						
				array(
					"fender_back_right",
					"Højre bagdør",
					array(
						array("fender_back_right_dent", "Buler", "dent"),
						array("fender_back_right_scratch", "Ridser", "scratch"),
						array("fender_back_right_rust", "Rust", "rust"),
						array("fender_back_right_stone", "Stenslag", "stone")
						)
					),
					
				array(
					"mirror_right",
					"Højre sidespejl",
					array(
						array("mirror_right_scratch", "Ridser", "mirror_scratch"),
						array("mirror_right_stone", "Stenslag", "mirror_stone"),
						array("mirror_right_glass", "Spejlglas", "mirror_glass")
						)
					),
				
				array(
					"panel_right",
					"Højre dørpanel",
					array(
						array("panel_right_dent", "Buler", "dent"),
						array("panel_right_rust", "Rust", "rust"),
						array("panel_right_stone", "Stenslag", "stone")
						)
					),
					
				/* VENSTRE */
						
				array(
					"fender_front_left",
					"Venstre forskærm",
					array(
						array("fender_front_left_dent", "Buler", "dent"),
						array("fender_front_left_scratch", "Ridser", "scratch"),
						array("fender_front_left_rust", "Rust", "rust"),
						array("fender_front_left_stone", "Stenslag", "stone")
						)
					),
						
				array(
					"door_front_left",
					"Venstre fordør",
					array(
						array("door_front_left_dent", "Buler", "dent"),
						array("door_front_left_scratch", "Ridser", "scratch"),
						array("door_front_left_rust", "Rust", "rust"),
						array("door_front_left_stone", "Stenslag", "stone")
						)
					),
						
				array(
					"door_back_left",
					"Venstre bagdør",
					array(
						array("door_back_left_dent", "Buler", "dent"),
						array("door_back_left_scratch", "Ridser", "scratch"),
						array("door_back_left_rust", "Rust", "rust"),
						array("door_back_left_stone", "Stenslag", "stone")
						)
					),
						
				array(
					"fender_back_left",
					"Venstre bagdør",
					array(
						array("fender_back_left_dent", "Buler", "dent"),
						array("fender_back_left_scratch", "Ridser", "scratch"),
						array("fender_back_left_rust", "Rust", "rust"),
						array("fender_back_left_stone", "Stenslag", "stone")
						)
					),
					
				array(
					"mirror_left",
					"Venstre sidespejl",
					array(
						array("mirror_left_scratch", "Ridser", "mirror_scratch"),
						array("mirror_left_stone", "Stenslag", "mirror_stone"),
						array("mirror_left_glass", "Spejlglas", "mirror_glass")
						)
					),
				
				array(
					"panel_left",
					"Venstre dørpanel",
					array(
						array("panel_left_dent", "Buler", "dent"),
						array("panel_left_rust", "Rust", "rust"),
						array("panel_left_stone", "Stenslag", "stone")
						)
					),
					
				/* BAG */
				
				array(
					"bumper_back",
					"Bagkofanger",
					array(
						array("bumper_back", "Stand", "condition")
						)
					),
					
				array(
					"door_back",
					"Bagklap",
					array(
						array("door_back_dent", "Buler", "dent"),
						array("door_back_rust", "Rust", "rust"),
						array("door_back_stone", "Stenslag", "stone")
						)
					),
					
				/* TAG */
				
				array(
					"roof",
					"Tag",
					array(
						array("roof_dent", "Buler", "dent"),
						array("roof_rust", "Rust", "rust"),
						array("roof_stone", "Stenslag", "stone")
						)
					),
				
				/* UNDERVOGN */
						
				array(
					"under",
					"Undervogn",
					array(
						array("under", "Rust", "rust"),
						)
					)
					
				);
			$vars_exterior = array();
			$html_exterior = "";
			for ($i = 0; $i < count($arr_fields); $i++)
			{
				list($name, $title, $fields) = $arr_fields[$i];
				
				$html_exterior .= "<div class=\"exteriorRow\"><div class=\"exteriorTitle\">$title<br>" .
					"<img src=\"/modules/$module/img/" . $name . ".png\" /></div><div class=\"exteriorColumn\">";
				
				$count = 0;
				for ($j = 0; $j < count($fields); $j++)
				{
					list($fieldname, $fieldtitle, $fieldsource) = $fields[$j];
					
					if ($count >= count($fields) / 2 and $count > 0)
					{
						$html_exterior .= "</div><div class=\"exteriorColumn\">";
						$count = 0;
					}
					
					$vars_exterior[$fieldname] = $vars["exterior_" . $fieldname];
					if ($frm->submitted) $res_exterior[$fieldname] = $vars["exterior_" . $fieldname];
					
					$html_exterior .= "<div class=\"exteriorCell\"><div class=\"exteriorCell_fieldtitle\">$fieldtitle</div> <select name=\"exterior_" . $fieldname . "\">" .
						OBA_list2select_html(module_setting("vars_exterior_" . $fieldsource), $res_exterior[$fieldname]) . "</select></div>";
					
					$count++;
				}
				
				$html_exterior .= "</div><div class=\"clear\"></div></div>";
			}
			$frm->tpl("td", $html_exterior);
				
				
			
			/*
				BILLEDER
			*/
			$frm->tpl("th", "Billeder");
			$tmphtml = "";
			$ressimages = $db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_images
				WHERE
					auction_id = '$id'
				ORDER BY
					`order`
				");
			while ($resimage = $db->fetch_array($ressimages))
			{
				$tmphtml .= "<div style=\"float: left; width: 100px; height: 120px; margin: 10px; text-align: center; border: 1px solid #a0a0a0;\">" .
					"<div style=\"width: 100px; height: 100px; background-image: url(/modules/$module/upl/image_" . $resimage["id"] . "_thumb.jpg); " .
					"background-size: contain; background-position: 50% 50%; background-repeat: no-repeat;\"></div>" .
					"<input type=\"checkbox\" name=\"delete_image_" . $resimage["id"] . "\" style=\"width: 15px; height: 15px;\"> Slet" .
					"</div>";
			}
			$frm->tpl("td", $tmphtml);
			
			$frm->enctype = "multipart/form-data";
			$frm->tpl("td", "Upload JPG-billeder, max " . ini_get("max_file_uploads") . " billeder á max " . ini_get("upload_max_filesize") . "b: " .
				"<input type=\"file\" name=\"upload_image[]\" multiple>");
				
			if ($frm->done())
			{
				$cnv = new convert;
				if ($do == "auctions_add")
				{
					// Opretter auktion
					$id = OBA_id();
					
					// Dato
					if ($frm->values["auction_type"] == "online")
					{
						// Online
						list($ts_from, $ts_to) = explode("-", $frm->values["auction_date_online"]);
						$ts_from = intval($ts_from);
						$ts_to = intval($ts_to);
						$auction_date = date("Y-m-d", $ts_from);
						$start_time = "'" . date("Y-m-d H:i", $ts_from) . "'";
						$end_time = "'" . date("Y-m-d H:i", $ts_to) . "'";
					}
					else
					{
						// Fysisk
						$auction_date = $db->escape($cnv->date_dk2uk($frm->values["auction_date_live"]));
						$start_time = "NULL";
						$end_time = "NULL";
					}
					
					$sql = "
						INSERT INTO
							" . $_table_prefix . "_module_" . $module . "_auctions
						(
							`id`, 
							`auction_date`,
							`regno`,
							`chasno`,
							`brand`,
							`model`,
							`variant`,
							`type`,
							`fuel`,
							`doors`,
							`year`,
							`km`,
							`color`,
							no_vat,
							no_tax,
							yellow_plate,
							`newly_tested`,
							`newly_tested_date`,
							`is_regged`,
							`description`,
							`min_price`,
							seller_id,
							seller_type,
							seller_name,
							seller_address,
							seller_zipcode,
							seller_city,
							seller_phone,
							seller_email,
							seller_vat,
							seller_bank_regno,
							seller_bank_account,
							category_id,
							type_id,
							first_reg_date,
							
							`start_time`,
							`end_time`,
							`auction_type`,
							`unpaid_debt`,
							`equipment`,
							`tires`,
							`condition`,
							`maintain`,
							`exterior`,
							`motorsize`,
							`hp`,
							`geartype`,
							`gearcount`,
							`new_price`,
							`wheel_drive`,
							`km_doc`,
							
							group_id
						)
						VALUES
						(
							'$id',
							'" . $db->escape($auction_date) . "',
							'" . $db->escape($frm->values["regno"]) . "',
							'" . $db->escape($frm->values["chasno"]) . "',
							'" . $db->escape($frm->values["brand"]) . "',
							'" . $db->escape($frm->values["model"]) . "',
							'" . $db->escape($frm->values["variant"]) . "',
							'" . $db->escape($frm->values["type"]) . "',
							'" . $db->escape($frm->values["fuel"]) . "',					
							'" . $db->escape($frm->values["doors"]) . "',
							'" . $db->escape($frm->values["year"]) . "',
							'" . $db->escape($frm->values["km"]) . "',
							'" . $db->escape($frm->values["color"]) . "',
							'" . $db->escape($frm->values["no_vat"] != "" ? 1 : 0) . "',
							'" . $db->escape($frm->values["no_tax"] != "" ? 1 : 0) . "',
							'" . $db->escape($frm->values["yellow_plate"] != "" ? 1 : 0) . "',
							'" . $db->escape($frm->values["newly_tested"] != "" ? 1 : 0) . "',
							" . ($frm->values["newly_tested_date"] != "" ? date("'Y-m-d'", strtotime($cnv->date_dk2uk($frm->values["newly_tested_date"]))) : "NULL") . ",
							'" . $db->escape($frm->values["is_regged"] != "" ? 1 : 0) . "',
							'" . $db->escape($frm->values["description"]) . "',
							'" . $db->escape($frm->values["min_price"]) . "',
							'" . $db->escape($usr->data["id"]) . "',
							'" . $db->escape($usr->data["extra_type"]) . "',
							'" . $db->escape($usr->data["name"]) . "',
							'" . $db->escape($usr->data["address"]) . "',
							'" . $db->escape($usr->data["zipcode"]) . "',
							'" . $db->escape($usr->data["city"]) . "',
							'" . $db->escape($usr->data["phone"]) . "',
							'" . $db->escape($usr->data["email"]) . "',
							'" . $db->escape($usr->data["vat"]) . "',
							'" . $db->escape($usr->data["bank_regno"]) . "',
							'" . $db->escape($usr->data["bank_account"]) . "',
							'" . $db->escape($frm->values["category_id"]) . "',
							'" . $db->escape($frm->values["type_id"]) . "',
							" . ($frm->values["first_reg_date"] != "" ? date("'Y-m-d'", strtotime($cnv->date_dk2uk($frm->values["first_reg_date"]))) : "NULL") . ",
							
							$start_time,
							$end_time,
							'" . $db->escape($frm->values["auction_type"]) . "',
							'" . ($frm->values["unpaid_debt"] != "" ? 1 : 0) . "',
							'" . $db->escape(serialize($vars_equipment)) . "',
							'" . $db->escape(serialize($vars_tires)) . "',
							'" . $db->escape(serialize($vars_condition)) . "',
							'" . $db->escape(serialize($vars_maintain)) . "',
							'" . $db->escape(serialize($vars_exterior)) . "',
							'" . $db->escape($frm->values["motorsize"]) . "',
							'" . $db->escape($frm->values["hp"]) . "',
							'" . $db->escape($frm->values["geartype"]) . "',
							'" . $db->escape($frm->values["gearcount"]) . "',
							" . (is_numeric($frm->values["new_price"]) ? intval($frm->values["new_price"]) : "NULL") . ",
							'" . $db->escape($frm->values["wheel_drive"]) . "',
							'" . $db->escape($frm->values["km_doc"] != "" ? 1 : 0) . "',
							
							'" . intval($frm->values["group_id"]) . "'
						)
						";
					$db->execute($sql);
					OBA_sync("SQL", $sql);
				}
				else
				{
					$sql = "
						UPDATE
							" . $_table_prefix . "_module_" . $module . "_auctions
						SET
							regno = '" . $db->escape($frm->values["regno"]) . "',
							chasno = '" . $db->escape($frm->values["chasno"]) . "',
							brand = '" . $db->escape($frm->values["brand"]) . "',
							model = '" . $db->escape($frm->values["model"]) . "',
							`variant` = '" . $db->escape($frm->values["variant"]) . "',
							`type` = '" . $db->escape($frm->values["type"]) . "',
							fuel = '" . $db->escape($frm->values["fuel"]) . "',					
							doors = '" . $db->escape($frm->values["doors"]) . "',
							no_vat = '" . $db->escape($frm->values["no_vat"] != "" ? 1 : 0) . "',
							no_tax = '" . $db->escape($frm->values["no_tax"] != "" ? 1 : 0) . "',
							yellow_plate = '" . $db->escape($frm->values["yellow_plate"] != "" ? 1 : 0) . "',
							`year` = '" . $db->escape($frm->values["year"]) . "',
							km = '" . $db->escape($frm->values["km"]) . "',
							color = '" . $db->escape($frm->values["color"]) . "',
							newly_tested = '" . $db->escape($frm->values["newly_tested"] != "" ? 1 : 0) . "',
							newly_tested_date = " . ($frm->values["newly_tested_date"] != "" ? date("'Y-m-d'", strtotime($cnv->date_dk2uk($frm->values["newly_tested_date"]))) : "NULL") . ",
							is_regged = '" . $db->escape($frm->values["is_regged"] != "" ? 1 : 0) . "',
							description = '" . $db->escape($frm->values["description"]) . "',
							min_price = '" . $db->escape($frm->values["min_price"]) . "',
							category_id = '" . $db->escape($frm->values["category_id"]) . "',
							type_id = '" . $db->escape($frm->values["type_id"]) . "',
							first_reg_date = " . ($frm->values["first_reg_date"] != "" ? date("'Y-m-d'", strtotime($cnv->date_dk2uk($frm->values["first_reg_date"]))) : "NULL") . ",
							
							`unpaid_debt` = '" . ($frm->values["unpaid_debt"] != "" ? 1 : 0) . "',
							`equipment` = '" . $db->escape(serialize($vars_equipment)) . "',
							`tires` = '" . $db->escape(serialize($vars_tires)) . "',
							`condition` = '" . $db->escape(serialize($vars_condition)) . "',
							`maintain` = '" . $db->escape(serialize($vars_maintain)) . "',
							`exterior` = '" . $db->escape(serialize($vars_exterior)) . "',
							`motorsize` = '" . $db->escape($frm->values["motorsize"]) . "',
							`hp` = '" . $db->escape($frm->values["hp"]) . "',
							`geartype` = '" . $db->escape($frm->values["geartype"]) . "',
							`gearcount` = '" . $db->escape($frm->values["gearcount"]) . "',
							`new_price` = " . (is_numeric($frm->values["new_price"]) ? intval($frm->values["new_price"]) : "NULL") . ",
							`wheel_drive` = '" . $db->escape($frm->values["wheel_drive"]) . "',
							`km_doc` = '" . $db->escape($frm->values["km_doc"] != "" ? 1 : 0) . "',
							
							group_id = '" . intval($frm->values["group_id"]) . "'
						WHERE
							id = '$id' AND
							seller_id = '" . $usr->user_id . "'
						";
					$db->execute($sql);
					OBA_sync("SQL", $sql);
				}
				
				$ressimages = $db->execute("
					SELECT
						*
					FROM
						" . $_table_prefix . "_module_" . $module . "_images
					WHERE
						auction_id = '$id'
					");
				while ($resimage = $db->fetch_array($ressimages))
				{
					if ($frm->values["delete_image_" . $resimage["id"]] != "")
					{
						unlink($_document_root . "/modules/$module/upl/image_" . $resimage["id"] . ".jpg");
						unlink($_document_root . "/modules/$module/upl/image_" . $resimage["id"] . "_thumb.jpg");
						OBA_sync("DELETE_FILE", "image_" . $resimage["id"] . ".jpg");
						OBA_sync("DELETE_FILE", "image_" . $resimage["id"] . "_thumb.jpg");
						$sql = "
							DELETE FROM
								" . $_table_prefix . "_module_" . $module . "_images
							WHERE
								id = '" . $resimage["id"] . "'
							";
						$db->execute($sql);
						OBA_sync("SQL", $sql);
					}
				}
				
				$image = new image;
				$tmpimg = $_document_root . "/tmp/" . uniqid(time()) . ".jpg";
				$order = $db->execute_field("
					SELECT
						MAX(`order`)
					FROM
						" . $_table_prefix . "_module_" . $module . "_images
					WHERE
						auction_id = '$id'
					") + 1;
				for ($i = 0; $i < 20; $i++)
				{
					if (is_uploaded_file($_FILES["upload_image"]["tmp_name"][$i]))
					{
						if (is_file($tmpimg)) unlink($tmpimg);
						move_uploaded_file($_FILES["upload_image"]["tmp_name"][$i], $tmpimg);
						if (
								$img = imagecreatefromjpeg($tmpimg)
								or
								$img = imagecreatefrompng($tmpimg)
								or
								$img = imagecreatefromgif($tmpimg)
							)
						{
							$imgid = OBA_id();
							$sql = "
								INSERT INTO
									" . $_table_prefix . "_module_" . $module . "_images
								(
									id,
									auction_id,
									`order`
								)
								VALUES
								(
									'$imgid',
									'$id',
									'$order'
								)
								";
							$db->execute($sql);
							OBA_sync("SQL", $sql);
							
							imagejpeg($image->imagemaxsize($img, 1000, 1000), $_document_root . "/modules/$module/upl/image_" . $imgid . ".jpg");
							imagejpeg($image->imagemaxsize($img, 200, 200), $_document_root . "/modules/$module/upl/image_" . $imgid . "_thumb.jpg");
							imagedestroy($img);
							
							OBA_sync("SAVE_FILE", "image_" . $imgid . ".jpg");
							OBA_sync("SAVE_FILE", "image_" . $imgid . "_thumb.jpg");
							
							$order++;
						}
						unlink($tmpimg);
					}
				}
				
				$frm->cleanup();
				
				header("Location: /site/$_lang_id/$module/$page/auctions_overview");
				exit;
			}
			
			$html .= $ajax->html();
			$tmp = new tpl("MODULE|$module|dealer_auctions_edit");
			$tmp->set("form", $frm->html());
			$tmp->set("dates", $dates);
			$tmp->set("ajax", $ajax->group);
			$html .= $tmp->html();
		}
		
	}
	elseif ($do == "auctions_delete")
	{
		$sql = "
			DELETE FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				id = '$id' AND
				(
					auction_date > '" . date("Y-m-d") . "' OR
					NOT ISNULL(start_time) AND
					NOT ISNULL(end_time) AND
					end_time < '" . date("Y-m-d H:i:s") . "' AND
					(
						cur_price < min_price
						OR
						cur_price = 0
					)
				)
				AND
				seller_id = '" . $usr->user_id . "'
			";
		if ($db->execute($sql) and $db->affected_rows() == 1)
		{
			OBA_sync("SQL", $sql);
			
			$ress = $db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_images
				WHERE
					auction_id = '$id'
				");
			while ($res = $db->fetch_array($ress))
			{
				unlink($_document_root . "/modules/$module/upl/image_" . $res["id"] . ".jpg");
				unlink($_document_root . "/modules/$module/upl/image_" . $res["id"] . "_thumb.jpg");
				
				OBA_sync("DELETE_FILE", "image_" . $res["id"] . ".jpg");
				OBA_sync("DELETE_FILE", "image_" . $res["id"] . "_thumb.jpg");
			}
			
			$sql = "
				DELETE FROM
					" . $_table_prefix . "_module_" . $module . "_images
				WHERE
					auction_id = '$id'
				";
			$db->execute($sql);
			OBA_sync("SQL", $sql);
		}
		
		header("Location: /site/$_lang_id/$module/$page/auctions_overview");
		exit;
	}
	elseif ($do == "auctions_reset")
	{
		// Gentag auktion
		
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				seller_id = '" . $usr->user_id . "' AND
				id = '$id' AND
				NOT ISNULL(auction_no) AND
				(
					ISNULL(end_time)
					OR
					end_time < '" . date("Y-m-d H:i:s") . "'
					OR
					cancel = 1
				)
			");
		if (!$res = $db->fetch_array())
		{
			header("Location: /site/$_lang_id/$module/$page/auctions_overview");
			exit;
		}
		
		$msg = new message;
		$msg->title("Tilmeld auktion igen");
		$html .= $msg->html();
		
		$frm = new form;
		$frm->tpl("th", "Auktionsdata");
		$frm->tpl("td2", "Auktionstype:" , $res["auction_type"] == "online" ? "Online" : "Fysisk");
		$frm->tpl("td2", "Auktionsnr:", $res["auction_no"]);
		$frm->tpl("td2", "Regnr:", htmlentities(stripslashes($res["regno"])));
		$frm->tpl("td2", "Mærke:", htmlentities(stripslashes($res["brand"])));
		$frm->tpl("td2", "Model:", htmlentities(stripslashes($res["model"])));
		
		if ($res["auction_type"] == "online")
		{
			// Online
			
			// Henter mulige auktionsdatoer for online
			$sel_online_days = array();
			for ($i = 0; $i <= 7; $i++)
			{
				$ts = strtotime("+" . $i . " day");
				$db->execute("
					SELECT
						time,
						duration
					FROM
						" . $_table_prefix . "_module_" . $module . "_online_days
					WHERE
						weekday = '" . date("w", $ts) . "'
					ORDER BY
						time
					");
				while ($db->fetch_array())
				{
					$ts_from = strtotime(date("Y-m-d", $ts) . " " . substr($db->array["time"], 0, 5));
					$ts_to = strtotime(date("Y-m-d H:i", $ts_from) . " +" . $db->array["duration"] . " hour");
					$sel_online_days[] = array($ts_from . "-" . $ts_to, date("d-m-Y H:i", $ts_from) . " til " . date("d-m-Y H:i", $ts_to));
				}
			}
			$frm->select(
				"Auktionsdato",
				"auction_date",
				"",
				"^[0-9]+-[0-9]+$",
				"Påkrævet",
				'
					list($ts_from, $ts_to) = explode("-", $this->values["auction_date"]);
					$ts_from = intval($ts_from);
					$ts_to = intval($ts_to);
					if ($ts_from >= $ts_to) $error = "Påkrævet";
					//if (date("Y-m-d", $ts_to) <= date("Y-m-d H:i")) $error = "";
				',
				$sel_online_days
				);
		}
		else
		{
			// Fysisk
			$frm->input(
				"Auktionsdato",
				"auction_date",
				date("d-m-Y", strtotime($res["auction_date"])),
				"^[0-9]{2}-[0-9]{2}-[0-9]{4}$",
				"Ugyldig dato - format: dd-mm-åååå",
				'
					$db = new db;
					$cnv = new convert;
					if (!$db->execute_field("
						SELECT
							`date`
						FROM
							' . $_table_prefix . '_module_' . $module . '_dates
						WHERE
							`date` = \'" . $db->escape($cnv->date_dk2uk($this->values["auction_date"])) . "\' AND
							`date` >= \'" . date("Y-m-d") . "\'
						"))
					{
						$error = "Auktionsdato findes ikke";
					}
				'
				);
		}
		
		$frm->input(
			"Mindstepris",
			"min_price",
			$res["min_price"],
			"^[0-9]+$",
			"Skal være et tal"
			);
			
		if ($frm->done())
		{
			// Nulstiller auktion
			$cnv = new convert;
			
			if ($res["auction_type"] == "online")
			{
				// Online
				list($ts_from, $ts_to) = explode("-", $frm->values["auction_date"]);
				$ts_from = intval($ts_from);
				$ts_to = intval($ts_to);
				$auction_date = date("Y-m-d", $ts_from);
				$start_time = "'" . date("Y-m-d H:i", $ts_from) . "'";
				$end_time = "'" . date("Y-m-d H:i", $ts_to) . "'";
			}
			else
			{
				// Fysisk
				$auction_date = $db->escape($cnv->date_dk2uk($frm->values["auction_date"]));
				$start_time = "NULL";
				$end_time = "NULL";
			}
				
			$sql = "
				UPDATE
					" . $_table_prefix . "_module_" . $module . "_auctions
				SET
					auction_date = '$auction_date',
					auction_no = NULL,
					min_price = '" . intval($frm->values["min_price"]) . "',
					cur_price = 0,
					bidder_id = 0,
					start_time = $start_time,
					end_time = $end_time,
					cancel = 0
				WHERE
					id = '$id'
				";
			$db->execute($sql);
			OBA_sync("SQL", $sql);
			
			// Sletter bud
			$sql = "
				DELETE FROM
					" . $_table_prefix . "_module_" . $module . "_bids
				WHERE
					auction_id = '$id'
				";
			$db->execute($sql);
			OBA_sync("SQL", $sql);

			header("Location: /site/$_lang_id/$module/$page/auctions_overview");
			exit;
		}

		$html .= $frm->html();
		
		$html .= "
<script>
$(document).ready(function() {
	$('#auction_date').datepicker({
		dateFormat: 'dd-mm-yy',
		minDate: '+0',
		maxDate: '+180',
		dayNamesMin: ['Sø', 'Ma', 'Ti', 'On', 'To', 'Fr', 'Lø', 'Sø'],
		monthNames: ['Januar', 'Februar', 'Marts', 'April', 'Maj', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'December'],
		firstDay: 1
	});
});
</script>			
			";
		
	}
	elseif ($do == "auctions_overview")
	{
		// Dine biler
		
		// Bygger søge SQL
		$searchstring = trim($vars["searchstring"]);
		$sql_where = "";
		if ($searchstring != "")
		{
			$sql_where = "
				AND (
					auction_no = '" . intval($searchstring) . "' OR
					regno LIKE '%" . $db->escape($searchstring) . "%' OR
					brand LIKE '%" . $db->escape($searchstring) . "%' OR
					model LIKE '%" . $db->escape($searchstring) . "%'
					)
				";
		}
		
		$total = $db->execute_field("
			SELECT
				COUNT(*)
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				seller_id = '" . $usr->user_id . "' AND
				(
					auction_type = 'online' AND
					end_time > '" . date("Y-m-d H:i:s") . "'
					OR
					auction_type = 'live' AND
					auction_date >= '" . date("Y-m-d") . "' AND
					ISNULL(end_time)
				)
				$sql_where
			");
			
		if ($total == 0 and !isset($vars["searchstring"]))
		{
			$tmp = new tpl("MODULE|$module|dealer_auctions_none");
			$html .= $tmp->html();
		}
		else
		{
			$frm = new form;
			$frm->method("get");
			$frm->submit_text = "{LANG|Søg}";
			$frm->tpl("th", "{LANG|Søg}");
			$frm->input(
				"{LANG|Søgeord}",
				"searchstring",
				$searchstring
				);
			
			$paging = new paging;
			$limit = $paging->limit(25);
			$paging->total($total);
			$start = ($paging->current_page() - 1) * $limit;
			
			$ress = $db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_auctions
				WHERE
					seller_id = '" . $usr->user_id . "' AND
					(
						auction_type = 'online' AND
						end_time > '" . date("Y-m-d H:i:s") . "'
						OR
						auction_type = 'live' AND
						auction_date >= '" . date("Y-m-d") . "' AND
						ISNULL(end_time)
					)
					$sql_where
				ORDER BY
					`cancel` DESC,
					IF(ISNULL(auction_no), 0, 1),
					auction_date,
					auction_no
				LIMIT
					$start, $limit
				");

			$elements = "";
			while ($res = $db->fetch_array($ress))
			{
				$tmp = new tpl("MODULE|$module|dealer_auctions_overview_element");
				$tmp->set("id", $res["id"]);
				$tmp->set("year", $res["year"]);
				$tmp->set("auction_no", $res["auction_no"]);
				$tmp->set("brand", $res["brand"]);
				$tmp->set("model", $res["model"]);
				$tmp->set("variant", $res["variant"]);
				$tmp->set("type", $res["type"]);
				$tmp->set("doors", $res["doors"]);
				$tmp->set("gearcount", $res["gearcount"]);
				$tmp->set("fuel", $res["fuel"]);
				$tmp->set("color", $res["color"]);
				$tmp->set("km", $res["km"]);
				
				if ($res["cancel"] == 1)
				{
					// Afvist
					$tmp->set("status", "Rejected");
					$tmp->set("auction_no", "-");
				}
				elseif ($res["auction_no"] == "")
				{
					// Ikke godkendt
					$tmp->set("status", "New");
					$tmp->set("auction_no", "-");
				}
				elseif ($res["auction_type"] == "live" and $res["auction_date"] == date("Y-m-d") and $res["end_time"] == "" or $res["auction_type"] == "online" and $res["start_time"] <= date("Y-m-d H:i:s") and $res["end_time"] > date("Y-m-d H:i:s"))
				{
					// Igangværende
					$tmp->set("status", "Active");
				}
				else
				{
					// Ikke startet endnu
					$tmp->set("status", "Waiting");
				}
				
				$elements .= $tmp->html();
			}

			$tmp = new tpl("MODULE|$module|dealer_auctions_overview");
			$tmp->set("paging", $paging->html());
			$tmp->set("form", $frm->html());
			$tmp->set("elements", $elements);
			$html .= $tmp->html();
		}
		
	}
	elseif ($do == "favorites_delete")
	{
		$db->execute("
			DELETE FROM
				" . $_table_prefix . "_module_" . $module . "_favorites
			WHERE
				user_id = '" . $usr->user_id . "' AND
				auction_id = '$id'
			");
		header("Location: /site/$_lang_id/$module/$page/favorites_overview");
		exit;
	}
	elseif ($do == "favorites_overview")
	{
		
		$tmp = new tpl("{MODULE|$module|auctions|dealer_favorites}");
		$html .= $tmp->html();
		
	}
	elseif ($do == "deals_overview")
	{
		// Dine handler
		
		// Bygger søge SQL
		$sql_select = "";
		$sql_join = "";
		$sql_where = " 0 = 1 ";

		// Vis
		if ($vars["deals"] == "bids")
		{
			// Dine bud
			$sql_select = ", MAX(b.bid) AS bid_bid, b.bidder_id AS bid_bidder_id, MAX(b.time) AS bid_time ";
			$sql_join = "
				INNER JOIN
					" . $_table_prefix . "_module_" . $module . "_bids AS b
				ON
					b.auction_id = a.id AND
					b.bidder_id = '" . $usr->user_id . "'
				";
			$sql_where = "1";
			
			if ($vars["status"] == "sold" or $vars["status"] == "not_sold") $vars["status"] = "";
		}
		elseif ($vars["deals"] == "auctions")
		{
			// Dine tilmeldte biler
			$sql_where = " a.seller_id = '" . $usr->user_id . "' ";
			
			if ($vars["status"] == "bought" or $vars["status"] == "not_bought") $vars["status"] = "";
		}
		else
		{
			// Dine handler
			
			$sql_where = "
				(
					a.seller_id = '" . $usr->user_id . "'
					OR
					a.bidder_id = '" . $usr->user_id . "'
				)
				AND
				a.cur_price >= a.min_price AND
				a.cur_price > 0 AND
				NOT ISNULL(a.end_time) AND
				a.end_time < '" . date("Y-m-d H:i:s") . "'
				";
			
			if ($vars["status"] == "not_sold" or $vars["status"] == "not_bought") $vars["status"] = "";
		}

		// Periode
		if ($vars["date_from"] != "")
		{
			$cnv = new convert;
			$sql_where .= "
				AND
				NOT ISNULL(a.end_time) AND a.end_time >= '" . $db->escape($cnv->date_dk2uk($vars["date_from"])) . "'
				";
		}
		if ($vars["date_to"] != "")
		{
			$cnv = new convert;
			$sql_where .= "
				AND
				NOT ISNULL(a.start_time) AND a.start_time <= '" . $db->escape($cnv->date_dk2uk($vars["date_to"])) . " 23:59:59'
				";
		}
		
		// Status
		if ($vars["status"] == "sold")
		{
			// Solgte
			$sql_where .= " AND a.cur_price >= a.min_price AND a.cur_price > 0 AND NOT ISNULL(a.end_time) AND a.end_time < '" . date("Y-m-d H:i:s") . "' ";
		}
		elseif ($vars["status"] == "not_sold")
		{
			// Ikke solgte
			$sql_where .= " AND (a.cur_price < a.min_price OR a.cur_price = 0) AND NOT ISNULL(a.end_time) AND a.end_time < '" . date("Y-m-d H:i:s") . "' ";
		}
		elseif ($vars["status"] == "bought")
		{
			// Købt
			$sql_where .= " AND a.bidder_id = '" . $usr->user_id . "' AND a.cur_price >= a.min_price AND a.cur_price > 0 AND NOT ISNULL(a.end_time) AND a.end_time < '" . date("Y-m-d H:i:s") . "' ";
		}
		elseif ($vars["status"] == "not_bought")
		{
			// Ikke købt
			$sql_where .= " AND (a.cur_price < a.min_price OR cur_price = 0) AND NOT ISNULL(end_time) AND end_time < '" . date("Y-m-d H:i:s") . "' ";
		}
		else
		{
			// Alle
		}

		// Katalognummer
		if ($vars["auction_no"] != "")
		{
			$sql_where .= " AND auction_no = '" . $db->escape($vars["auction_no"]) . "' ";
		}
		
		$total = $db->execute_field("
			SELECT
				COUNT(a.id)
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions AS a
				$sql_join
			WHERE
				$sql_where
				AND
				a.cancel = 0
			GROUP BY
				a.id
			");
			
		if ($total == 0 and $sql_join == "" and $sql_where == "")
		{
			$tmp = new tpl("MODULE|$module|dealer_deals_none");
			$html .= $tmp->html();
		}
		else
		{
			$paging = new paging;
			$limit = $paging->limit(25);
			$paging->total($total);
			$start = ($paging->current_page() - 1) * $limit;
			
			$ress = $db->execute("
				SELECT
					a.*
					$sql_select
				FROM
					" . $_table_prefix . "_module_" . $module . "_auctions AS a
					$sql_join
				WHERE
					$sql_where
					AND
					a.cancel = 0
				GROUP BY
					a.id
				ORDER BY
					a.end_time DESC,
					a.auction_no
				LIMIT
					$start, $limit
				");

			$elements = "";
			while ($res = $db->fetch_array($ress))
			{
				if ($res["seller_id"] == $usr->user_id and $res["bid_bidder_id"] != $usr->user_id and $res["end_time"] < date("Y-m-d H:i:s"))
				{
					// Er sælger
					$tmp = new tpl("MODULE|$module|dealer_deals_overview_element_seller");
					
					if ($res["cur_price"] >= $res["min_price"] and $res["cur_price"] > 0)
					{
						// Solgt
						$tmp->set("status", "Sold");
						
						// Henter køber-info
						$db->execute("
							SELECT
								*
							FROM
								" . $_table_prefix . "_user_" . $module . "_cust
							WHERE
								id = '" . $res["bidder_id"] . "'
							");
						if ($res2 = $db->fetch_array())
						{
							// Køber fundet
							$tmp->set("buyer_company", $res2["company"]);
							$tmp->set("buyer_name", $res2["name"]);
							$tmp->set("buyer_address", $res2["address"]);
							$tmp->set("buyer_zipcode", $res2["zipcode"]);
							$tmp->set("buyer_city", $res2["city"]);
							$tmp->set("buyer_phone", $res2["phone"]);
							$tmp->set("buyer_email", $res2["email"]);
							$tmp->set("buyer_vat", $res2["vat"]);
						}
						else
						{
							// Køber ikke fundet :-(
							$tmp->set("buyer_company", "<i>Info om køber er ikke tilgængelig (#" . $res["bidder_id"] . ")");
						}
					}
					else
					{
						// Ikke solgt
						$tmp->set("status", "NotSold");
					}
					
					$tmp->set("end_time", strftime("%A %d/%m/%Y kl. %H:%M", strtotime($res["end_time"])));
					$tmp->set("cur_price", $res["cur_price"]);
					$tmp->set("id", $res["id"]);
					$tmp->set("year", $res["year"]);
					$tmp->set("auction_no", $res["auction_no"]);
					$tmp->set("brand", $res["brand"]);
					$tmp->set("model", $res["model"]);
					$tmp->set("variant", $res["variant"]);
					$tmp->set("type", $res["type"]);
					$tmp->set("doors", $res["doors"]);
					$tmp->set("gearcount", $res["gearcount"]);
					$tmp->set("fuel", $res["fuel"]);
					$tmp->set("color", $res["color"]);
					$tmp->set("km", $res["km"]);
					$elements .= $tmp->html();
				}
				
				if ($res["bidder_id"] == $usr->user_id and $res["cur_price"] >= $res["min_price"] and $res["cur_price"] > 0 and $res["end_time"] < date("Y-m-d H:i:s"))
				{
					// Er køber
					$tmp = new tpl("MODULE|$module|dealer_deals_overview_element_buyer");
					$tmp->set("end_time", strftime("%A %d/%m/%Y kl. %H:%M", strtotime($res["end_time"])));
					$tmp->set("cur_price", $res["cur_price"]);
					$tmp->set("id", $res["id"]);
					$tmp->set("year", $res["year"]);
					$tmp->set("auction_no", $res["auction_no"]);
					$tmp->set("brand", $res["brand"]);
					$tmp->set("model", $res["model"]);
					$tmp->set("variant", $res["variant"]);
					$tmp->set("type", $res["type"]);
					$tmp->set("doors", $res["doors"]);
					$tmp->set("gearcount", $res["gearcount"]);
					$tmp->set("fuel", $res["fuel"]);
					$tmp->set("color", $res["color"]);
					$tmp->set("km", $res["km"]);
					
					$tmp->set("seller_name", $res["seller_name"]);
					$tmp->set("seller_address", $res["seller_address"]);
					$tmp->set("seller_zipcode", $res["seller_zipcode"]);
					$tmp->set("seller_city", $res["seller_city"]);
					$tmp->set("seller_phone", $res["seller_phone"]);
					$tmp->set("seller_email", $res["seller_email"]);
					$tmp->set("seller_vat", $res["seller_vat"]);
					
					$elements .= $tmp->html();
				}
				elseif ($res["bid_bidder_id"] == $usr->user_id)
				{
					// Bud
					
					$tmp = new tpl("MODULE|$module|dealer_deals_overview_element_bidder");
					$tmp->set("bid_time", strftime("%A %d/%m/%Y kl. %H:%M", strtotime($res["bid_time"])));
					$tmp->set("bid_bid", $res["bid_bid"]);
					$tmp->set("end_time", strftime("%A %d/%m/%Y kl. %H:%M", strtotime($res["end_time"])));
					$tmp->set("cur_price", $res["cur_price"]);
					$tmp->set("id", $res["id"]);
					$tmp->set("year", $res["year"]);
					$tmp->set("auction_no", $res["auction_no"]);
					$tmp->set("brand", $res["brand"]);
					$tmp->set("model", $res["model"]);
					$tmp->set("variant", $res["variant"]);
					$tmp->set("type", $res["type"]);
					$tmp->set("doors", $res["doors"]);
					$tmp->set("gearcount", $res["gearcount"]);
					$tmp->set("fuel", $res["fuel"]);
					$tmp->set("color", $res["color"]);
					$tmp->set("km", $res["km"]);
					
					$tmp->set("seller_name", $res["seller_name"]);
					$tmp->set("seller_address", $res["seller_address"]);
					$tmp->set("seller_zipcode", $res["seller_zipcode"]);
					$tmp->set("seller_city", $res["seller_city"]);
					$tmp->set("seller_phone", $res["seller_phone"]);
					$tmp->set("seller_email", $res["seller_email"]);
					$tmp->set("seller_vat", $res["seller_vat"]);
					
					if ($res["bid_bidder_id"] == $res["bidder_id"])
					{
						// Er aktuel byder
						if ($res["cur_price"] >= $res["min_price"] and $res["cur_price"] > 0)
						{
							// Højeste bud
							$tmp->set("status", "Highest");
						}
						else
						{
							// Mindstepris ikke opnået
							$tmp->set("status", "MinPrice");
						}
					}
					else
					{
						// Overbud
						$tmp->set("status", "Overbid");
					}
					
					$elements .= $tmp->html();
				}
			}

			$tmp = new tpl("MODULE|$module|dealer_deals_overview");
			$tmp->set("paging", $paging->html());
			$tmp->set("elements", $elements);
			$html .= $tmp->html();
		}
		
	}
	elseif ($do == "deals_contract")
	{
		// Udskriv købsaftale
		
		// Henter auktion
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				NOT ISNULL(end_time) AND
				end_time <= '" . date("Y-m-d H:i:s") . "' AND
				(
					seller_id = '" . $usr->user_id . "'
					OR
					bidder_id = '" . $usr->user_id . "'
				)
				AND
				cur_price >= min_price AND
				cur_price > 0
				AND
				`cancel` = 0
			");
		if (!$auc = $db->fetch_array())
		{
			die("Auktion ikke fundet");
		}
		
		// Henter køber
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_user_" . $module . "_cust
			WHERE
				id = '" . $auc["bidder_id"] . "'
			");
		$buy = $db->fetch_array();
		
		// FPDF + FPDI
		define("RELATIVE_PATH", $_document_root . "/modules/$module/fpdf/");
		define("FPDF_FONTPATH", $_document_root . "/modules/$module/fpdf/font/");
		require_once($_document_root . "/modules/$module/fpdf/fpdf.php");
		require_once($_document_root . "/modules/$module/fpdf/fpdi.php");
		
		// Nyt objekt
		$pdf = new FPDI;
		
		// Sætter parametre
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont("Arial", "", 10);
		
		// Sider
		$pagecount = $pdf->setSourceFile($_document_root . "/modules/$module/pdf/contract.pdf");
		
		for ($i = 1; $i <= $pagecount; $i++)
		{
			$tplidx = $pdf->ImportPage($i); 
			$s = $pdf->getTemplatesize($tplidx); 
			$pdf->AddPage("P", array($s["w"], $s["h"])); 
			$pdf->useTemplate($tplidx); 
			
			if ($i == 1)
			{
				// Køber
				$pdf->Text(16, 67, $buy["name"]);
				$pdf->Text(16, 78, $buy["address"]);
				$pdf->Text(16, 89, $buy["zipcode"] . " " . $buy["city"]);
				$pdf->Text(16, 100, $buy["phone"]);
				
				// Sælger
				$pdf->Text(16, 125, $auc["seller_name"]);
				$pdf->Text(16, 136, $auc["seller_address"]);
				$pdf->Text(16, 147, $auc["seller_zipcode"] . " " . $auc["seller_city"]);
				$pdf->Text(16, 158, $auc["seller_phone"]);
				
				// Bilen
				$pdf->Text(16, 183, $auc["brand"]);
				$pdf->Text(16, 194, $auc["model"]);
				$pdf->Text(16, 205, $auc["year"]);
				$pdf->Text(16, 216, $auc["regno"]);
				$pdf->Text(16, 227, $auc["chasno"]);
				
				$pdf->Text(111, 183, $auc["newly_tested_date"] != "" ? date("d-m-Y", strtotime($auc["newly_tested_date"])) : "");
				$pdf->Text(111, 194, $auc["first_reg_date"] != "" ? date("d-m-Y", strtotime($auc["first_reg_date"])) : "");
				$pdf->Text(111, 205, $auc["color"]);
				$pdf->Text(111, 216, $auc["km"]);
				
				// Købspris
				$pdf->Text(16, 253, "DKK " . $auc["cur_price"] . ",-");
			}
			elseif ($i == 3)
			{
				// Bilens data
				$pdf->Text(16, 122, $auc["brand"]);
				$pdf->Text(16, 133, $auc["model"]);
				$pdf->Text(16, 144, $auc["chasno"]);
				$pdf->Text(16, 155, $auc["regno"]);
			}
		}
				

		// Viser PDF
		$pdf->Output();
		
		exit;
		
	}
	elseif ($do == "deals_buyer_invoice" or $do == "deals_seller_invoice")
	{
		// Henter auktion
		
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				NOT ISNULL(end_time) AND
				end_time <= '" . date("Y-m-d H:i:s") . "' AND
				(
					seller_id = '" . $usr->user_id . "'
					OR
					bidder_id = '" . $usr->user_id . "'
				)
				AND
				cur_price >= min_price AND
				cur_price > 0 AND
				`cancel` = 0 AND
				invoice_id > 0 AND
				seller_account_invoice_id > 0
			");
		if (!$res = $db->fetch_array()) die("Faktura er endnu ikke klar");
		
		if ($do == "deals_buyer_invoice" and $res["bidder_id"] = $usr->user_id and $res["invoice_id"] > 0)
		{
			$fpdf = OBA_invoice_fpdf($res["invoice_id"]);
			$fpdf->Output($tmpfile);
			exit;
		}
		elseif ($do == "deals_seller_invoice" and $res["user_id"] = $usr->user_id and $res["seller_account_invoice_id"] > 0)
		{
			$fpdf = OBA_invoice_fpdf($res["seller_account_invoice_id"]);
			$fpdf->Output($tmpfile);
			exit;
		}
		else
		{
			die("Faktura findes ikke");
		}
	}