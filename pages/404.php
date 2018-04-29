<?php
	/*
		404 fejlside
	*/
	
	$tpl = "404";
	
	$breadcrumb = breadcrumb(array(
		array("{LANG|Fejlside}", $_SERVER["REQUEST_URI"])
		));
?>