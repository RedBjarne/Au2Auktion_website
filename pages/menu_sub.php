<?php
	global $vars;

	if ($do == "force")
	{
		// Brug angivet id
	}
	elseif (($vars["module"] == "" and ($vars["page"] == "default" or $vars["page"] == ""))) 
	{
		$sub_id = intval($vars["id"]);
		if ($sub_id == 0)
		{
			// Forside
			$id = $db->execute_field("
				SELECT
					id
				FROM
					" . $_table_prefix . "_pages_
				WHERE
					frontpage = '1' AND
					lang_id = '$_lang_id'
				");
		}
		else
		{
			while ($sub_id > 0)
			{
				$db->execute("
					SELECT
						id,
						sub_id
					FROM
						" . $_table_prefix . "_pages_
					WHERE
						id = '$sub_id' OR
						frontpage = '1'
					ORDER BY
						IF(id  = '$sub_id', 0, 1)
					");
				if ($db->fetch_array())
				{
					if ($db->array["sub_id"] > 0)
					{
						$sub_id = $db->array["sub_id"];
					}
					else
					{
						$id = $db->array["id"];
						$sub_id = 0;
					}
				}
			}
		}
	}
	else
	{
		$id = intval($_SESSION["last_active_sub_page_id"]);
	}

	$_SESSION["last_active_sub_page_id"] = $id;
	
	if ($id > 0)
	{
		$html .= pages_show($id, true);
	}
?>