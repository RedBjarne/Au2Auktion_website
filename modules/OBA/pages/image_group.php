<?php
	// Inkluderer n�dvendige filer
	include("../../../inc/config.php");
	include("../../../class/image.php");
	
	// Henter variabler
	$id = intval($_GET["id"]);
	$width = intval($_GET["width"]);
	$height = intval($_GET["height"]);
	
	// Tjekker width og height
	if ($width > 3000) $width = 3000;
	if ($height > 3000) $height = 3000;
	
	// �bner billede
	$img = @imagecreatefromjpeg("../upl/group_" . $id . ".jpg");
	if (!$img) $img = @imagecreatefromjpeg("../img/no_image.jpg");
	if (!$img) $img = imagecreate(3000, 3000);
	
	// Sender headers
	header("Content-type: image/png");
	header("Expires: " . date("D, j M Y H:i:s", time() + (86400 * 30)) . " CEST");
	header("Cache-Control: Public");
	header("Pragma: Public");
	
	// Viser billede i �nsket st�rrelse
	$image = new image;
	if ($width > 0 and $height > 0)
	{
		// S� skal billedet have en bestemt st�rrelse
		$img = $image->imagesize($img, $width, $height);
	}
	elseif ($width > 0)
	{
		// S� er det kun bredden der er angivet
		$img = $image->imagemaxsize($img, $width, 3000);
	}
	elseif ($height > 0)
	{
		// S� er det kun h�jden der er angivet
		$img = $image->imagemaxsize($img, 3000, $height);
	}
	else
	{
		// S� er det ikke angivet hverken h�jde eller bredde
	}
	
	imagesavealpha($img, true);
	imagepng($img);