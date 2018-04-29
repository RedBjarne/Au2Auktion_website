<?php
	/*
		Sitemap
	*/
	
	$sitemap = array();
	
	if (module_setting("nonpublic_sitemap") != 1)
	{
		$sql_where = " WHERE `public` = '1' ";
	}
	else
	{
		$sql_where = "";
	}

	// WYSIWYG sider
	$ress1 = $db->execute("
		SELECT
			*
		FROM
			" . $_table_prefix . "_module_" . $module . "_pages
		$sql_where
		ORDER BY
			title
		");
	while ($res1 = $db->fetch_array($ress1))
	{
		$sitemap[count($sitemap)] = array(
			"title"			=> stripslashes($res1["title"]),
			"description"	=> strip_tags(stripslashes($res1["html_" . $_lang_id])),
			"keywords"		=> stripslashes($res1["keywords_" . $_lang_id]),
			"url"			=> $_site_url . "/site/$_lang_id/$module/default/show/" . $res1["id"],
			"date"			=> date("Y-m-d"),
			"content"		=> "{MODULE|$module|default|show|" . $res1["id"] . "}"
			);
	}
?>