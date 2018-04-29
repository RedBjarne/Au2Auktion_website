<?php
	require_once($_document_root . "/modules/$module/inc/functions.php");
	
	/*
		Finder GPS koordinater til auktioner
	*/
	$ress = $db->execute("
		SELECT
			*
		FROM
			" . $_table_prefix . "_module_" . $module . "_auctions
		WHERE
			ISNULL(gps_lat)
		");
	while ($res = $db->fetch_array($ress))
	{
		// GPS
		$gps = OBA_get_coords($res["seller_address"] . " " . $res["seller_address2"] . ", " . $res["seller_zipcode"] . " " . $res["seller_city"] . ", Danmark");
		$sql = "
			UPDATE
				" . $_table_prefix . "_module_" . $module . "_auctions
			SET
				gps_lat = '" . number_format($gps[0], 10, ".", "") . "',
				gps_lng = '" . number_format($gps[1], 10, ".", "") . "'
			WHERE
				id = '" . $res["id"] . "'
			";
		$db->execute($sql);
		OBA_sync("SQL", $sql);
	}

	
	/*
		Online auktioner, færdig og skal faktureres.
		Markeres som færdig, så admin-system automatisk opretter faktura
	*/		
	$ress = $db->execute("
		SELECT
			id
		FROM
			" . $_table_prefix . "_module_" . $module . "_auctions
		WHERE
			auction_type = 'online' AND
			NOT ISNULL(end_time) AND
			end_time < '" . date("Y-m-d H:i:00") . "' AND
			ISNULL(end_email_time) AND
			cur_price >= min_price AND
			cur_price > 0 AND
			seller_account_invoice_id = 0 AND
			invoice_id = 0 AND
			ISNULL(end_web_time)
		");
	while ($res = $db->fetch_array($ress))
	{
		/*
			Vi sætter end_email_time = NOW(), da der ikke køres med synkronisering mellem servere mere,
			dvs. serveren vil aldrig sætte tidspunktet, og der laves desuden ikke faktura automatisk,
			men det kan evt. integreres her i koden.
			
			Hvis faktureringen skal køres et andet sted, så skal end_email_time = NULL, og sættes = NOW(),
			når faktureringen er gennemført.
		*/
		
		$sql = "
			UPDATE
				" . $_table_prefix . "_module_" . $module . "_auctions
			SET
				`end_web_time` = '" . date("Y-m-d H:i:s") . "',
				end_email_time = '" . date("Y-m-d H:i:s") . "'
			WHERE
				id = '" . $res["id"] . "'
			";
		$db->execute($sql);
		OBA_sync("SQL", $sql);
	}
	
	

	/*
		Online auktioner, mail ved afslutning
	*/
	
	$ress = $db->execute("
		SELECT	
			*
		FROM
			" . $_table_prefix . "_module_" . $module . "_auctions
		WHERE
			auction_type = 'online' AND
			NOT ISNULL(end_time) AND
			end_time < '" . date("Y-m-d H:i:00") . "' AND
			ISNULL(end_email_time) AND
			(
				cur_price >= min_price AND
				cur_price > 0 AND
				seller_account_invoice_id > 0 AND
				invoice_id > 0
				OR
				cur_price < min_price
				OR
				cur_price = 0
			)
		");
	while ($res = $db->fetch_array($ress))
	{
		$sql = "
			UPDATE
				" . $_table_prefix . "_module_" . $module . "_auctions
			SET
				end_email_time = '" . date("Y-m-d H:i:s") . "'
			WHERE
				id = '" . $res["id"] . "'
			";
		if ($db->execute($sql))
		{
			OBA_sync("SQL", $sql);
			
			// Billede
			$image_id = $db->execute_field("
				SELECT
					id
				FROM
					" . $_table_prefix . "_module_" . $module . "_images
				WHERE
					auction_id = '" . $res["id"] . "'
				");
	
			echo($res["id"] . ".. ");
			
			if ($res["cur_price"] >= $res["min_price"] and $res["cur_price"] > 0)
			{
				// Solgt
				echo("solgt.. ");
				
				// Salær inkl. moms
				$salery = $db->execute_field("
					SELECT
						salery
					FROM
						" . $_table_prefix . "_module_" . $module . "_online_salery
					WHERE
						bid <= '" . $res["cur_price"] . "'
					ORDER BY
						bid DESC
					LIMIT
						0, 1
					");
				
				$db->execute("
					SELECT
						*
					FROM
						" . $_table_prefix . "_user_" . $module . "_cust
					WHERE
						id = '" . $res["bidder_id"] . "'
					");
				if ($buyer = $db->fetch_array())
				{
					// E-mail til køber
					$tmp = new tpl("MODULE|$module|email_sold_buyer");
					$tmp->set("image_id", $image_id);
					$tmp->set("cur_price", $res["cur_price"]);
					$tmp->set("auction_no", $res["auction_no"]);
					$tmp->set("brand", $res["brand"]);
					$tmp->set("model", $res["model"]);
					$tmp->set("variant", $res["variant"]);
					$tmp->set("first_reg_date", $res["first_reg_date"] != "" ? date("M/Y", strtotime($res["first_reg_date"])) : "-");
					$tmp->set("km", $res["km"]);
					$tmp->set("start_date", date("d-m-Y", strtotime($res["start_time"])));
					$tmp->set("salery", $salery);
					
					/*
					// Faktura
					$fpdf = OBA_invoice_fpdf($res["invoice_id"]);
					$tmpfile = $_document_root . "/tmp/" . uniqid(time()) . ".pdf";
					$fpdf->Output($tmpfile, "F");
					$e->attach($tmpfile, "faktura.pdf");
					*/
					
					$e = new email;
					$e->to(preg_replace("/ .*$/", "", $buyer["name"]), $buyer["email"]);
					$e->subject("Tillykke du har vundet auktionen");
					$e->body($tmp->html());
					$e->send();
					
					unlink($tmpfile);
				}
				
				// E-mail til sælger
				$tmp = new tpl("MODULE|$module|email_sold_seller");
				$tmp->set("salery", $salery);
				$subject = "Tillykke du har solgt din bil";
			}
			else
			{
				// Ikke solgt
				echo("ikke solgt.. ");
				
				// E-mail til sælger
				$tmp = new tpl("MODULE|$module|email_not_sold_seller");
				$subject = "Desværre opnåede din bil ikke mindsteprisen";
			}
			
			$tmp->set("image_id", $image_id);
			$tmp->set("cur_price", $res["cur_price"]);
			$tmp->set("auction_no", $res["auction_no"]);
			$tmp->set("brand", $res["brand"]);
			$tmp->set("model", $res["model"]);
			$tmp->set("variant", $res["variant"]);
			$tmp->set("first_reg_date", $res["first_reg_date"] != "" ? date("M/Y", strtotime($res["first_reg_date"])) : "-");
			$tmp->set("km", $res["km"]);
			$tmp->set("min_price", $res["min_price"]);
			$tmp->set("start_date", date("d-m-Y", strtotime($res["start_time"])));

			$e = new email;
			
			/*
			// Faktura
			if ($res["seller_account_invoice_id"] > 0)
			{
				$fpdf = OBA_invoice_fpdf($res["seller_account_invoice_id"]);
				$tmpfile = $_document_root . "/tmp/" . uniqid(time()) . ".pdf";
				$fpdf->Output($tmpfile, "F");
			}
			if ($res["seller_account_invoice_id"] > 0) $e->attach($tmpfile, "faktura.pdf");
			*/
			
			$e->to(preg_replace("/ .*$/", "", $res["seller_name"]), $res["seller_email"]);
			$e->subject($subject);
			$e->body($tmp->html());
			$e->send();
			
			if ($res["seller_account_invoice_id"] > 0) unlink($tmpfile);
		}
		
	}