<?
	/*
		Beskrivelse:	Opbygger sitemap liste til brug i Google sitemap
		11-05-2007:		Første udgave
		28-09-2007:		Gemmer nu en cache ud fra domænenavn
	*/
	
	// Cache af sitemap
	$cache = $_document_root . "/tmp/google_sitemap_" . $_SERVER["HTTP_HOST"] . ".xml";
	
	if (is_file($cache) and filemtime($cache) < time() - 3600) @unlink($cache);
	
	if (!is_file($cache))
	{
		// Convert
		$cnv = new convert;
		
		// Henter sitemap
		$sitemap = sitemap_array();
		
		// Laver liste
		$elements = "";
		for ($i = 0; $i < count($sitemap); $i++)
		{
			$url = $sitemap[$i]["url"];
			$date = $sitemap[$i]["date"];
			$changefreq = $sitemap[$i]["changefreq"];
			$priority = $sitemap[$i]["priority"];
			
			if (!eregi("^(http|https)://", $url)) $url = $_site_url . "/" . eregi_replace("^/", "", $url);
			if (!$date or $date == "") $date = date("Y-m-d");
			if (!$changefreq or $changefreq == "") $changefreq = "daily";
			if (!$priority or $priority == "") $priority = "0.5";
			
			$tmp = new tpl("google_sitemap_element");
			$tmp->set("url", $cnv->xmlentities($url));
			$tmp->set("date", $cnv->xmlentities($date));
			$tmp->set("changefreq", $cnv->xmlentities($changefreq));
			$tmp->set("priority", $cnv->xmlentities($priority));
			$elements .= $tmp->html();
		}
		
		// Færdiggør sitemap
		$tmp = new tpl("google_sitemap");
		$tmp->set("elements", $elements);
		
		if ($fp = fopen($cache, "w"))
		{
			fwrite($fp, utf8_encode($tmp->html()));
			fclose($fp);
		}
	}
	
	// Headers
	header("Content-type: text/xml; charset=utf-8");
	
	// Viser XML
	die(file_get_contents($cache));
?>