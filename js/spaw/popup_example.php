<html>

<head>
	<title>Eksempel <?=$id?></title>
	<link rel="stylesheet" href="<?=$_GET["css"]?>" />
</head>

<body style="margin: 5px;">

<script type="text/javascript">
<!--
try
{
	document.body.innerHTML = window.opener.document.getElementById('<?=$_GET["id"]?>').value;
}
catch(e)
{
	// dummy
	document.write('Fejl opstået - kontroller at du ikke har lukket administrationen');
}
-->
</script>

</body>

</html>