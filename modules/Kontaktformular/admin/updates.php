<?
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

	// Mulighed for bekræftelses mail til afsender
	$updates["2006051100"]["text"] = "Mulighed for bekræftelses mail til afsender";
	$updates["2006051100"]["update"] = array(
		"FILE|admin/default.php",
		"FILE|pages/default.php",
		"EVAL|" . base64_encode('
			$db->execute("
				ALTER TABLE 
					" . $_table_prefix . "_module_" . $module . "_forms
				ADD
					email_field varchar(50) not null default \'\',
				ADD
		  			email_body text not null default \'\',
		  		ADD
		  			email_subject varchar(50) not null default \'\'
		  		");
			')
		);

		// Automatisk opdatering, genereret 19-05-2008 13:11:52
		$updates["2008051900"]["text"] = "Rettelse af fejl ved registrering af ip-adresse";
		$updates["2008051900"]["update"] = array(
					"FILE|pages/default.php",
		"");
		
		// Automatisk opdatering, genereret 07-11-2008 13:14:00
		$updates["2008110700"]["text"] = "Mulighed for at bruge feltetnavnet `required_free_subject` til emne i e-mail";
		$updates["2008110700"]["update"] = array(
					"FILE|pages/default.php",
		"");
		
		// Automatisk opdatering, genereret 25-12-2008 22:11:44
		$updates["2008122500"]["text"] = "Gemmer nu alle henvendelser i databasen";
		$updates["2008122500"]["update"] = array(
					"FILE|admin/data.php",
					"FILE|admin/install.php",
					"FILE|admin/menu.php",
					"FILE|admin/uninstall.php",
					"FILE|html/default.html",
					"FILE|pages/default.php",
			"EVAL|" . base64_encode('$db->execute("
CREATE TABLE `" . $_table_prefix . "_module_" . $module . "_data` (
`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`time` DATETIME NOT NULL ,
`title` VARCHAR( 50 ) NOT NULL ,
`data` TEXT NOT NULL ,
`email` VARCHAR( 50 ) NOT NULL ,
`ip` VARCHAR( 15 ) NOT NULL
)
");'),
		"");
		
		// Automatisk opdatering, genereret 09-06-2009 19:07:48
		$updates["2009060900"]["text"] = "Rettet så webserver tid bruges istedet for mysql tid ved henvendelser";
		$updates["2009060900"]["update"] = array(
					"FILE|pages/default.php",
		"");
		
		// Automatisk opdatering, genereret 26-10-2009 12:17:35
		$updates["2009102600"]["text"] = "Ændret så afsender sættes til brugers e-mail istedet for CMS";
		$updates["2009102600"]["update"] = array(
					"FILE|pages/default.php",
		"");
		
		// Automatisk opdatering, genereret 11-03-2010 16:05:46
		$updates["2010031100"]["text"] = "Tilføjet sprog fil";
		$updates["2010031100"]["update"] = array(
				"FOLDER|lang",
					"FILE|html/default_send.html",
					"FILE|lang/lang.php",
					"FILE|pages/default.php",
		"");
		
		// Automatisk opdatering, genereret 11-03-2010 16:13:46
		$updates["2010031101"]["text"] = "Rettelse af optional  / required felter";
		$updates["2010031101"]["update"] = array(
					"FILE|pages/default.php",
		"");
		
		// Automatisk opdatering, genereret 23-04-2010 10:33:52
		$updates["2010042300"]["text"] = "Mulighed for bekræft formular via f.eks. default_confirm.html";
		$updates["2010042300"]["update"] = array(
					"FILE|html/default.html",
					"FILE|html/default_confirm.html",
					"FILE|html/default_send.html",
					"FILE|pages/default.php",
		"");
		
		// Automatisk opdatering, genereret 28-02-2011 11:36:14
		$updates["2011022800"]["text"] = "Opdatering af sprogfiler";
		$updates["2011022800"]["update"] = array(
					"FILE|html/default.html",
					"FILE|html/default_confirm.html",
					"FILE|lang/en.php",
					"FILE|lang/lang.php",
		"");
		
		// Automatisk opdatering, genereret 04-03-2011 12:42:27
		$updates["2011030400"]["text"] = "Opdatering";
		$updates["2011030400"]["update"] = array(
					"FILE|pages/default.php",
		"");
		
		// Automatisk opdatering, genereret 22-11-2011 20:55:09
		$updates["2011112200"]["text"] = "Mulighed for at vedhæfte filer i kontaktformular";
		$updates["2011112200"]["update"] = array(
					"FILE|html/default.html",
					"FILE|html/default_confirm.html",
					"FILE|html/default_confirm_file.html",
					"FILE|html/default_file.html",
					"FILE|html/default_send.html",
					"FILE|html/default_send_file.html",
					"FILE|pages/default.php",
		"");
		
			// Automatisk opdatering, genereret 03-07-2013 12:56:47
			$updates["2013070300"]["text"] = "Tilføjelse af email skabelon";
			$updates["2013070300"]["update"] = array("FILE|admin/install.php",
						"FILE|html/email.html",
						"FILE|html/email_field.html",
						"FILE|pages/default.php",
			"");
			
			// Automatisk opdatering, genereret 22-07-2013 13:28:06
			$updates["2013072200"]["text"] = "";
			$updates["2013072200"]["update"] = array("FILE|admin/install.php",
						"FILE|pages/default.php",
			"");
			
			// Automatisk opdatering, genereret 23-08-2013 14:09:26
			$updates["2013082300"]["text"] = "Mulighed for at definere felter i email til kunde";
			$updates["2013082300"]["update"] = array("FILE|admin/install.php",
						"FILE|pages/default.php",
			"");
			?>