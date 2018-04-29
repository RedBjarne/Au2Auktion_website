<?php
	/*
		Standard formular til listeredigering, tilføj, ret, slet
	*/

	// Titel på siden	
	$title = module2title($module) . " - {LANG|Henvendelser}";
	
	// Limit i oversigten
	$limit = 15;
	
	// Modul tabel
	$table = "data";
	
	$msg = new message;
	$msg->title($title);
	$html .= $msg->html();
	
	if ($do == "delete")
	{
		// Slet
	
		$db->execute("
			DELETE FROM
				" . $_table_prefix . "_module_" . $module . "_" . $table . "
			WHERE
				id = '$id'
			");
			
		header("Location: ./?module=$module&page=$page&_paging_page=" . $vars["_paging_page"] .
			"&searchstring=" . urlencode($vars["searchstring"]));
		exit;
				
	}
	else
	{
		// Oversigt
		
		// Bygger søge SQL
		$searchstring = trim($vars["searchstring"]);
		$sql_where = "";
		if ($searchstring != "")
		{
			$sql_where = "
				WHERE
					title LIKE '%" . addslashes($searchstring) . "%' OR 
					email LIKE '%" . addslashes($searchstring) . "%' OR 
					ip LIKE '%" . addslashes($searchstring) . "%' OR 
					`data` LIKE '%" . addslashes($searchstring) . "%'
				";
		}
		
		$frm = new form;
		$frm->method("get");
		$frm->submit_text = "{LANG|Søg}";
		$frm->tpl("th", "{LANG|Søg}");
		$frm->input(
			"{LANG|Søgeord}",
			"searchstring",
			$searchstring
			);
		$html .= $frm->html();
		
		$total = $db->execute_field("
			SELECT
				COUNT(*)
			FROM
				" . $_table_prefix . "_module_" . $module . "_" . $table . "
			$sql_where
			");
			
		$paging = new paging;
		$paging->limit($limit);
		$paging->total($total);
		$start = ($paging->current_page() - 1) * $limit;
		$html .= $paging->html();
		
		$tbl = new table;
		$tbl->th("{LANG|Tid}");
		$tbl->th("{LANG|Formular}");
		$tbl->th("{LANG|E-mail}");
		$tbl->th("{LANG|IP-adresse}");
		$tbl->th("{LANG|Data}");
		$tbl->th("{LANG|Slet}");
		$tbl->endrow();
		
		$db->execute("
			SELECT
				*
			FROM
				" . $_table_prefix . "_module_" . $module . "_" . $table . "
			$sql_where
			ORDER BY
				id DESC
			LIMIT
				$start, $limit
			");
		
		$first_value = "";	
		while ($res = $db->fetch_array())
		{
			$tbl->td(date("d-m-Y H:i", strtotime($res["time"])));
			$tbl->td(stripslashes($res["title"]));
			$tbl->td(stripslashes($res["email"]));
			$tbl->td($res["ip"]);
			$tbl->td(nl2br(strip_tags(stripslashes($res["data"]))));
			$tbl->choise("{LANG|Slet}", "delete", $res["id"], "{LANG|Slet henvendelse}?");
			$tbl->endrow();
		}
		
		if ($db->num_rows() == 0)
		{
			$tbl->td("{LANG|Ingen}...", 6);
		} 
		
		$html .= $tbl->html();
	}
?>