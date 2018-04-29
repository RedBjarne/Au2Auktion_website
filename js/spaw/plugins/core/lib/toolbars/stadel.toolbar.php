<?php
$items = array
(
  new SpawTbButton("stadel", "preview", "isDesignModeEnabled", "", "stadelPreviewClick"),
  new SpawTbButton("stadel", "fullscreen", "isDesignModeEnabled", "", "stadelFullScreenClick"),
  new SpawTbImage("core", "separator")
);

	// Kun for admin brugere
	if (isset($_SESSION["sess_spaw_cmselements"]) and isset($_SESSION["_user_id_admin"]))
	{
		if (is_array($_SESSION["sess_spaw_cmselements"]) and intval($_SESSION["_user_id_admin"]) > 0)
		{
			$items[] = new SpawTbDropdown("stadel", "cmselement", "isDesignModeEnabled", "", "stadelElementChange", $_SESSION["sess_spaw_cmselements"]);
			$items[] = new SpawTbImage("core", "separator");
		}
	}
?>
