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

	/*
		Version:		06-04-2006
		Beskrivelse:	Konfigurationsfil for CMS
	*/
	
	// CMS info
	$_cms_menu = "http://cms.stadel.dk/menu/?";
	$_cms_update = "http://cms.stadel.dk/update/?";
	$_cms_domain = ereg_replace("^www\.", "", $_SERVER["HTTP_HOST"]);
	$_cms_check = "20130919205848D1GD1NRyyB0ik7Xp3Yk6x1s1U";
	$_cms_ftp_server = "";
	$_cms_ftp_username = "";
	$_cms_ftp_password = "";
	$_cms_ftp_root = "html";
	
	// Generelt
	$_document_root = ereg_replace("/inc/config\.php$", "", __FILE__);
	$_site_url = "http://" . $_SERVER["HTTP_HOST"];
	$_class_dir = $_document_root . "/class/";
	$_table_prefix = "oba";
	
	// Temp
	$_tmp_dir = $_document_root . "/tmp/";
	$_tmp_url = $_site_url . "/tmp/";
	$_tmp_expire = time() - 900;
	
	// Upload
	$_upl_dir = $_document_root . "/upl/";
	$_upl_url = $_site_url . "/upl/";
	
	// Klasse: db
	$_db_server = "localhost";
	$_db_database = "kontorkl_au2auktion-admin";
	$_db_username = "kontorkl_au2ad";
	$_db_password = "00wbpKqcSDT3";
	$_db_buffer_active = false;
	
	// Klasse: tpl
	$_tpl_use_cache = true;
	$_tpl_cache_ttl = 900;
	$_tpl_dir = $_document_root . "/html/";
	$_tpl_ext = "html";
	
	// Tjekker om det er cluster servere
	if (isset($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"])) $_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_X_CLUSTER_CLIENT_IP"];
	
	// Sætter timezone til Europe/Copenhagen
	date_default_timezone_set("Europe/Copenhagen");
	setlocale(LC_ALL, "da_DK");
	
	// Sætter tegnsæt til ISO-8859-1
	header("Content-type: text/html; charset=ISO-8859-1");

	if (isset($_SERVER["REDIRECT_STATUS"]) and !isset($_SERVER["REDIRECT_REQUEST_METHOD"])) $_SERVER["REDIRECT_REQUEST_METHOD"] = "GET";