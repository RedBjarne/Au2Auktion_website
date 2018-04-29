<?php
	include("../../inc/config.php");
	$spaw_root = $_document_root . "/js/spaw/";
	include($spaw_root . "spaw_control.class.php");
	$vars = $_SERVER["REQUEST_METHOD"] == "POST" ? $_POST : $_GET;
	$id = $vars["id"];
	$do = $vars["do"];
	$data = stripslashes($vars["data"]);
	$css = $vars["css"];
	
	if ($do == "showSpaw")
	{
		$spaw_lang = "dk";
		if (!is_file($_document_root . "/js/spaw/plugins/core/lib/lang/" .
			$spaw_lang . ".lang.inc.php")) $spaw_lang = "en";
		$SPAW_Wysiwyg = new SPAW_Wysiwyg(
			"data",
			$data,
			$spaw_lang,
			'full',
			'',
			'100%',
			'425',
			$css,
			''
			);
		$html = "
			<form action=\"popup_wysiwyg.php\" method=\"post\" onsubmit=\"document.getElementById('submitbutton').disabled = true;\">
			<input type=\"hidden\" name=\"id\" value=\"$id\">
			<input type=\"hidden\" name=\"do\" value=\"closeSpaw\">
			" . $SPAW_Wysiwyg->getHtml() . "
			<div align=\"right\">
				<input type=\"submit\" value=\"Gem\" id=\"submitbutton\">
			</div>
			</form>
			<script type=\"text/javascript\">
			function ResizeSpaw()
			{
				
			}
			
			onresize = ResizeSpaw;
			</script>
			";
	}
	elseif ($do == "closeSpaw")
	{
		$html = "
			<input type=\"hidden\" id=\"SpawData\" value=\"" . htmlentities($data) . "\">
			<script type=\"text/javascript\">
			try
			{
				window.opener.document.getElementById('$id').value = document.getElementById('SpawData').value;
				try
				{
					window.opener." . $id . "_obj.updatePageDoc(window.opener." . $id . "_obj.getActivePage());
				}
				catch(e)
				{
					// dummy
				}
				close();
			}
			catch(e)
			{
				// dummy
				document.write('Fejl opstået - kontroller at du ikke har lukket administrationen');
			}
			</script>
			";
	}
	else
	{
		$html = "
			<form action=\"popup_wysiwyg.php\" method=\"post\" id=\"SpawForm\">
			<input type=\"hidden\" name=\"id\" value=\"$id\">
			<input type=\"hidden\" name=\"do\" value=\"showSpaw\">
			<input type=\"hidden\" name=\"data\" id=\"SpawData\" value=\"\">
			<input type=\"hidden\" name=\"css\" value=\"$css\">
			</form>
			<script type=\"text/javascript\">
			try
			{
				document.getElementById('SpawData').value = window.opener.document.getElementById('$id').value;
				document.getElementById('SpawForm').submit();
			}
			catch(e)
			{
				// dummy
				document.write('Fejl opstået - kontroller at du ikke har lukket administrationen');
			}
			</script>
			";
	}
?>

<html>

<head>
	<title>WYSIWYG <?=$id?></title>
	<link rel="stylesheet" href="<?=$css?>">
</head>

<body style="margin: 5px;">

<?=$html?>

</body>

</html>