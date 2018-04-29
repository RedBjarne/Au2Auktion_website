<?php
	require_once($_document_root . "/modules/$module/inc/qrcode.php");
	
	function qrstadelimage($code, $pixelsize = 5)
	{
		$qrcode = new QRcode($code, "H");
		$arr = $qrcode->getBarcodeArray();
		$bcode = $arr["bcode"];
		
		$img_w = count($bcode[0]) * $pixelsize + 4 * $pixelsize;
		$img_h = count($bcode) * $pixelsize + 4 * $pixelsize;
		
		$offset_x = 2 * $pixelsize;
		$offset_y = 2 * $pixelsize;
		
		$img = imagecreatetruecolor($img_w, $img_h);
		$white = imagecolorallocate($img, 255, 255, 255);
		$black = imagecolorallocate($img, 0, 0, 0);
		imagefilledrectangle($img, 0, 0, $img_w - 1, $img_h - 1, $white);
		
		for ($r = 0; $r < count($bcode); $r++)
		{
			for ($c = 0; $c < count($bcode[$r]); $c++)
			{
				if ($bcode[$r][$c] == 1)
				{
					$x = $offset_x + $c * $pixelsize;
					$y = $offset_y + $r * $pixelsize;
					imagefilledrectangle($img, $x, $y, $x + $pixelsize - 1, $y + $pixelsize - 1, $black);
				}
			}
		}
		
		return $img;
	}
?>