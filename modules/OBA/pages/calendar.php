<?php
	if (preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $vars["date"]))
	{
		$date = date("Y-m-01 12:00:00", strtotime($vars["date"]));
	}
	else
	{
		$date = date("Y-m-01 12:00:00");
	}

	$dates = array();
	$ress = $db->execute("
		SELECT
			`date`
		FROM
			" . $_table_prefix . "_module_" . $module . "_dates
		WHERE
			MONTH(`date`) = MONTH('$date') AND
			YEAR(`date`) = YEAR('$date') AND
			`date` >= '" . date("Y-m-d") . "'
		ORDER BY
			`date`
		");
	while ($res = $db->fetch_array($ress)) $dates[$res["date"]] = true;
	
	$days = "";
	$ts = strtotime($date);
	$w = date("w", $ts);
	if ($w == 0) $w = 7;
	for ($i = 1; $i < $w; $i++)
	{
		$tmp = new tpl("MODULE|$module|calendar_empty_day");
		$days .= $tmp->html();
	}
	
	$month = date("m", $ts);
	while (date("m", $ts) == $month)
	{
		$tmp = new tpl("MODULE|$module|calendar_day" . (isset($dates[date("Y-m-d", $ts)]) ? "_auction" : ""));
		$tmp->set("day", date("d", $ts));
		$tmp->set("date", date("Y-m-d", $ts));
		$days .= $tmp->html();
		
		// Søndag?
		if (date("w", $ts) == 0)
		{
			$tmp = new tpl("MODULE|$module|calendar_new_row");
			$days .= $tmp->html();
		}
		
		// Næste dag
		$ts += 86400;
	}
	
	$tmp = new tpl("MODULE|$module|calendar");
	
	// Forrige måned
	if (date("Y-d", strtotime($date . " -1 month")) >= date("Y-m"))
	{
		$tmp->set("date_prev", date("Y-m-d", strtotime($date . " -1 month")));
		$tmp->set("month_prev", strftime("%B", strtotime($date . " -1 month")));
		$tmp->set("year_prev", strftime("%Y", strtotime($date . " -1 month")));
	}
	
	// Næste måned
	if (date("Y-m", strtotime($date . " +1 month")) <= date("Y-m", strtotime("+6 month")))
	{
		$tmp->set("date_next", date("Y-m-d", strtotime($date . " +1 month")));
		$tmp->set("month_next", strftime("%B", strtotime($date . " +1 month")));
		$tmp->set("year_next", strftime("%Y", strtotime($date . " +1 month")));
	}
	
	$tmp->set("month", strftime("%B", strtotime($date)));
	$tmp->set("year", strftime("%Y", strtotime($date)));
	$tmp->set("days", $days);
	$html .= $tmp->html();