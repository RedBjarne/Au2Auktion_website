<?php
	/*
		Live auktion
	*/
	
	// Angiv om der skal vises test auktioner
	$test = false; //($_SERVER["REMOTE_ADDR"] == gethostbyname("privat.stadel.dk"));
	
	$usr = new user($module . "_cust");
	if (!$usr->logged_in)
	{
		header("Location: /site/$_lang_id/$module/user/login");
		exit;
	}
	
	require_once($_document_root . "/modules/$module/inc/functions.php");
	
	$a = new ajax;
	if ($a->do == "get_prev_and_next")
	{
		$response = array("state" => "ok");
		
		// Forrige auktioner
		$prev_count = 0;
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				auction_date = '" . date("Y-m-d") . "' AND
				NOT ISNULL(start_time) AND
				NOT ISNULL(end_time) AND
				NOT ISNULL(auction_no)
			ORDER BY
				auction_no DESC
			LIMIT
				0, 6
			");
		while ($db->fetch_array())
		{
			$response["prev_id" . $prev_count] = $db->array["id"];
			$response["prev_auction_no" . $prev_count] = $db->array["auction_no"];
			$response["prev_brand" . $prev_count] = $db->array["brand"];
			$response["prev_model" . $prev_count] = $db->array["model"];
			$response["prev_variant" . $prev_count] = $db->array["variant"];
			$response["prev_fuel" . $prev_count] = $db->array["fuel"];
			$response["prev_year" . $prev_count] = $db->array["year"];
			$response["prev_cur_price" . $prev_count] = $db->array["cur_price"];
			$response["prev_min_price" . $prev_count] = $db->array["min_price"];
			$response["prev_bidder_id" . $prev_count] = $db->array["bidder_id"];
			$response["prev_cancel" . $prev_count] = $db->array["cancel"];
			$prev_count++;
		}
		$response["prev_count"] = $prev_count;

		// Næste auktioner
		$next_count = 0;
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				" . ($test ? "" : ("auction_date = '" . date("Y-m-d") . "' AND")) . "
				ISNULL(start_time) AND
				ISNULL(end_time) AND
				NOT ISNULL(auction_no)
			ORDER BY
				auction_no
			LIMIT
				0, 6
			");
		while ($db->fetch_array())
		{
			$response["next_id" . $next_count] = $db->array["id"];
			$response["next_auction_no" . $prev_count] = $db->array["auction_no"];
			$response["next_brand" . $next_count] = $db->array["brand"];
			$response["next_model" . $next_count] = $db->array["model"];
			$response["next_variant" . $next_count] = $db->array["variant"];
			$response["next_fuel" . $next_count] = $db->array["fuel"];
			$response["next_year" . $next_count] = $db->array["year"];
			$response["next_cur_price" . $next_count] = $db->array["cur_price"];
			$response["next_min_price" . $next_count] = $db->array["min_price"];
			$next_count++;
		}
		$response["next_count"] = $next_count;
		
		$a->response($response);
	}
	if ($a->do == "get_auction")
	{
		$db->execute("
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
				" . ($test ? "" : ("auc.auction_date = '" . date("Y-m-d") . "' AND")) . "
				auc.id = '" . $db->escape($a->values["id"]) . "'
			");
		if ($res = $db->fetch_array())
		{
			$response = array(
				"state" => "ok",
				"auction_date" => $res["auction_date"],
				"auction_no" => $res["auction_no"],
				"regno" => $res["regno"],
				"brand" => $res["brand"],
				"model" => $res["model"],
				"variant" => $res["variant"],
				"type" => $res["type"],
				"fuel" => $res["fuel"],
				"doors" => $res["doors"],
				"year" => $res["year"],
				"km" => $res["km"],
				"color" => $res["color"],
				"newly_tested" => $res["newly_tested"],
				"newly_tested_date" => ($res["newly_tested_date"] != "" ? date("d-m-Y", strtotime($res["newly_tested_date"])) : "-"),
				"is_regged" => $res["is_regged"],
				"service" => $res["service"],
				"first_reg_date" => ($res["first_reg_date"] != "" ? date("d-m-Y", strtotime($res["first_reg_date"])) : "-"),
				"description" => $res["description"],
				"min_price" => $res["min_price"],
				"cur_price" => $res["cur_price"],
				"bidder_id" => $res["bidder_id"],
				"category_id" => $res["category_id"],
				"category_type" => $res["category_type"],
				"category_title" => $res["category_title"],
				"type_id" => $res["type_id"],
				"no_vat" => $res["no_vat"],
				"yellow_plate" => $res["yellow_plate"],
				"chasno" => $res["chasno"],
				"no_tax" => $res["no_tax"],
				"cancel" => $res["cancel"]
				);
				
			$images = "";
			$db->execute("
				SELECT
					*
				FROM
					" . $_table_prefix . "_module_" . $module . "_images
				WHERE
					auction_id = '" . $db->escape($a->values["id"]) . "'
				");
			while ($db->fetch_array())
			{
				if ($images != "") $images .= "|";
				$images .= $db->array["id"];
			}
			
			$response["images"] = $images;
		}
		else
		{
			$response = array(
				"state" => "none"
				);
		}
		
		$a->response($response);
	}
	if ($a->do == "refresh")
	{
		// Opdaterer online antal
		if (intval(module_setting("online_count_time")) < time() - 15)
		{
			module_setting("online_count_time", time());
			$count = $db->execute_field("
				SELECT
					COUNT(*)
				FROM
					" . $_table_prefix . "_user_" . $module . "_cust
				WHERE
					login_time > '" . date("Y-m-d H:i:s", strtotime("-15 second")) . "'
				");
			module_setting("online_count", $count);
			OBA_sync("ONLINE", $count);
		}
		
		// Test
		if ($test)
		{
			// Finder tilfældig auktion til test
			if (!isset($_SESSION[$module . "_test_timeout"]) or $_SESSION[$module . "_test_timeout"] < time())
			{
				$cur_auction_id = $db->execute_field("
					SELECT
						id
					FROM
						" . $_table_prefix . "_module_" . $module . "_auctions
					WHERE
						auction_date >= '" . date("Y-m-d") . "'
					ORDER BY
						RAND()
					LIMIT
						0, 1
					");
				$_SESSION[$module . "_test_timeout"] = time() + 10;
				$_SESSION[$module . "_test_id"] = $cur_auction_id;
			}
			else
			{
				$cur_auction_id = $_SESSION[$module . "_test_id"];
			}
		}
		else
		{
			$cur_auction_id = module_setting("cur_auction_id");
		}
		
		// Henter aktuel auction
		$db->execute("
			SELECT
				cur_price,
				bidder_id
			FROM
				" . $_table_prefix . "_module_" . $module . "_auctions
			WHERE
				id = '" . $db->escape($cur_auction_id) . "'
			");
		$res = $db->fetch_array();
		
		// Henter brugers aktuelle bud
		$my_bid = intval($db->execute_field("
			SELECT
				MAX(bid)
			FROM
				" . $_table_prefix . "_module_" . $module . "_bids
			WHERE
				auction_id = '" . $db->escape($cur_auction_id) . "' AND
				bidder_id = '" . $usr->user_id . "'
			"));

		$a->response(array(
			"state" => "ok",
			"time" => date("d-m-Y H:i:s"),
			"prev_auction_id" => module_setting("prev_auction_id"),
			"current_auction_id" => $cur_auction_id,
			"next_auction_id" => module_setting("next_auction_id"),
			"cur_price" => intval($res["cur_price"]),
			"is_winner" => ($res["bidder_id"] == $usr->user_id ? "1" : "0"),
			"my_bid" => $my_bid
			));
	}
	if ($a->do == "bid")
	{
		// Byd på auktion
		$id = $a->values["id"];
		$bid = intval($a->values["bid"]);
		if ($id == module_setting("cur_auction_id") and module_setting("cur_auction_id") != "")
		{
			// Henter auktion
			$db->execute("
				SELECT
					id,
					cur_price
				FROM
					" . $_table_prefix . "_module_" . $module . "_auctions
				WHERE
					id = '" . $db->escape($id) . "'
				");
			$res = $db->fetch_array();
		}
		else
		{
			$res = false;
		}
		
		if ($res)
		{
			// Auktion er ok
			
			// Tjekker bud
			if ($bid >= $res["cur_price"] + 500)
			{
				// Bud er ok
				
				// Sender bud til admin server
				OBA_sync("BID", $res["id"] . "|" . $usr->user_id . "|" . $bid);
				
				$a->response(array(
					"state" => "ok",
					"message" => "Dit bud bekræftes - vent..."
					));
			}
			else
			{
				// Bud er for lavt
				$a->response(array(
					"state" => "ok",
					"message" => "Dit bud er for lavt",
					"cur_price" => $res["cur_price"]
					));
			}
		}
		else
		{
			// Auktion er afsluttet
			$a->response(array(
				"state" => "ok",
				"message" => "Angivne auktion er lukket"
				));
		}
	}
	$html .= $a->html();
	
	$tmp = new tpl("MODULE|$module|live");
	$tmp->set("user_id", $usr->user_id);
	$tmp->set("ajax", $ajax->group);
	$html .= $tmp->html();
