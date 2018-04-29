<?php
	/*COPYRIGHT*\
		COPYRIGHT STADEL.DK 2006
		
		AL KODE I DENNE FIL TILH�RER STADEL.DK, THOMAS@STADEL.DK.
		KODEN M� UNDER INGEN  OMST�NDIGHEDER  BENYTTES  TIL ANDET
		FORM�L END  DET DEN ER K�B TIL.  KODEN M� IKKE  �NDRES AF
		ANDRE   END   STADEL.DK.   KODEN  M�  IKKE  S�LGES  ELLER
		VIDEREDISTRIBUERES  HELT, DELVIS ELLER SOM EN KOPI AF DET
		SYSTEM   DET  OPRINDELIGT  ER  K�BT  SAMMEN  MED.  ENHVER
		OVERTR�DELSE  AF EN ELLER FLERE AF DE N�VNTE  BETINGELSER
		VIL RESULTERE I RETSFORF�LGELSE OG ERSTATNING FOR BRUD P�
		OPHAVSRETTEN AF KODEN, IFLG.  DANSK  OPHAVSRETSLOV. DENNE
		COPYRIGHT    MEDDELELSE    M�    DESUDEN    UNDER   INGEN
		OMST�NDIGHEDER FJERNES FRA DENNE FIL.
	
		ALL   CODE  IN  THIS  FILE  ARE  COPYRIGHTED   STADEL.DK,
		THOMAS@STADEL.DK.  IT'S NOT  ALLOWED TO USE THIS CODE FOR 
		ANY OTHER PURPOSE  THAN TOGEHTER  WITH THE ORGINAL SCRIPT 
		AS IT HAS BEEN  BOUGHT  AS A PART OF. IT'S NOT ALLOWED TO 
		SELL OR REDISTRIBUTE  THE CODE IN IT'S COMPLETE SENTENCE,
		ANY  PART OF THE  CODE OR AS A PART OF ANOTHER  SYSTEM OR 
		SCRIPT.  ANY  VIOLATION  OF  THESE  RULES  WILL RESULT IN 
		PROSECUTION   AND   COMPENSATION  FOR  VIOLATION  OF  THE 
		COPYRIGHT OF THIS SYSTEM,  SCRIPT AND CODE,  ACCORDING TO 
		DANISH  COPYRIGHT LAW. THIS  COPYRIGHT  MAY  NOT,  IN ANY 
		CIRCUMSTANCE, BE REMOVED FROM THIS FILE.
	\*COPYRIGHT*/

	// Overskrift
	$msg = new message;
	$msg->title(module2title($module) . " - {LANG|Indstillinger}");
	$html .= $msg->html();

	// Formular
	$frm = new form;
	$frm->tpl("th", "{LANG|Indstillinger}");
	$frm->checkbox(
		"{LANG|Lad ikke publicerede sider indg� i sitemap}",
		"nonpublic_sitemap",
		module_setting("nonpublic_sitemap") == 1
		);
	$frm->textarea(
		"{LANG|CSS klasser (en pr. linie)}",
		"classes",
		module_setting("classes")
		);
	
	if ($frm->done())
	{
		// Gemmer indstillinger
		module_setting("nonpublic_sitemap", $frm->values["nonpublic_sitemap"] != "" ? 1 : 0);
		$classes = "";
		$arr = explode("\n", $frm->values["classes"]);
		for ($i = 0; $i < count($arr); $i++)
		{
			$str = trim($arr[$i]);
			if ($str != "")
			{
				if ($classes != "") $classes .= "\r\n";
				$classes .= $str;
			}
		}
		module_setting("classes", $classes);
		header("Location: ./?module=$module&page=$page");
		exit;
	}
	
	// Viser formular
	$html .= $frm->html();
?>