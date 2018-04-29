<?php
	/*COPYRIGHT*\
		COPYRIGHT STADEL.DK 2006
		
		AL KODE I DENNE FIL TILHØRER STADEL.DK, THOMAS@STADEL.DK.
		KODEN MÅ UNDER INGEN  OMSTÆNDIGHEDER  BENYTTES  TIL ANDET
		FORMÅL END  DET DEN ER KØB TIL.  KODEN MÅ IKKE  ÆNDRES AF
		ANDRE   END   STADEL.DK.   KODEN  MÅ  IKKE  SÆLGES  ELLER
		VIDEREDISTRIBUERES  HELT, DELVIS ELLER SOM EN KOPI AF DET
		SYSTEM   DET  OPRINDELIGT  ER  KØBT  SAMMEN  MED.  ENHVER
		OVERTRÆDELSE  AF EN ELLER FLERE AF DE NÆVNTE  BETINGELSER
		VIL RESULTERE I RETSFORFØLGELSE OG ERSTATNING FOR BRUD PÅ
		OPHAVSRETTEN AF KODEN, IFLG.  DANSK  OPHAVSRETSLOV. DENNE
		COPYRIGHT    MEDDELELSE    MÅ    DESUDEN    UNDER   INGEN
		OMSTÆNDIGHEDER FJERNES FRA DENNE FIL.
	
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

	$updates = array();

	/*
	// Opdaterings-eksempel
	$updates["2005101400"]["text"] = "Demo-opdatering";
	$updates["2005101400"]["update"] = array(
		"FOLDER|side",
		"FILE|side/default.php"
		);
	*/
		
		// Automatisk opdatering, genereret 06-02-2007 10:55:00
		$updates["2007020600"]["text"] = "Rettelse af upload";
		$updates["2007020600"]["update"] = array(
					"FILE|admin/default.php",
		"");
		
		// Automatisk opdatering, genereret 27-04-2007 15:30:11
		$updates["2007042700"]["text"] = "Mulighed for flere sprog i admin";
		$updates["2007042700"]["update"] = array(
				"FOLDER|lang",
					"FILE|admin/default.php",
					"FILE|admin/menu.php",
					"FILE|lang/en.php",
					"FILE|lang/lang.php",
		"");
		
		// Automatisk opdatering, genereret 13-02-2008 10:24:33
		$updates["2008021300"]["text"] = "Opdatering af sprog filer";
		$updates["2008021300"]["update"] = array(
					"FILE|lang/lang.php",
		"");
		
		// Automatisk opdatering, genereret 01-10-2008 14:53:04
		$updates["2008100100"]["text"] = "Opdatering af sprog-filer samt engelsk";
		$updates["2008100100"]["update"] = array(
					"FILE|admin/default.php",
					"FILE|lang/en.php",
					"FILE|lang/lang.php",
		"");
		
		// Automatisk opdatering, genereret 19-11-2008 20:38:29
		$updates["2008111900"]["text"] = "Mulighed for at skrive indhold på flere sprog";
		$updates["2008111900"]["update"] = array(
					"FILE|admin/default.php",
					"FILE|pages/default.php",
			"EVAL|" . base64_encode('$db->execute("
	ALTER TABLE
		" . $_table_prefix . "_module_" . $module . "_pages
	CHANGE
		html html_da TEXT
	");'),
		"");
		
		// Automatisk opdatering, genereret 28-05-2009 10:06:41
		$updates["2009052800"]["text"] = "Tilføjet søgeord samt mulighed for at afkrydse publiceret";
		$updates["2009052800"]["update"] = array(
					"FILE|admin/default.php",
					"FILE|admin/install.php",
					"FILE|pages/default.php",
					"FILE|pages/sitemap.php",
			"EVAL|" . base64_encode('$db->execute("
	ALTER TABLE
		" . $_table_prefix . "_module_" . $module . "_pages
	ADD
		`public` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
	");					'),
		"");
		
		// Automatisk opdatering, genereret 03-06-2009 12:37:41
		$updates["2009060300"]["text"] = "Rettelse af fejl i visning i Internet Explorer";
		$updates["2009060300"]["update"] = array(
					"FILE|admin/default.php",
		"");
		
		// Automatisk opdatering, genereret 04-06-2009 21:35:26
		$updates["2009060400"]["text"] = "Ændring så søgeord indtastes via link under `Rediger side`";
		$updates["2009060400"]["update"] = array(
					"FILE|admin/default.php",
					"FILE|lang/lang.php",
		"");
		
		// Automatisk opdatering, genereret 29-06-2009 11:20:23
		$updates["2009062900"]["text"] = "Rettelse af fejl i sitemap";
		$updates["2009062900"]["update"] = array(
					"FILE|pages/sitemap.php",
		"");
		
		// Automatisk opdatering, genereret 07-07-2009 10:36:46
		$updates["2009070700"]["text"] = "mulighed for at definere om ikke publicerede sider skal vises i sitemap";
		$updates["2009070700"]["update"] = array(
					"FILE|admin/menu.php",
					"FILE|admin/settings.php",
					"FILE|pages/sitemap.php",
		"");
		
		// Automatisk opdatering, genereret 08-07-2009 20:39:46
		$updates["2009070800"]["text"] = "Tilføjelse så indhold kan vises i sitemap søgning";
		$updates["2009070800"]["update"] = array(
					"FILE|pages/sitemap.php",
		"");
		
		// Automatisk opdatering, genereret 21-10-2009 22:11:35
		$updates["2009102100"]["text"] = "Mulighed for gruppering af sider";
		$updates["2009102100"]["update"] = array(
					"FILE|admin/default.php",
					"FILE|admin/install.php",
			"EVAL|" . base64_encode('	$db->execute("
		ALTER TABLE " . $_table_prefix . "_module_" . $module . "_pages
		ADD `group` varchar(25) not null default \'\'
		");
'),
		"");
		
		// Automatisk opdatering, genereret 06-11-2009 11:48:24
		$updates["2009110600"]["text"] = "Mulighed for at kopiere eksisterende side (skabelon)";
		$updates["2009110600"]["update"] = array(
					"FILE|admin/default.php",
					"FILE|lang/lang.php",
		"");
		
		// Automatisk opdatering, genereret 06-11-2009 12:02:07
		$updates["2009110601"]["text"] = "Rettelse af tekst i admin";
		$updates["2009110601"]["update"] = array(
					"FILE|admin/default.php",
					"FILE|lang/lang.php",
		"");
		
		// Automatisk opdatering, genereret 28-02-2011 11:38:06
		$updates["2011022800"]["text"] = "Opdatering af sprogfiler";
		$updates["2011022800"]["update"] = array(
					"FILE|lang/en.php",
					"FILE|lang/lang.php",
		"");
		
		// Automatisk opdatering, genereret 20-01-2012 00:03:13
		$updates["2012012000"]["text"] = "Mulighed for angivelse af CSS klasser under indstillinger og i forbindelse med WYSIWYG-side";
		$updates["2012012000"]["update"] = array(
				"FOLDER|html",
					"FILE|admin/default.php",
					"FILE|admin/install.php",
					"FILE|admin/settings.php",
					"FILE|html/default.html",
					"FILE|pages/default.php",
			"EVAL|" . base64_encode('$db->execute("
	ALTER TABLE
		" . $_table_prefix . "_module_" . $module . "_pages
	ADD
		`class` VARCHAR(25)
	");'),
		"");
		
		// Automatisk opdatering, genereret 25-01-2012 10:59:14
		$updates["2012012500"]["text"] = "Opdatering";
		$updates["2012012500"]["update"] = array(
					"FILE|pages/default.php",
		"");
		
		// Automatisk opdatering, genereret 25-01-2012 11:00:16
		$updates["2012012501"]["text"] = "Opdatering";
		$updates["2012012501"]["update"] = array(
					"FILE|pages/default.php",
		"");
		
		// Automatisk opdatering, genereret 31-01-2012 13:19:03
		$updates["2012013100"]["text"] = "Opdatering";
		$updates["2012013100"]["update"] = array(
					"FILE|admin/install.php",
		"");
		
			// Automatisk opdatering, genereret 31-08-2012 15:18:09
			$updates["2012083100"]["text"] = "";
			$updates["2012083100"]["update"] = array(
						"FILE|admin/default.php",
			"");
			
			// Automatisk opdatering, genereret 19-08-2013 12:51:20
			$updates["2013081900"]["text"] = "";
			$updates["2013081900"]["update"] = array("FILE|admin/install.php",
						"FILE|pages/default.php",
			"");
			?>