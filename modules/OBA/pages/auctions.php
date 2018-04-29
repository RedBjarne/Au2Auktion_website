<?php
	// Auktioner

	require_once($_document_root . "/modules/$module/inc/functions.php");
	
	$usr = new user($module . "_cust");
			
	if (preg_match("/^(A|O)[0-9]+$/", $vars["id"]))
	{
		$id = $vars["id"];
	}

	if ($do == "pdf")
	{
		// Vis PDF med auktioner
		
		if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $vars["date"]) or $vars["date"] < date("Y-m-d")) die("Ingen PDF tilgængelig for den valgte dato <script> history.back(); </script>");
		
		// Felter der skal vises
		$array_fields = array(
			"regno" => "Regnr.",
			"chasno" => "Stelnr.",
			"brand" => "Mærke",
			"model" => "Model",
			"variant" => "Variant",
			"type" => "Type",
			"fuel" => "Brændstof",
			"doors" => "Døre",
			"year" => "Årgang",
			"km" => "Km-stand",
			"color" => "Farve",
			"newly_tested" => "Nysynet",
			"newly_tested_date" => "Sidste syn",
			"is_regged" => "Indregistreret",
			"first_reg_date" => "1. indreg.",
			"no_vat" => "Moms",
			"no_tax" => "Afgift",
			"service" => "Servicebog",
			"category_type" => "Kategori"
			);
		if ($vars["show_min_price"] != "")
		{
			$array_fields["min_price"] = "Mindstepris";
			$array_fields["keyno"] = "Nøglenr";
		}
		
		// FPDF + FPDI
		define("RELATIVE_PATH", $_document_root . "/modules/$module/fpdf/");
		define("FPDF_FONTPATH", $_document_root . "/modules/$module/fpdf/font/");
		require_once($_document_root . "/modules/$module/fpdf/fpdf.php");
		require_once($_document_root . "/modules/$module/fpdf/fpdi.php");
		
		// Instanciation of inherited class
		$fpdf = new FPDI('P', 'mm', 'A4');
		$fpdf->SetAutoPageBreak(false);
		
		// Forside
		$fpdf->AddPage();
		$fpdf->Image($_document_root . "/modules/$module/pdf/frontpage.jpg", 0, 0, 210, 297);
		$fpdf->SetTextColor(0, 0, 0);
		$fpdf->SetFont("Arial", "", 30);
		$fpdf->SetXY(0, 185);
		$fpdf->MultiCell(210, 0, strftime("%A d. %e. %B %Y", strtotime($vars["date"])), "0", "C");
	
		// Standard farve
		$fpdf->SetTextColor(0, 0, 0);
		
		$ress = $db->execute("
			SELECT
				auc.*,
				cat.type AS category_type
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions AS auc
			LEFT JOIN
				" . $_table_prefix . "_module_" . $module . "_categories AS cat
			ON
				cat.id = auc.category_id
			WHERE
				auc.auction_date = '" . $db->escape($vars["date"]) . "' AND
				auc.auction_date >= '" . date("Y-m-d") . "' AND
				NOT ISNULL(auc.auction_no) AND
				auc.auction_type = 'live' AND
				auc.cancel <> '1'
			ORDER BY
				auc.auction_no
			");
		$total = $db->num_rows($ress);
		$count = 0;
		while ($res = $db->fetch_array($ress))
		{
			if ($count == 0 or $count >= 3)
			{
				$fpdf->AddPage();
				$offset_y = 0;
				$count = 0;
			}
			elseif ($count == 1)
			{
				$offset_y = 99;
			}
			else
			{
				$offset_y = 198;
			}
			
			// Overskrift
			$fpdf->SetFont("Arial", "", 16);
			$fpdf->Text(10, $offset_y + 15, "AUKTIONSNR: " . $res["auction_no"]);
			
			// Felter
			$fpdf->SetFont("Arial", "", 9);
			$y = 20;
			$fcount = 0;
			$finset = 0;
			foreach ($array_fields as $key => $val)
			{
				if (
					$res[$key] != ""
					or
					in_array($key, array("newly_tested", "is_regged", "no_vat", "no_tax")) and $res[$key] == 1
					)
				{
					if ($finset == 0 and $fcount >= count($array_fields) / 2)
					{
						$finset = 70;
						$y1 = $fpdf->GetY();
						$y = 20;
					}
					if ($key == "keyno" and intval($res[$key]) == 0) $res[$key] = "-";
					if ($key == "newly_tested" or $key == "is_regged" or $key == "no_vat" or $key == "no_tax" or $key == "service")
					{
						if ($res[$key] == 1)
						{
							if ($key == "no_vat")
							{
								$fpdf->Text($finset + 33, $offset_y + $y, "ekskl. moms");
							}
							elseif ($key == "no_tax")
							{
								$fpdf->Text($finset + 33, $offset_y + $y, "uden afgift (ikke reg. i dk)");
							}
							else
							{
								$fpdf->Text($finset + 33, $offset_y + $y, "Ja");
							}
								
							$fpdf->Text($finset + 10, $offset_y + $y, $val . ":");
							$y += 4.3;
							$fcount++;
						}
					}
					else
					{
						if (preg_match("/_date$/", $key))
						{
							$fpdf->Text($finset + 33, $offset_y + $y, date("d-m-Y", strtotime($res[$key])));
						}
						else
						{
							$fpdf->Text($finset + 33, $offset_y + $y, stripslashes($res[$key]));
						}
						$fpdf->Text($finset + 10, $offset_y + $y, $val . ":");
						$y += 4.3;
						$fcount++;
					}
				}
			}
			$y1 = max($fpdf->GetY(), $y1);
			
			// Billede
			$y = $offset_y + 10;
			$imgid = $db->execute_field("
				SELECT
					id
				FROM
					" . $_table_prefix . "_module_" . $module . "_images
				WHERE
					auction_id = '" . $res["id"] . "'
				");
			if ($imgid)
			{
				// Tilføjer billede
				$maxw = 60;
				$maxh = 45;
				
				$imgfile = $_document_root . "/modules/$module/upl/image_" . $imgid . ".jpg";
				if ($img = imagecreatefromjpeg($imgfile))
				{
					$w = imagesx($img);
					$h = imagesy($img);
					imagedestroy($img);
					
					if ($w / $h > $maxw / $maxh)
					{
						$h = $maxw / $w * $h;
						$w = $maxw;
					}
					else
					{
						$w = $maxh / $h * $w;
						$h = $maxh;
					}
				}
				else
				{
					$w = $maxw;
					$h = $maxh;
				}
				$l = 200 - $w;
				$t = $y;
				$fpdf->Image($imgfile, $l, $t, $w, $h, "JPG");
				$y2 = $t + $h + 5;
			}
			else
			{
				$fpdf->Text(140, $y, "Intet billede");
				$y2 = $y1;
			}
			
			// Beskrivelse
			$y = 65;
			$arr = explode("\n", wordwrap($res["description"], 125, "\n", true));
			for ($i = 0; $i < count($arr) and $i < 9; $i++)
			{
				$fpdf->Text(10, $offset_y + $y, $arr[$i]);
				$y += 3.5;
			}
			
			// Tegner streg
			if ($count > 0)
			{
				$fpdf->SetDrawColor(0, 0, 0);
				$fpdf->SetLineWidth(0.1);
				$fpdf->Line(0, 99 * $count, 210, 99 * $count);
			}
			
			$count++;
		}
		
		// Betingelser
		$pagecount = $fpdf->setSourceFile($_document_root . "/modules/$module/pdf/terms.pdf"); 
		for ($i = 1; $i <= $pagecount; $i++)
		{
			$tplidx = $fpdf->ImportPage($i); 
			$s = $fpdf->getTemplatesize($tplidx); 
			$fpdf->AddPage('P', array($s['w'], $s['h'])); 
			$fpdf->useTemplate($tplidx); 
		}

		// Vis PDF				
		$fpdf->Output();
		exit;
		
	}
	elseif ($do == "show")
	{
		// Vis auktion

		$ress = $db->execute("
			SELECT
				auc.*,
				cat.type AS category_type,
				cat.title AS category_title
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions AS auc
			LEFT JOIN
				" . $_table_prefix . "_module_" . $module . "_categories AS cat
			ON
				cat.id = auc.category_id
			WHERE
				(
					auc.id = '" . $db->escape($id) . "' OR
					(
						auc.auction_no = '" . $db->escape($vars["auction_no"]) . "' AND
						auc.auction_no <> ''
					)
				)
				AND
				(
					auc.auction_type = 'online' AND
					auc.start_time <= '" . date("Y-m-d H:i") . "' AND
					auc.end_time > '" . date("Y-m-d H:i") . "'
					
					OR
					
					auc.auction_type = 'live' AND
					auc.auction_date >= '" . date("Y-m-d") . "' AND
					ISNULL(auc.end_time)
				)
				AND
				NOT ISNULL(auc.auction_no) AND
				auc.cancel <> '1'
			");
		if (!$res = $db->fetch_array($ress))
		{
			echo("<script> alert('Den ønskede auktion er desværre ikke tilgængelig'); history.back(); </script>");
			exit;
			header("Location: /site/$_lang_id/$module/$page/overview");
			exit;
		}
		$vars["id"] = $res["id"];
		$id = $res["id"];
		
		$ajax = new ajax;
		if ($ajax->do == "switch_favorite")
		{
			if (!$usr->logged_in)
			{
				$ajax->response(array(
					"state" => "error",
					"message" => "Du skal være logget ind for at benytte favoritter"
					));
			}
			if ($db->execute_field("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_favorites
				WHERE
					user_id = '" . $usr->user_id . "' AND
					auction_id = '" . $db->escape($res["id"]) . "'
				"))
			{
				// Fjern
				$db->execute("
					DELETE FROM
						" . $_table_prefix . "_module_" . $module . "_favorites
					WHERE
						user_id = '" . $usr->user_id . "' AND
						auction_id = '" . $db->escape($res["id"]) . "'
					");
				$ajax->response(array(
					"state" => "ok",
					"is_favorite" => "0"
					));
			}
			else
			{
				// Tilføj
				$db->execute("
					INSERT INTO
						" . $_table_prefix . "_module_" . $module . "_favorites
					(
						user_id,
						auction_id
					)
					VALUES
					(
						'" . $usr->user_id . "',
						'" . $db->escape($res["id"]) . "'
					)
					");
				$ajax->response(array(
					"state" => "ok",
					"is_favorite" => "1"
					));
			}
		}

		// Billeder		
		$image = "";
		$images = "";
		$ress2 = $db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_images
			WHERE
				auction_id = '" . $res["id"] . "'
			");
		while ($res2 = $db->fetch_array($ress2))
		{
			if ($image == "")
			{
				$tmp = new tpl("MODULE|$module|auctions_show_primary_image");
				$tmp->set("id", $res2["id"]);
				$image = $tmp->html();
			}
			
			if ($count >= 4)
			{
				$tmp = new tpl("MODULE|$module|auctions_show_new_row");
				$images .= $tmp->html();
				$count = 0;
			}
			
			$tmp = new tpl("MODULE|$module|auctions_show_image");
			$tmp->set("id", $res2["id"]);
			$images .= $tmp->html();
			
			$count++;
		}
		if ($images == "")
		{
			$tmp = new tpl("MODULE|$module|auctions_show_no_image");
			$image = $tmp->html();
		}
		
		// Udstyr
		$html_equipment = "";
		$equipment = unserialize($res["equipment"]);
		if ($equipment["airbags"] != "" and $equipment["airbags"] > 0) $equipment["equipment"][] = $equipment["airbags"] . " airbags";
		$count = 0;
		for ($i = 0; $i < count($equipment["equipment"]); $i++)
		{
			if ($count >= 4)
			{
				$tmp = new tpl("MODULE|$module|auctions_show_new_row");
				$html_equipment .= $tmp->html();
				$count = 0;
			}
			
			$tmp = new tpl("MODULE|$module|auctions_show_equipment");
			$tmp->set("equipment", $equipment["equipment"][$i]);
			$html_equipment .= $tmp->html();
			
			$count++;
		}
		
		// Dæk
		$tires = unserialize($res["tires"]);
		
		// Stand
		$condition = unserialize($res["condition"]);
		
		// Vedligeholdelse
		$maintain = unserialize($res["maintain"]);
		
		// Udvendig stand
		$exterior = unserialize($res["exterior"]);
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
		$html_exterior = "";
		$count = 0;
		for ($i = 0; $i < count($arr_fields); $i++)
		{
			list($name, $title, $fields) = $arr_fields[$i];

			$tmpstr = "";
			for ($j = 0; $j < count($fields); $j++)
			{
				list($fieldname, $fieldtitle, $fieldsource) = $fields[$j];
				
				if ($exterior[$fieldname] != "")
				{
					if ($tmpstr != "") $tmpstr .= ", ";
					$tmpstr .= $exterior[$fieldname];
				}
			}
			
			if ($tmpstr != "")
			{
				$tmp = new tpl("MODULE|$module|auctions_show_exterior");
				$tmp->set("name", $name);
				$tmp->set("title", $title);
				$tmp->set("fields", $tmpstr);
				$html_exterior .= $tmp->html();
			}
		}
		if ($html_exterior == "")
		{
			$tmp = new tpl("MODULE|$module|auctions_show_exterior_no_errors");
			$html_exterior = $tmp->html();
		}
		
		
		
		// Byd på bilen
		if ($res["auction_type"] == "online")
		{
			// Online
			if ($usr->logged_in)
			{
				// Logget ind
				$tmp = new tpl("MODULE|$module|auctions_show_bid_online");
				
				if ($res["cur_price"] > 0 and $res["bidder_id"] == $usr->user_id)
				{
					// Har det højeste bud
					if ($res["cur_price"] < $res["min_price"])
					{
						// Har ikke opnået mindstepris
						$tmp->set("bid_title", "Du har højeste bud, men er under mindsteprisen");
						$tmp->set("bid_class", "AuctionBidMinPrice");
						
						if ($res["min_price"] - $res["cur_price"] <= 500)
						{
							$tmp->set("bid_tip", "<b>Tip:</b> Mindsteprisen er DKK " . number_format($res["min_price"] - $res["cur_price"], 0, ".", "") . " over dit bud");
						}
						elseif ($res["min_price"] - $res["cur_price"] <= 5000)
						{
							$tmp->set("bid_tip", "<b>Tip:</b> Mindsteprisen er under DKK 5.000 over dit bud");
						}
						else
						{
							$tmp->set("bid_tip", "<b>Tip:</b> Mindsteprisen er over DKK 5.000 over dit bud");
						}
					}
					else
					{
						// Mindstepris opnået
						$tmp->set("bid_class", "AuctionBidHighest");
						$tmp->set("bid_title", "Du har højeste bud");
						$tmp->set("bid_tip", "<b>Bemærk:</b> Mindsteprisen er opnået");
						$tmp->set("bid_message", "Bilen er din, hvis du ikke bliver overbudt inden " . strftime("%A kl. %H:%M+", strtotime($res["end_time"])));
					}
				}
				elseif ($res["cur_price"] > 0 and $max_price = $db->execute_field("
					SELECT
						MAX(bid)
					FROM
						" . $_table_prefix . "_module_" . $module . "_bids
					WHERE
						auction_id = '" . $db->escape($res["id"]) . "' AND
						bidder_id = '" . $usr->user_id . "'
					"))
				{
					// Er blevet overbudt
					$tmp->set("bid_class", "AuctionBidOverbid");
					$tmp->set("bid_title", "Du er blevet overbudt, byd igen");
					$tmp->set("bid_max", number_format($max_price, 0, ",", "."));
				}
				else
				{
					// Har ikke budt
					$tmp->set("bid_class", "AuctionBidDefault");
				}
				
				// Salær
				$tmp->set("salery", number_format($db->execute_field("
					SELECT
						salery
					FROM
						" . $_table_prefix . "_module_" . $module . "_online_salery
					WHERE
						bid <= '" . $res["cur_price"] . "'
					ORDER BY
						bid DESC
					LIMIT 1
					"), 0, ",", "."));
					
				// Næste bud
				$tmp->set("next_bid", number_format($res["cur_price"] + 100, 0, ",", "."));
				
				// Antal bud
				$tmp->set("bid_count", $db->execute_field("
					SELECT
						COUNT(*)
					FROM
						" . $_table_prefix . "_module_" . $module . "_bids
					WHERE
						auction_id = '" . $res["id"] . "'
					"));
					
				// Seneste 5 bud
				$db->execute("
					SELECT
						*
					FROM
						" . $_table_prefix . "_module_" . $module . "_bids
					WHERE
						auction_id = '" . $res["id"] . "'
					ORDER BY
						id DESC
					LIMIT
						0, 5
					");
				while ($db->fetch_array())
				{
					$tmp1 = new tpl("MODULE|$module|auctions_show_bid_online_bid");
					$tmp1->set("bid", number_format($db->array["bid"], 0, ",", "."));
					$tmp1->set("time", strftime("kl. %H:%M %d/%m", strtotime($db->array["time"])));
					$tmp->add("bids", $tmp1->html());
				}
				
				// Slut tid
				$tmp->set("end_time", strftime("%A kl. %H:%M+", strtotime($res["end_time"])));				
				
				// Sekunder tilbage
				$tmp->set("secs_left", strtotime($res["end_time"]) - time());
			}
			else
			{
				// Ikke logget ind
				$tmp = new tpl("MODULE|$module|auctions_show_bid_login");
			}
		}
		else
		{
			// Live
			$tmp = new tpl("MODULE|$module|auctions_show_bid_live");
		}
		$tmp->set("id", $res["id"]);
		$tmp->set("auction_no", $res["auction_no"]);
		$tmp->set("brand", $res["brand"]);
		$tmp->set("model", $res["model"]);
		$tmp->set("variant", $res["variant"]);
		$tmp->set("cur_price", $res["cur_price"]);
		$tmp->set("auction_date", date("d-m-Y", strtotime($res["auction_date"])));
		$bid = $tmp->html();
		
				
		
		// Template
		$tmp = new tpl("MODULE|$module|auctions_show");
		
		// Sælger type
		$seller_type = $db->execute_field("
			SELECT
				extra_type
			FROM
				" . $_table_prefix . "_user_" . $module . "_cust
			WHERE
				id = '" . $res["seller_id"] . "'
			");
		if ($seller_type == "private") $seller_type = "Privat-person";
		if ($seller_type == "dealer") $seller_type = "Forhandler";
		$tmp->set("seller_type", $seller_type);
		
		// Info
		$tmp->set("new_price", $res["new_price"]);
		$tmp->set("regno", preg_replace("/.{5}$/", "*****", $res["regno"]));
		
		// Byd
		$tmp->set("bid", $bid);
		
		// Udstyr
		$tmp->set("equipment_equipment", $html_equipment);
		$tmp->set("equipment_comment", $equipment["comment"]);
		
		// Dæk
		$tmp->set("tires_type", $tires["type"]);
		$tmp->set("tires_rim", $tires["rim"]);
		$tmp->set("tires_depth_front", $tires["depth_front"]);
		$tmp->set("tires_depth_back", $tires["depth_back"]);
		$tmp->set("tires_type_extra", $tires["type_extra"]);
		$tmp->set("tires_rim_extra", $tires["rim_extra"]);
		$tmp->set("tires_depth_front_extra", $tires["depth_front_extra"]);
		$tmp->set("tires_depth_back_extra", $tires["depth_back_extra"]);
		
		// Stand
		$tmp->set("condition_inside", $condition["inside"]);
		$tmp->set("condition_mecanical", $condition["mecanical"]);
		$tmp->set("condition_lacquer", $condition["lacquer"]);
		$tmp->set("condition_light_front", $condition["light_front"]);
		$tmp->set("condition_light_back", $condition["light_back"]);
		$tmp->set("condition_light_fog", $condition["light_fog"]);
		$tmp->set("condition_damage", $condition["damage"]);
		$tmp->set("condition_electric", $condition["electric"]);
		
		// Vedligeholdelse
		$tmp->set("maintain_book", $maintain["book"]);
		$tmp->set("maintain_service_ok", $maintain["service_ok"]);
		$tmp->set("maintain_rust_treat", $maintain["rust_treat"]);
		$tmp->set("maintain_brake", $maintain["brake"]);
		$tmp->set("maintain_last_service", $maintain["last_service"]);
		$tmp->set("maintain_next_service", $maintain["next_service"]);
		$tmp->set("maintain_timing_belt", $maintain["timing_belt"]);
		$tmp->set("maintain_oil", $maintain["oil"]);
		$tmp->set("maintain_comment", $maintain["comment"]);
		
		// Udvendig
		$tmp->set("exterior", $html_exterior);		
		
		$tmp->set("id", $res["id"]);
		$tmp->set("auction_date", date("d-m-Y", strtotime($res["auction_date"])));
		$tmp->set("auction_no", $res["auction_no"]);
		$tmp->set("brand", stripslashes($res["brand"]));
		$tmp->set("model", stripslashes($res["model"]));
		$tmp->set("variant", stripslashes($res["variant"]));
		$tmp->set("type", stripslashes($res["type"]));
		$tmp->set("fuel", stripslashes($res["fuel"]));
		$tmp->set("doors", stripslashes($res["doors"]));
		$tmp->set("year", stripslashes($res["year"]));
		$tmp->set("km", stripslashes($res["km"]));
		$tmp->set("km_doc", $res["km_doc"]);
		$tmp->set("hp", $res["hp"]);
		$tmp->set("color", stripslashes($res["color"]));
		$tmp->set("newly_tested", $res["newly_tested"]);
		$tmp->set("newly_tested_date", $res["newly_tested_date"] != "" ? date("d-m-Y", strtotime($res["newly_tested_date"])) : "-");
		$tmp->set("is_regged", $res["is_regged"]);
		$tmp->set("service", $res["service"]);
		$tmp->set("first_reg_date", $res["first_reg_date"] != "" ? date("d-m-Y", strtotime($res["first_reg_date"])) : "-");
		$tmp->set("no_vat", $res["no_vat"]);
		$tmp->set("no_tax", $res["no_tax"]);
		$tmp->set("yellow_plate", $res["yellow_plate"]);
		$tmp->set("chasno", preg_replace("/.{4}$/", "****", $res["chasno"]));
		$tmp->set("category_type", $res["category_type"]);
		$tmp->set("category_title", $res["category_title"]);
		$tmp->set("description", nl2br(stripslashes($res["description"])));
		
		// Billeder
		$tmp->set("images", $images);
		$tmp->set("image", $image);
		
		// Gruppe
		$tmp->set("group_id", $res["group_id"]);
		$tmp->set("group_title", $db->execute_field("
			SELECT
				title
			FROM
				" . $_table_prefix . "_module_" . $module . "_groups
			WHERE
				id = '" . $res["group_id"] . "'
			"));
			
		$tmp->set("auction_type", $res["auction_type"]);
		$tmp->set("ajax", $ajax->group);
		$tmp->set("is_favorite", $db->execute_field("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_favorites
				WHERE
					user_id = '" . $usr->user_id . "' AND
					auction_id = '" . $db->escape($res["id"]) . "'
				") ? 1 : 0);		
				
		$tmp->set("end_time", strftime("%A kl. %H:%M+", strtotime($res["end_time"])));
		$tmp->set("cur_price", $res["cur_price"]);
		
		$html .= $ajax->html();
		$html .= $tmp->html();
		
		if ($vars["print"] == "true") $tpl = "print";
		
	}
	elseif ($do == "bid")
	{
		// Byd på auktion
		
		if (!$usr->logged_in)
		{
			header("Location: /site/$_lang_id/$module/$page/show/$id#bid");
			exit;
		}
	
		// Henter auktion
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				id = '$id' AND
				auction_type = 'online' AND
				start_time <= '" . date("Y-m-d H:i") . "' AND
				end_time >= '" . date("Y-m-d H:i") . "' AND
				`cancel` <> '1'
			");
		if (!$res = $db->fetch_array())
		{
			echo("<script> alert('Auktionen er afsluttet eller ikke længere tilgængelig!'); history.back(); </script>");
			exit;
		}
		
		// Kontrollerer bud
		$bid = intval($vars["bid"]);
		if ($bid < $res["cur_price"] + 100)
		{
			echo("<script> alert('Du skal byde mindst DKK 100,- højere end nuværende bud!'); history.back(); </script>");
			exit;
		}
		
		// Gemmer bud
		$sql = "
			UPDATE
				" . $_table_prefix . "_module_" . $module . "_auctions
			SET
				cur_price = '$bid',
				bidder_id = '" . $usr->user_id . "',
				end_time = '" . ((strtotime($res["end_time"]) < time() + 900) ? date("Y-m-d H:i:00", strtotime("+15 min")) : $res["end_time"]) . "'
			WHERE
				id = '" . $res["id"] . "'
			";
		if ($db->execute($sql))
		{
			OBA_sync("SQL", $sql);
			
			$sql = "
				INSERT INTO
					" . $_table_prefix . "_module_" . $module . "_bids
				(
					`id`,
					`time`,
					`auction_id`,
					`bidder_id`,
					`bid`,
					`type`
				)
				VALUES
				(
					'" . OBA_id() . "',
					'" . date("Y-m-d H:i:s") . "',
					'" . $res["id"] . "',
					'" . $usr->user_id . "',
					'$bid',
					'Online'
				)
				";
			if ($db->execute($sql))
			{
				OBA_sync("SQL", $sql);
			}
			
			// Bud OK
			
			// Billede
			$image_id = $db->execute_field("
				SELECT
					id
				FROM
					" . $_table_prefix . "_module_" . $module . "_images
				WHERE
					auction_id = '" . $res["id"] . "'
				");
			
			// Mail til byder
			if ($usr->data["email"])
			{
				$tmp = new tpl("MODULE|$module|email_bidder");
				$tmp->set("image_id", $image_id);
				$tmp->set("cur_price", $bid);
				$tmp->set("auction_no", $res["auction_no"]);
				$tmp->set("brand", $res["brand"]);
				$tmp->set("model", $res["model"]);
				$tmp->set("variant", $res["variant"]);
				$tmp->set("first_reg_date", $res["first_reg_date"] != "" ? date("M/Y", strtotime($res["first_reg_date"])) : "-");
				$tmp->set("km", $res["km"]);
				$tmp->set("min_price", $res["min_price"]);
				$tmp->set("start_date", date("d-m-Y", strtotime($res["start_time"])));
				
				$e = new email;
				$e->to(preg_replace("/ .*$/", "", $usr->data["name"]), $usr->data["email"]);
				$e->subject("Du har budt på en bil");
				$e->body($tmp->html());
				$e->send();
			}
			
			// Mail til sælger
			if ($res["seller_email"] != "")
			{
				$tmp = new tpl("MODULE|$module|email_bid_seller");
				$tmp->set("image_id", $image_id);
				$tmp->set("cur_price", $bid);
				$tmp->set("auction_no", $res["auction_no"]);
				$tmp->set("brand", $res["brand"]);
				$tmp->set("model", $res["model"]);
				$tmp->set("variant", $res["variant"]);
				$tmp->set("first_reg_date", $res["first_reg_date"] != "" ? date("M/Y", strtotime($res["first_reg_date"])) : "-");
				$tmp->set("km", $res["km"]);
				$tmp->set("min_price", $res["min_price"]);
				$tmp->set("start_date", date("d-m-Y", strtotime($res["start_time"])));
				
				$e = new email;
				$e->to(preg_replace("/ .*$/", "", $res["seller_name"]), $res["seller_email"]);
				$e->subject("Du har modtaget et nyt bud på din bil");
				$e->body($tmp->html());
				$e->send();
			}
			
			// Mail til tidligere byder
			if ($res["bidder_id"] > 0 and $res["bidder_id"] != $usr->user_id)
			{
				// Henter bruger
				if ($tmpuser = $usr->get_user($res["bidder_id"]))
				{
					$tmp = new tpl("MODULE|$module|email_overbid");
					$tmp->set("image_id", $image_id);
					$tmp->set("cur_price", $bid);
					$tmp->set("auction_no", $res["auction_no"]);
					$tmp->set("brand", $res["brand"]);
					$tmp->set("model", $res["model"]);
					$tmp->set("variant", $res["variant"]);
					$tmp->set("first_reg_date", $res["first_reg_date"] != "" ? date("M/Y", strtotime($res["first_reg_date"])) : "-");
					$tmp->set("km", $res["km"]);
					$tmp->set("start_date", date("d-m-Y", strtotime($res["start_time"])));
					
					$e = new email;
					$e->to(preg_replace("/ .*$/", "", $tmpuser["name"]), $tmpuser["email"]);
					$e->subject("Du er desværre blevet overbudt");
					$e->body($tmp->html());
					$e->send();
				}
			}
			
			// Gemmer som favorit
			$db->execute("
				INSERT INTO
					" . $_table_prefix . "_module_" . $module . "_favorites
				(
					user_id,
					auction_id
				)
				VALUES
				(
					'" . $usr->user_id . "',
					'" . $db->escape($res["id"]) . "'
				)
				");
			
			if ($vars["return_url"] != "")
			{
				header("Location: " . $vars["return_url"]);
			}
			else
			{
				header("Location: /site/$_lang_id/$module/$page/show/" . $res["id"] . "#bid");
			}
			exit;
		}
		else
		{
			// Fejl ?
			echo("<script> alert('Der er opstået en uventet fejl - forsøg igen senere!'); history.back(); </script>");
			exit;
		}
			
		
	}
	elseif ($do == "search")
	{
		// Søgning, vers 10-09-2014
		
		// Søgebar
		$html .= "{MODULE|$module|$page|searchbar}";
		
		// Grupper
		$html .= "{MODULE|$module|$page|groups}";
		
	}
	elseif ($do == "searchid")
	{
		// Søg på katalognummer (id)
		
		$tmp = new tpl("MODULE|$module|searchid");
		$html .= $tmp->html();
		
	}
	elseif ($do == "search" and false)
	{
		// Søgning - GAMMEL
		
		$ajax = new ajax;
		if ($ajax->do == "search")
		{
			$response = array();
			
			// Inkluderer søgning
			$vars["auction_type"] = $ajax->values["auction_type"];
			$vars["no_vat"] = $ajax->values["no_vat"];
			$vars["no_tax"] = $ajax->values["no_tax"];
			$vars["brand"] = $ajax->values["brand"];
			$vars["year"] = $ajax->values["year"];
			$vars["fuel"] = $ajax->values["fuel"];
			$vars["km"] = $ajax->values["km"];
			
			$tmp = new tpl("{MODULE|$module|$page}");
			$ajax->response(array(
				"state" => "ok",
				"html" => $tmp->html()
				));
		}
		$html .= $ajax->html();
		
		// Mærker
		$select_brand = "";
		$db->execute("
			SELECT
				DISTINCT(brand)
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				auction_date >= '" . date("Y-m-d") . "' AND
				NOT ISNULL(auction_no) AND
				`cancel` <> '1'
			ORDER BY
				brand
			");
		while ($db->fetch_array())
		{
			$tmp = new tpl("MODULE|$module|auctions_search_select");
			$tmp->set("value", $db->array["brand"]);
			$tmp->set("title", $db->array["brand"]);
			$select_brand .= $tmp->html();
		}
		
		// Årgang
		$select_year = "";
		$db->execute("
			SELECT
				DISTINCT(`year`)
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				auction_date >= '" . date("Y-m-d") . "' AND
				NOT ISNULL(auction_no) AND
				`cancel` <> '1'
			ORDER BY
				`year`
			");
		while ($db->fetch_array())
		{
			$tmp = new tpl("MODULE|$module|auctions_search_select");
			$tmp->set("value", $db->array["year"]);
			$tmp->set("title", $db->array["year"]);
			$select_year .= $tmp->html();
		}
		
		// Brændstof
		$select_fuel = "";
		$db->execute("
			SELECT
				DISTINCT(fuel)
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				auction_date >= '" . date("Y-m-d") . "' AND
				NOT ISNULL(auction_no) AND
				`cancel` <> '1'
			ORDER BY
				fuel
			");
		while ($db->fetch_array())
		{
			$tmp = new tpl("MODULE|$module|auctions_search_select");
			$tmp->set("value", $db->array["fuel"]);
			$tmp->set("title", $db->array["fuel"]);
			$select_fuel .= $tmp->html();
		}
		
		// Km interval
		$select_km = "";
		$array_km = array(
			"0-20000",
			"20001-50000",
			"50001-100000",
			"100001-150000",
			"150001-"
			);
		for ($i = 0; $i < count($array_km); $i++)
		{
			list($min, $max) = explode("-", $array_km[$i]);
			if ($db->execute_field("
				SELECT
					id
				FROM
					" . $_table_prefix . "_module_" . $module . "_auctions
				WHERE
					auction_date >= '" . date("Y-m-d") . "' AND
					NOT ISNULL(auction_no) AND
					km >= '$min' AND
					`cancel` <> '1'
					" . ($max != "" ? " AND km <= '$max' ": "") . "
				LIMIT
					1
				"))
			{
				$tmp = new tpl("MODULE|$module|auctions_search_select");
				$tmp->set("value", $array_km[$i]);
				$tmp->set("title", $array_km[$i]);
				$select_km .= $tmp->html();
			}
		}
		
		$tmp = new tpl("MODULE|$module|auctions_search");
		$tmp->set("ajax", $ajax->group);
		$tmp->set("select_brand", $select_brand);
		$tmp->set("select_year", $select_year);
		$tmp->set("select_fuel", $select_fuel);
		$tmp->set("select_km", $select_km);
		$html .= $tmp->html();
		
	}
	elseif ($do == "groups" or $do == "groups_left")
	{
		// Grupper
		
		$ress = $db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_groups
			ORDER BY
				title
			");
		while ($res = $db->fetch_array($ress))
		{
			$count = $db->execute_field("
				SELECT
					COUNT(*)
				FROM
					" . $_table_prefix . "_module_" . $module . "_auctions
				WHERE
					group_id = '" . $res["id"] . "' AND
					auction_type = 'online' AND
					end_time >= '" . date("Y-m-d H:i") . "' AND
					start_time <= '" . date("Y-m-d H:i") . "' AND
					NOT ISNULL(auction_no) AND
					`cancel` <> '1'
				");
			$date = $db->execute_field("
				SELECT
					MAX(end_time)
				FROM
					" . $_table_prefix . "_module_" . $module . "_auctions
				WHERE
					group_id = '" . $res["id"] . "' AND
					auction_type = 'online' AND
					end_time >= '" . date("Y-m-d H:i") . "' AND
					NOT ISNULL(auction_no) AND
					`cancel` <> '1'
				");
			
			$tmp = new tpl("MODULE|$module|auctions_" . $do . "_element");
			$tmp->set("id", $res["id"]);
			$tmp->set("title", $res["title"]);
			$tmp->set("count", $count);
			$tmp->set("date", $date ? date("d-m-Y \k\l\. H:i", strtotime($date)) : "-");
			$elements .= $tmp->html();
		}
		
		$tmp = new tpl("MODULE|$module|auctions_" . $do);
		$tmp->set("elements", $elements);
		$html .= $tmp->html();
		
	}
	elseif ($do == "searchbar")
	{
		// Søgebar
		
		$ajax = new ajax;
		if ($ajax->do == "searchcount")
		{
			$vars["auction_type"] = "online";
			$vars["brand"] = $ajax->values["brand"];
			$vars["fuel"] = $ajax->values["fuel"];
			$vars["region"] = $ajax->values["region"];
			$tmp = new tpl("{MODULE|$module|$page|searchcount}");
			$ajax->response(array(
				"state" => "ok",
				"count" => $tmp->html()
				));
		}
		$html .= str_replace("typeof(ajax) != 'function'", "false", $ajax->html());

		// Mærker
		$select_brand = "";
		$db->execute("
			SELECT
				DISTINCT(brand)
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				(
					auction_date >= '" . date("Y-m-d") . "' OR
					auction_type = 'online' AND
					end_time >= '" . date("Y-m-d H:i") . "'
				)
				AND
				NOT ISNULL(auction_no) AND
				`cancel` <> '1'
			ORDER BY
				brand
			");
		while ($db->fetch_array())
		{
			$tmp = new tpl("MODULE|$module|auctions_searchbar_select");
			if ($vars["brand"] == $db->array["brand"]) $tmp->set("selected", "selected");
			$tmp->set("value", $db->array["brand"]);
			$tmp->set("title", $db->array["brand"]);
			$select_brands .= $tmp->html();
		}
		
		// Brændstof
		$select_fuel = "";
		$db->execute("
			SELECT
				DISTINCT(fuel)
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				(
					auction_date >= '" . date("Y-m-d") . "' OR
					auction_type = 'online' AND
					end_time >= '" . date("Y-m-d H:i") . "'
				)
				AND
				NOT ISNULL(auction_no) AND
				`cancel` <> '1'
			ORDER BY
				fuel
			");
		while ($db->fetch_array())
		{
			$tmp = new tpl("MODULE|$module|auctions_searchbar_select");
			if ($vars["fuel"] == $db->array["fuel"]) $tmp->set("selected", "selected");
			$tmp->set("value", $db->array["fuel"]);
			$tmp->set("title", $db->array["fuel"]);
			$select_fuel .= $tmp->html();
		}
		
		// Regioner
		$select_regions = "";
		$db->execute("
			SELECT
				r.*
			FROM
				" . $_table_prefix . "_module_" . $module . "_regions AS r
			INNER JOIN
				" . $_table_prefix . "_module_" . $module . "_auctions AS a
			ON
				a.seller_zipcode >= r.zip_from AND
				a.seller_zipcode <= r.zip_to AND
				(
					a.auction_date >= '" . date("Y-m-d") . "' OR
					a.auction_type = 'online' AND
					a.end_time >= '" . date("Y-m-d H:i") . "'
				)
				AND
				NOT ISNULL(a.auction_no) AND
				a.cancel <> '1'
			GROUP BY
				r.zip_from
			ORDER BY
				r.zip_from
			");
		while ($db->fetch_array())
		{
			$tmp = new tpl("MODULE|$module|auctions_searchbar_select");
			if ($vars["region"] == $db->array["zip_from"] . "-" . $db->array["zip_to"]) $tmp->set("selected", "selected");
			$tmp->set("value", $db->array["zip_from"] . "-" . $db->array["zip_to"]);
			$tmp->set("title", $db->array["region"]);
			$select_regions .= $tmp->html();
		}
	
		$tmp = new tpl("MODULE|$module|auctions_searchbar");
		$tmp->set("select_brands", $select_brands);
		$tmp->set("select_fuel", $select_fuel);
		$tmp->set("select_regions", $select_regions);
		$html .= $tmp->html();
		
	}
	else
	{
		// Oversigt

		$ajax = new ajax;
		if ($ajax->do == "new_bids")
		{
			// Undersøg om der er nye bud
			$auctions = explode("\n", $ajax->values["auctions"]);
			for ($i = 0; $i < count($auctions); $i++)
			{
				list($id, $cur_price) = explode("|", $auctions[$i]);
				if ($id != "" and $cur_price != "")
				{
					if ($db->execute_field("
						SELECT
							cur_price
						FROM
							" . $_table_prefix . "_module_" . $module . "_auctions
						WHERE
							id = '" . $db->escape($id) . "' AND
							NOT ISNULL(end_time) AND
							end_time > '" . date("Y-m-d H:i:s") . "' AND
							`cancel` = 0
						") != $cur_price)
					{
						$ajax->response(array("state" => "new_bids"));
					}
				}
			}
			$ajax->response(array("state" => "ok"));
		}
		if ($ajax->do == "switch_favorite")
		{
			if (!$usr->logged_in)
			{
				$ajax->response(array(
					"state" => "error",
					"message" => "Du skal være logget ind for at benytte favoritter"
					));
			}
			if ($db->execute_field("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_favorites
				WHERE
					user_id = '" . $usr->user_id . "' AND
					auction_id = '" . $db->escape($ajax->values["id"]) . "'
				"))
			{
				// Fjern
				$db->execute("
					DELETE FROM
						" . $_table_prefix . "_module_" . $module . "_favorites
					WHERE
						user_id = '" . $usr->user_id . "' AND
						auction_id = '" . $db->escape($ajax->values["id"]) . "'
					");
				$ajax->response(array(
					"state" => "ok",
					"is_favorite" => "0"
					));
			}
			else
			{
				// Tilføj
				$db->execute("
					INSERT INTO
						" . $_table_prefix . "_module_" . $module . "_favorites
					(
						user_id,
						auction_id
					)
					VALUES
					(
						'" . $usr->user_id . "',
						'" . $db->escape($ajax->values["id"]) . "'
					)
					");
				$ajax->response(array(
					"state" => "ok",
					"is_favorite" => "1"
					));
			}
		}
		if ($ajax->do == "save_gps")
		{
			$_SESSION[$module . "_gps"] = array(floatval($ajax->values["lat"]), floatval($ajax->values["lng"]));
			$ajax->response(array(
				"state" => "ok"
				));
		}
		$gps = false;
		if ($usr->logged_in)
		{
			if (!isset($_SESSION[$module . "_gps_" . $usr->user_id]))
			{
				$_SESSION[$module . "_gps_" . $usr->user_id] = OBA_get_coords($usr->data["address"] . ", " . $usr->data["zipcode"] . ", " . $usr->data["city"] . ", Danmark");
				$gps = $_SESSION[$module . "_gps_" . $usr->user_id];
			}
		}
		if (!$gps)
		{
			$gps = $_SESSION[$module . "_gps"];
		}

		// Auktionstype
		$auction_type = $vars["auction_type"];
		if (!preg_match("/^(live|online)$/", $auction_type)) $auction_type = "live";
		
		// Template
		if ($auction_type == "online")
		{
			$tplprefix = "auctions_overview_online";
		}
		else
		{
			$tplprefix = "auctions_overview";
		}
		if ($do == "dealer_favorites")
		{
			if (!$usr->logged_in)
			{
				header("Location: /site/$_lang_id/$module/dealer/login");
				exit;
			}
			$tplprefix = "dealer_favorites";
			if ($vars["order"] == "") $vars["order"] = "id";
		}
		

		$title = "";
				
		if ($do == "random")
		{
			// Tilfældige biler
			if ($auction_type == "online")
			{
				$sql_where = " WHERE auction_type = 'online' AND start_time <= '" . date("Y-m-d H:i") . "' AND end_time > '" . date("Y-m-d H:i") . "' AND NOT ISNULL(auction_no) AND `cancel` <> '1' ";
			}
			else
			{
				$sql_where = " WHERE auction_type = 'live' AND auction_date >= '" . date("Y-m-d") . "' AND NOT ISNULL(auction_no) AND ISNULL(end_time) AND `cancel` <> '1' ";
			}
			$sql_order = " ORDER BY RAND() ";
			$sql_limit = " LIMIT 0, $id ";
			$title = "";
			$bottom = "";
		}
		elseif (preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $vars["date"]) and $vars["date"] >= date("Y-m-d"))
		{
			// Dato visning
			if ($auction_type == "online")
			{
				$sql_where = " WHERE auction_type = 'online' AND start_time <= '" . date("Y-m-d H:i") . "' AND end_time > '" . date("Y-m-d H:i") . "' AND NOT ISNULL(auction_no) AND `cancel` <> '1' ";
			}
			else
			{
				$sql_where = " WHERE auction_type = 'live' AND auction_date = '" . $db->escape($vars["date"]) . "' AND NOT ISNULL(auction_no) AND ISNULL(end_time) AND `cancel` <> '1' ";
			}
			$sql_order = " ORDER BY auction_date, auction_no ";
			$sql_limit = "";
			$title = "Auktioner på dagen " . date("d-m-Y", strtotime($vars["date"]));
			
			if ($auction_type == "live")
			{
				$tmp = new tpl("MODULE|$module|auctions_download_pdf");
				$tmp->set("date", $vars["date"]);
				$bottom = $tmp->html();
			}
			else
			{
				$bottom = "";
			}
		}
		else
		{
			// Søgning
			$sql_where = "";
			if ($auction_type == "online")
			{
				$sql_order = " ORDER BY description ";
			}
			else
			{
				$sql_order = " ORDER BY auction_date, auction_no ";
			}
			$sql_limit = "";
			if ($vars["searchstring"] != "")
			{
				$keywords = explode(" ", $vars["searchstring"]);
				for ($i = 0; $i < count($keywords); $i++)
				{
					$keyword = trim($keywords[$i]);
					if ($keyword != "")
					{
						$sql_where .= "
							AND
							(
								`auction_no` = '" . $db->escape($keyword) . "' OR
								`brand` LIKE '%" . $db->escape($keyword) . "%' OR
								`model` LIKE '%" . $db->escape($keyword) . "%' OR
								`variant` LIKE '%" . $db->escape($keyword) . "%' OR
								`type` LIKE '%" . $db->escape($keyword) . "%'
							)
							";
					}
				}
				$title = "";
			}
			if ($vars["type"] != "") $sql_where .= " AND `type` LIKE '" . $db->escape($vars["type"]) . "%' ";
			if ($vars["no_vat"] != "") $sql_where .= " AND `no_vat` = '" . $db->escape($vars["no_vat"]) . "' ";
			if ($vars["no_tax"] != "") $sql_where .= " AND `no_tax` = '" . $db->escape($vars["no_tax"]) . "' ";
			if ($vars["fuel"] != "") $sql_where .= " AND `fuel` = '" . $db->escape($vars["fuel"]) . "' ";
			if ($vars["brand"] != "") $sql_where .= " AND brand = '" . $db->escape($vars["brand"]) . "' ";
			if ($vars["year"] != "") $sql_where .= " AND year = '" . $db->escape($vars["year"]) . "' ";
			if ($vars["km"] != "")
			{
				list($min, $max) = explode("-", $vars["km"]);
				$sql_where .= " AND km >= '" . $db->escape($min) . "' " .
					($max != "" ? " AND km <= '" . $db->escape($max) . "' " : "");
			}
			if (preg_match("/^([0-9]{4})-([0-9]{4})$/", $vars["region"], $arr))
			{
				$sql_where .= " AND seller_zipcode >= '" . $arr[1] . "' AND seller_zipcode <= '" . $arr[2] . "' ";
			}
			
			if ($vars["group_id"] != "" and $auction_type == "online")
			{
				$sql_where .= " AND group_id = '" . intval($vars["group_id"]) . "' ";
				$title = $db->execute_field("
					SELECT
						title
					FROM
						" . $_table_prefix . "_module_" . $module . "_groups
					WHERE
						id = '" . intval($vars["group_id"]) . "'
					");
				if (!$title)
				{
					header("Location: /");
					exit;
				}						
			}
			
			if ($sql_where == "")
			{
				if ($auction_type == "live")
				{
					$title = "Alle fysiske auktioner";
				}
			}
			
			if ($do == "dealer_favorites")
			{
				$sql_where = "
					INNER JOIN
						" . $_table_prefix . "_module_" . $module . "_favorites AS favorites
					ON
						favorites.user_id = '" . $usr->user_id . "' AND
						favorites.auction_id = id
					WHERE 
					(
						auction_type = 'online' AND start_time <= '" . date("Y-m-d H:i") . "' AND end_time > '" . date("Y-m-d H:i") . "' AND NOT ISNULL(auction_no) AND `cancel` <> '1'
						OR
						auction_type = 'live' AND auction_date >= '" . date("Y-m-d") . "' AND NOT ISNULL(auction_no) AND ISNULL(end_time) AND `cancel` <> '1'
					)
					" . $sql_where;
			}
			elseif ($auction_type == "online")
			{
				$sql_where = " WHERE auction_type = 'online' AND start_time <= '" . date("Y-m-d H:i") . "' AND end_time > '" . date("Y-m-d H:i") . "' AND NOT ISNULL(auction_no) AND `cancel` <> '1' " . $sql_where;
			}
			else
			{
				$sql_where = " WHERE auction_type = 'live' AND auction_date >= '" . date("Y-m-d") . "' AND NOT ISNULL(auction_no) AND ISNULL(end_time) AND `cancel` <> '1' " . $sql_where;
			}
			
			if (in_array($vars["order"], array("description", "first_reg_date", "km", "gps", "id", "model", "auction_type", "end_time", "status", "cur_price")))
			{
				if ($vars["order"] == "gps")
				{
					if (!$gps)
					{
						$html .= "<script> alert('Vi kunne desværre ikke genkende din placering'); </script>";
						$vars["order"] = "description";
					}
					else
					{
						//$sql_order = "ORDER BY ABS(gps_lat - " . number_format($gps[0], 10, ".", "") . " + gps_lng - " . number_format($gps[1], 10, ".", "") . ")";
						
						$sql_order = "
							ORDER BY
								IF(gps_lat = 0, 1, 0),
								(
									acos(sin(" . number_format($gps[0], 10, ".", "") . " * PI() / 180)) * 
									sin(gps_lat * PI() / 180)
									+
									cos(" . number_format($gps[0], 10, ".", "") . " * PI() / 180) * 
									cos(gps_lat * PI() / 180) * 
									cos((" . number_format($gps[1], 10, ".", "") . " - gps_lng) * PI() / 180)
								) * 180 / PI()
							";
					}
				}
				
				if ($vars["order"] == "status")
				{
					$sql_order = "ORDER BY
						IF(bidder_id <> '" . $usr->user_id . "', 0, 1),
						IF(cur_price < min_price, 0, 1)
						";
				}
				elseif ($vars["order"] == "model")
				{
					$sql_order = "ORDER BY brand, model";
				}				
				elseif ($vars["order"] != "gps")
				{
					$sql_order = "ORDER BY " . $vars["order"];
				}
			}
			
			$bottom = "";
		}
		
		if ($do == "searchcount")
		{
			// Antal resultater
			$html .= $db->execute_field("
				SELECT
					COUNT(*)
				FROM
					" . $_table_prefix . "_module_" . $module . "_auctions
				$sql_where
				");
		}
		else
		{
			// Vis resultater
			$ress = $db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_auctions
				$sql_where
				$sql_order
				$sql_limit
				");
			$auctions = "";
			while ($res = $db->fetch_array($ress))
			{
				$images = "";
				$ress2 = $db->execute("
					SELECT
						*
					FROM
						" . $_table_prefix . "_module_" . $module . "_images
					WHERE
						auction_id = '" . $res["id"] . "'
					LIMIT
						1
					");
				while ($res2 = $db->fetch_array($ress2))
				{
					$tmp = new tpl("MODULE|$module|" . $tplprefix . "_auction_image");
					$tmp->set("id", $res2["id"]);
					$images .= $tmp->html();
				}
				if ($images == "")
				{
					$tmp = new tpl("MODULE|$module|" . $tplprefix . "_auction_no_image");
					$images .= $tmp->html();
				}
				
				// Afstand i luftlinie
				if ($res["gps_lat"] != 0 and $gps)
				{
					$gps_distance = round(OBA_coords_distance($res["gps_lat"], $res["gps_lng"], $gps[0], $gps[1]), 1);
				}
				else
				{
					$gps_distance = "";
				}
				
				// Bud
				$bid_tip = "";
				if ($usr->logged_in and $res["cur_price"] > 0)
				{
					// Logget ind
					if ($res["bidder_id"] == $usr->user_id)
					{
						// Har det højeste bud
						if ($res["cur_price"] < $res["min_price"])
						{
							// Har ikke opnået mindstepris
							$tmp = new tpl("MODULE|$module|" . $tplprefix . "_bid_min_price");
							$tmp->set("cur_price", number_format($res["cur_price"], 0, ",", "."));
							$bid = $tmp->html();
							
							// Bud-tip
							if ($res["min_price"] - $res["cur_price"] <= 500)
							{
								$bid_tip = "<b>Tip:</b> Mindsteprisen er DKK " . number_format($res["min_price"] - $res["cur_price"], 0, ".", "") . " over dit bud";
							}
							elseif ($res["min_price"] - $res["cur_price"] <= 5000)
							{
								$bid_tip = "<b>Tip:</b> Mindsteprisen er under DKK 5.000 over dit bud";
							}
							else
							{
								$bid_tip = "<b>Tip:</b> Mindsteprisen er over DKK 5.000 over dit bud";
							}
						}
						else
						{
							// Mindstepris opnået
							$tmp = new tpl("MODULE|$module|" . $tplprefix . "_bid_highest");
							$tmp->set("cur_price", number_format($res["cur_price"], 0, ",", "."));
							$bid = $tmp->html();
							
							// Bud-tip
							$bid_tip = "<b>Bemærk:</b> Mindsteprisen er opnået";
						}
					}
					elseif ($max_price = $db->execute_field("
						SELECT
							MAX(bid)
						FROM
							" . $_table_prefix . "_module_" . $module . "_bids
						WHERE
							auction_id = '" . $db->escape($res["id"]) . "' AND
							bidder_id = '" . $usr->user_id . "'
						"))
					{
						// Er blevet overbudt
						$tmp = new tpl("MODULE|$module|" . $tplprefix . "_bid_overbid");
						$tmp->set("cur_price", number_format($max_price, 0, ",", "."));
						$bid = $tmp->html();
					}
					else
					{
						// Har ikke budt
						$bid = "";
					}
				}
				else
				{
					// Ikke logget ind
					$bid = "";
				}
				
				$tmp = new tpl("MODULE|$module|" . $tplprefix . "_auction");
				$tmp->set("auction_type", $res["auction_type"]);
				$tmp->set("do_bid_class", $usr->logged_in ? "" : "Hidden");
				$tmp->set("next_bid", $res["cur_price"] + 100);
				$tmp->set("bid", $bid);
				$tmp->set("bid_tip", $bid_tip);
				$tmp->set("id", $res["id"]);
				$tmp->set("auction_date", date("d-m-Y", strtotime($res["auction_date"])));
				$tmp->set("auction_no", $res["auction_no"]);
				$tmp->set("brand", stripslashes($res["brand"]));
				$tmp->set("model", stripslashes($res["model"]));
				$tmp->set("variant", stripslashes($res["variant"]));
				$tmp->set("type", stripslashes($res["type"]));
				$tmp->set("fuel", stripslashes($res["fuel"]));
				$tmp->set("doors", stripslashes($res["doors"]));
				$tmp->set("year", stripslashes($res["year"]));
				$tmp->set("km", stripslashes($res["km"]));
				$tmp->set("color", stripslashes($res["color"]));
				$tmp->set("newly_tested", $res["newly_tested"]);
				$tmp->set("is_regged", $res["is_regged"]);
				$tmp->set("service", $res["service"]);
				$tmp->set("yellow_plate", $res["yellow_plate"]);
				$tmp->set("first_reg_date", $res["first_reg_date"] != "" ? date("M/Y", strtotime($res["first_reg_date"])) : "-");
				$tmp->set("newly_tested_date", $res["newly_tested_date"] != "" ? date("M/Y", strtotime($res["newly_tested_date"])) : "-");
				$tmp->set("description", nl2br(stripslashes($res["description"])));
				$tmp->set("images", $images);
				$tmp->set("end_time", strftime("%A kl. %H:%M+", strtotime($res["end_time"])));
				$tmp->set("cur_price", $res["cur_price"] > 0 ? ("Højeste bud <b>" . number_format($res["cur_price"], 0, ",", ".") . "</b>") : "<i>Ingen bud</i>");
				$tmp->set("cur_price_numeric", $res["cur_price"]);
				$tmp->set("zipcode", $res["seller_zipcode"]);
				$tmp->set("city", $res["seller_city"]);
				$tmp->set("gps_distance", $gps_distance);
				$tmp->set("gearcount", $res["gearcount"]);
				$tmp->set("no_tax", $res["no_tax"]);
				
				if ($usr->logged_in)
				{
					$tmp->set("is_favorite", $db->execute_field("
						SELECT
							*
						FROM
							" . $_table_prefix . "_module_" . $module . "_favorites
						WHERE
							user_id = '" . $usr->user_id . "' AND
							auction_id = '" . $db->escape($res["id"]) . "'
						") ? "1" : "0");
				}
				else
				{
					$tmp->set("is_favorite", "0");
				}
				
				$auctions .= $tmp->html();
			}
	
			if ($auctions == "")
			{
				$tmp = new tpl("MODULE|$module|" . $tplprefix . "_no_auctions");
				$auctions .= $tmp->html();
			}
			
			// Mærker
			$brands = "";
			$brands_total = 0;
			$ress = $db->execute("
				SELECT
					DISTINCT(brand) AS brand
				FROM
					" . $_table_prefix . "_module_" . $module . "_auctions
				" . preg_replace("/AND brand = '[^']*'/", "", $sql_where) . "
				ORDER BY
					brand
				");
			while ($res = $db->fetch_array($ress))
			{
				$count = $db->execute_field("
					SELECT
						COUNT(*)
					FROM
						" . $_table_prefix . "_module_" . $module . "_auctions
					WHERE
						auction_type = 'online' AND
						end_time >= '" . date("Y-m-d H:i") . "' AND
						start_time <= '" . date("Y-m-d H:i") . "' AND
						NOT ISNULL(auction_no) AND
						`cancel` <> '1' AND
						brand = '" . $db->escape($res["brand"]) . "'
					ORDER BY
						brand
					");
				$brands_total += $count;
					
				$tmp = new tpl("MODULE|$module|" . $tplprefix . "_brand");
				$tmp->set("brand", $res["brand"]);
				if ($res["brand"] == $vars["brand"]) $tmp->set("active", "Active");
				$tmp->set("count", $count);
				$brands .= $tmp->html();
			}

			$html .= $ajax->html();			
			$tmp = new tpl("MODULE|$module|$tplprefix");
			$tmp->set("ajax", $ajax->group);
			$tmp->set("gps_lat", number_format($gps[0], 10, ".", ""));
			$tmp->set("gps_lng", number_format($gps[1], 10, ".", ""));
			$tmp->set("brands", $brands);
			$tmp->set("brands_total", $brands_total);
			$tmp->set("title", $title);
			$tmp->set("auctions", $auctions);
			$html .= $tmp->html() . $bottom;
		}
		
		if ($vars["print"] == "true") $tpl = "print";
	}
