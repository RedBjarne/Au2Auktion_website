<?php
	function OBA_fpdf_layout(&$fpdf, $title, $left, $right1, $right2, $invoice_lines = false)
	{
		global $_document_root, $module;
		
		// Top
		$fpdf->SetFont("Arial", "", 16);
		$fpdf->Text(15, 25, $title);
		$fpdf->Image($_document_root . "/modules/$module/img/logo.png", 140, 10, 55);
		$fpdf->Line(15, 30, 195, 30);

		// Modtager			
		$fpdf->SetFont("Arial", "", 10);
		$fpdf->SetXY(25, 40);
		$fpdf->MultiCell(100, 5, $left);
			
		// Fakturainfo
		$fpdf->SetXY(140, 40);
		$fpdf->MultiCell(50, 5, $right1);
		$fpdf->SetXY(140, 40);
		$fpdf->MultiCell(50, 5, $right2, "0", "R");
			
		$fpdf->Line(15, 66, 195, 66);
			
		if ($invoice_lines)
		{
			// Overskrift til linier
			$fpdf->SetFont("Arial", "B", 10);
			$fpdf->Text(15, 75, "Varetekst");
			$fpdf->Text(100, 75, "Antal");
			$fpdf->Text(135, 75, "Enhedspris");
			$fpdf->Text(180, 75, "Subtotal");
			$fpdf->SetFont("Arial", "", 10);
			
			// Faktura bund
			module_setting("invoice_bottom");
			$fpdf->SetFont("Arial", "", 10);
			$fpdf->SetXY(15, 277 - count(explode("\n", module_setting("invoice_bottom"))) * 4);
			$fpdf->MultiCell(180, 4, module_setting("invoice_bottom"), "0", "L");
		}
		
		// Bund
		$fpdf->Line(15, 280, 195, 280);
		$fpdf->SetFont("Arial", "", 10);
		$fpdf->SetXY(15, 281);
		$fpdf->MultiCell(180, 5, module_setting("company_name") . " · " .
			module_setting("company_address") . " · " .
			module_setting("company_zipcity") . " · " .
			"CVR-nr. " . module_setting("company_vat"), "0", "C");
			
		$fpdf->Image($_document_root . "/modules/$module/img/logo.png", 140, 10, 55);
		
		$fpdf->SetFont("Arial", "", 10);
	}

	// Faktura -> PDF
	function OBA_invoice_fpdf($id, $copy = false)
	{
		global $_table_prefix, $module, $_document_root;
		$db = new db;
		
		$id = intval($id);
		
		// FPDF
		require_once($_document_root . "/modules/$module/fpdf/fpdf.php");

		// Instanciation of inherited class
		$fpdf = new FPDF('P', 'mm', 'A4');
		$fpdf->SetAutoPageBreak(false);
		$fpdf->SetTextColor(0, 0, 0);
		$fpdf->AddPage();

		if ($copy)
		{
			$fpdf->SetTextColor(230, 230, 230);
			$fpdf->SetFont("Arial", "B", 100);
			$fpdf->Text(60, 90, "KOPI");
			$fpdf->SetTextColor(0, 0, 0);
		}		
		
		if ($db->execute_field("
			SELECT
				SUM(quantity * price)
			FROM
				" . $_table_prefix . "_module_" . $module . "_invoices_lines
			WHERE
				invoice_id = '$id'
			") < 0)
		{
			$type = "Kreditnota";
		}
		else
		{
			$type = "Faktura";
		}
		
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_invoices
			WHERE
				id = '$id'
			");
		if ($res = $db->fetch_array())
		{
			OBA_fpdf_layout($fpdf, strtoupper($type), stripslashes($res["name"]) . "\r\n" .
				stripslashes($res["address"]) . "\r\n" . 
				stripslashes($res["zipcode"] . " " . $res["city"]), ($res["invoice_no"] > 0 ? ($type . "nr.:") : "") . "\r\n" .
				$type . "dato:\r\n" .
				"Side:", ($res["invoice_no"] > 0 ? $res["invoice_no"] : "") . "\r\n" .
				date("d-m-Y", strtotime($res["invoice_date"])) . "\r\n" .
				"1", true);
			
			// Linier
			$y = 78;
			$ress2 = $db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_invoices_lines
				WHERE
					invoice_id = '$id'
				ORDER BY
					id
				");
			$lines = "";
			$total = 0;
			$vat_base = 0;
			$pagecount = 1;
			while ($res2 = $db->fetch_array($ress2))
			{
				$lines = explode("\n", trim(stripslashes($res2["title"])));
			
				if ($y > 270 - count($lines) * 5)
				{
					// Ny side
					$fpdf->Text(173, $y + 5, "...fortsættes...");
					
					// Tilføjer ny side
					$pagecount++;
					$fpdf->AddPage();
					
					OBA_fpdf_layout($fpdf, "FAKTURA", "", ($res["invoice_no"] > 0 ? "Fakturanr.:" : "") . "\r\n" .
						"Fakturadato:\r\n" .
						"Side:", ($res["invoice_no"] > 0 ? $res["invoice_no"] : "") . "\r\n" .
						date("d-m-Y", strtotime($res["invoice_date"])) . "\r\n" .
						$pagecount, true);
						
					$y = 78;
					
				}
				
				$fpdf->SetXY(95, $y);
				$fpdf->MultiCell(15, 5, $res2["quantity"], "0", "R");
				
				$fpdf->SetXY(115, $y);
				$fpdf->MultiCell(40, 5, OBA_price($res2["price"]), "0", "R");
				
				$fpdf->SetXY(160, $y);
				$fpdf->MultiCell(35, 5, OBA_price($res2["price"] * $res2["quantity"]), "0", "R");
				
				if ($res2["no_vat"] == 1)
				{
					$fpdf->SetXY(194, $y);
					$fpdf->MultiCell(10, 5, "*");
				}
				
				for ($i = 0; $i < count($lines); $i++)
				{
					$fpdf->SetXY(15, $y);
					$fpdf->MultiCell(75, 5, $lines[$i]);
					$y += 4;
				}
				
				$y += 2;
				
				$total += ($res2["price"] * $res2["quantity"]);
				if ($res2["no_vat"] != 1) $vat_base += ($res2["price"] * $res2["quantity"]);
			}

			// Total	
			$y += 5;		
			if ($y > 270)
			{
				// Ny side
				$fpdf->Text(173, $y + 5, "...fortsættes...");
				
				// Tilføjer ny side
				$pagecount++;
				$fpdf->AddPage();
				
				OBA_fpdf_layout($fpdf, "FAKTURA", "", ($res["invoice_no"] > 0 ? "Fakturanr.:" : "") . "\r\n" .
					"Fakturadato:\r\n" .
					"Side:", ($res["invoice_no"] > 0 ? $res["invoice_no"] : "") . "\r\n" .
					date("d-m-Y", strtotime($res["invoice_date"])) . "\r\n" .
					$pagecount, false);
					
				$y = 78;
				
			}
			
			if ($total != $vat_base)
			{
				$fpdf->SetXY(15, $y);
				$fpdf->MultiCell(40, 5, "* = momsfritaget");
			}
			
			$fpdf->SetFont("Arial", "B");
			$fpdf->SetXY(115, $y);
			$fpdf->MultiCell(40, 5, "Total uden moms:\r\n" .
				"Moms (" . module_setting("vat_pct") . "%):\r\n" .
				"Total inkl. moms:", "0", "R");
			$fpdf->SetXY(160, $y);
			$fpdf->MultiCell(35, 5, OBA_price($total) . "\r\n" .
				OBA_price($vat_base / 100 * intval(module_setting("vat_pct"))) . "\r\n" .
				OBA_price($total + $vat_base / 100 * intval(module_setting("vat_pct"))), "0", "R");
		}
		
		return $fpdf;
	}

	// Beregner afstand i luftlinie
	function OBA_coords_distance($lat1, $lon1, $lat2, $lon2)
	{
	  $theta = $lon1 - $lon2;
	  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	  $dist = acos($dist);
	  $dist = rad2deg($dist);
	  $distance = $dist * 60 * 1.1515;
	  return round($distance * 1.609344, 3);
	}

	// Henter GPS koordinater ud fra adresse
	function OBA_get_coords($address)
	{
		$url = "http://maps.google.com/maps/api/geocode/json?address=" .
			urlencode(utf8_encode($address)) .
			"&sensor=false";
		$json = json_decode(file_get_contents($url));
		$gps_lng = $json->results[0]->geometry->location->lng;
		$gps_lat = $json->results[0]->geometry->location->lat;
		if (preg_match("/^[0-9,\.\-]+$/", $gps_lat) and preg_match("/^[0-9,\.\-]+$/", $gps_lng))
		{
			return array($gps_lat, $gps_lng);
		}
		else
		{
			return false;
		}		
	}

	// Funktion, der laver tekst-liste (en værdi pr linie) til select <option> i html
	function OBA_list2select_html($list, $value = "")
	{
		$lines = explode("\n", $list);
		$html = "<option value=\"\">Vælg</option>";
		$found = false;
		for ($i = 0; $i < count($lines); $i++)
		{
			$line = trim($lines[$i]);
			if ($line == $value) $found = true;
			if ($line != "")
			{
				$html .= "<option value=\"$line\" " . ($line == $value ? "selected" : "") . ">$line</option>";
			}
		}
		if (!$found and trim($value) != "") $html .= "<option value=\"$value\" selected>$value</option>";
		return $html;
	}
	
	// Funktion, der laver select <option> i html med interval
	function OBA_numberselect_html($from, $to, $value = "", $interval = 1, $k_seperator = true, $suffix = "", $skip_first_value = false)
	{
		if ($interval == 0) $interval = 1;
		$found = false;
		$html = "";
		if (!$skip_first_value) $html .= "<option value=\"\">Vælg</option>";
		for ($i = $from; $to > $from ? $i <= $to : $i >= $to; $i = $i + (abs($interval) * ($to > $from ? 1 : -1)))
		{
			$html .= "<option value=\"$i\" " . ($value == $i ? "selected" : "") . ">" . number_format($i, 0, ",", $k_seperator ? "." : "") . $suffix . "</option>";
			if ($value == $i) $found = true;
		}
		if (!$found and trim($value) != "") $html .= "<option value=\"$value\" selected>" . $value . $suffix . "</option>";
		return $html;
	}

	// Funktion, der laver select array med interval
	function OBA_numberselect($from, $to, $value = "", $interval = 1, $k_seperator = true)
	{
		if ($interval == 0) $interval = 1;
		$found = false;
		$arr = array(array("", "Vælg"));
		for ($i = $from; $to > $from ? $i <= $to : $i >= $to; $i = $i + (abs($interval) * ($to > $from ? 1 : -1)))
		{
			$arr[] = array($i, number_format($i, 0, ",", $k_seperator ? "." : ""));
			if ($value == $i) $found = true;
		}
		if (!$found and trim($value) != "") $arr[] = array($value, number_format($value, 0, ",", $k_seperator ? "." : ""));
		return $arr;
	}

	// Funktion, der laver tekst-liste (en værdi pr linie) til select array
	function OBA_list2select($list, $value = "")
	{
		$lines = explode("\n", $list);
		$arr = array(array("", "Vælg"));
		$found = false;
		for ($i = 0; $i < count($lines); $i++)
		{
			$line = trim($lines[$i]);
			if ($line == $value) $found = true;
			if ($line != "")
			{
				$arr[] = array($line, $line);
			}
		}
		if (!$found and trim($value) != "") $arr[] = array($value, $value);
		return $arr;
	}
	
	function OBA_regno_lookup($regno)
	{
		$data = array();
		
		// Henter fra biltorvet
		$html = utf8_decode(file_get_contents("http://www.biltorvet.dk/MinBil/Opslag/" . urlencode($regno)));
		
		// Finder data
		$pos1 = strpos($html, "<div class=\"row");
		$pos2 = strpos($html, "<div class=\"row", $pos1 + 1);
		while ($pos1 !== false and $pos2 > $pos1)
		{
			$subhtml = substr($html, $pos1, $pos2 - $pos1);
			$html = substr($html, $pos2);

			$subhtml = strip_tags($subhtml);
			
			list($key, $val) = explode(":", $subhtml, 2);
			
			$val = trim($val);
			$pos = strpos($val, "\n");
			if ($pos > 0) $val = trim(substr($val, 0, $pos));
			
			$key = trim($key);
			if ($key == "Registreringsnummer") $key = "regno";
			if ($key == "Betegnelse")
			{
				list($brand, $model, $variant) = explode(" ", $val, 3);
				if ($brand != "") $data["brand"] = $brand;
				if ($model != "") $data["model"] = $model;
				if ($variant != "") $data["variant"] = $variant;
				
				// Afgør om det er diesel eller ej
				$data["fuel"] = (preg_match("/ (TDI|SDI|CDI|GTD)/", $val) ? "Diesel" : "Benzin");
			}			
			if ($key == "Stelnummer") $key = "chasno";
			if ($key == "Type") $key = "type";
			if ($key == "Dato for første indregistering")
			{
				$key = "year";
				$val = substr($val, 6, 4);
			}			
			if ($key == "Dato for seneste godkendelse ved syn")
			{
				$data["newly_tested"] = ($val > date("Y-m-d", strtotime("-6 month")) ? 1 : 0);
				$key = "newly_tested_date";
			}
			
			$data[$key] = $val;
			
			$pos1 = strpos($html, "<div class=\"row");
			$pos2 = strpos($html, "<div class=\"row", $pos1 + 1);
		}
		
		/*
		// Henter fra trafikstyrelsen
		$html = utf8_decode(file_get_contents("http://selvbetjening.trafikstyrelsen.dk/Sider/resultater.aspx?Reg=" . urlencode($regno)));
		
		// Finder data
		while (preg_match("/<div class=\"pairName\">([^<]+)<\/div>[^<]*<div class=\"pairValue\">([^<]+)<\/div>/m", $html, $array))
		{
			$html = str_replace($array[0], "", $html);
			if ($array[1] == "Mærke") $array[1] = "brand";
			if ($array[1] == "Model") $array[1] = "model";
			if ($array[1] == "Stelnummer") $array[1] = "vinno";
			if ($array[1] == "Seneste reg.nr.") $array[1] = "regno";
			$data[$array[1]] = $array[2];
		}
		*/
		
		return $data;
	}

	function OBA_sync_sql_row($table, $id, $key = "id")
	{
		return; // Deaktiveret, da databaserne er lagt sammen til én 
		
		$db = new db;
		
		// Henter felter
		$ress = $db->execute("SHOW FIELDS FROM `$table`");
		$array_fields = array();
		while ($res = $db->fetch_array($ress)) $array_fields[] = $res["Field"];
		
		$ress = $db->execute("SELECT * FROM `$table` WHERE `$key` = '" . $db->escape($id) . "'");
		if ($res = $db->fetch_array($ress))
		{
			OBA_sync("SQL", "DELETE FROM `$table` WHERE `$key` = '" . $db->escape($res[$key]) . "'");
			
			// Bygger SQL
			$sql_fields = "";
			$sql_values = "";
			for ($i = 0; $i < count($array_fields); $i++)
			{
				if ($sql_fields != "") $sql_fields .= ",";
				if ($sql_values != "") $sql_values .= ",";
				$sql_fields .= "`" . $array_fields[$i] . "`";
				$sql_values .= "'" . $db->escape($res[$array_fields[$i]]) . "'";
			}
			OBA_sync("SQL", "INSERT INTO `$table` ($sql_fields) VALUES ($sql_values)");
		}
	}

	function OBA_price($price)
	{
		return "DKK " . number_format($price, 2, ",", ".");
	}

	function OBA_id()
	{
		global $_table_prefix, $module;
		$db = new db;
		$db->execute("LOCK TABLES " . $_table_prefix . "_settings_module WRITE");
		$id = intval(module_setting("id")) + 1;
		module_setting("id", $id);
		$db->execute("UNLOCK TABLES");
		return "O" . $id;
	}

	function OBA_sync($action, $data)
	{
		return; // Deaktiveret, da databaserne er lagt sammen til én 
		
		global $_table_prefix, $module;
		$db = new db;
		$db->execute("
			INSERT INTO
				" . $_table_prefix . "_module_" . $module . "_sync
			(
				`action`,
				`data`
			)
			VALUES
			(
				'" . $db->escape($action) . "',
				'" . $db->escape($data) . "'
			)
			");
	}
