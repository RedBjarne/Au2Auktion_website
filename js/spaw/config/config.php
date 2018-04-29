<?php
if (preg_match("/\/Admin\//", $_SERVER["HTTP_REFERER"]) or !isset($_SERVER["HTTP_REFERER"]))
{
	session_set_cookie_params(0, "/Admin/");
}
session_start();


global $_document_root;
$_document_root = str_replace("\\", "/", substr(__FILE__, 0, strlen(__FILE__) - strlen("/js/spaw/config/config.php")));
require_once(str_replace('\\\\','/',dirname(__FILE__)).'/../class/config.class.php');
require_once(str_replace('\\\\','/',dirname(__FILE__)).'/../class/util.class.php');

// sets physical filesystem directory of web site root
// if calculation fails (usually if web server is not apache) set this manually
//SpawConfig::setStaticConfigItem('DOCUMENT_ROOT', str_replace("\\","/",SpawVars::getServerVar("DOCUMENT_ROOT")));
SpawConfig::setStaticConfigItem('DOCUMENT_ROOT', $_document_root);
if (!ereg('/$', SpawConfig::getStaticConfigValue('DOCUMENT_ROOT')))
  SpawConfig::setStaticConfigItem('DOCUMENT_ROOT', SpawConfig::getStaticConfigValue('DOCUMENT_ROOT').'/');
// sets physical filesystem directory where spaw files reside
// should work fine most of the time but if it fails set SPAW_ROOT manually by providing correct path
SpawConfig::setStaticConfigItem('SPAW_ROOT', str_replace("\\","/",realpath(dirname(__FILE__)."/..").'/'));
// sets virtual path to the spaw directory on the server
// if calculation fails set this manually
SpawConfig::setStaticConfigItem('SPAW_DIR', '/'.str_replace(SpawConfig::getStaticConfigValue("DOCUMENT_ROOT"),'',SpawConfig::getStaticConfigValue("SPAW_ROOT")));

/*
// semi-automatic path calculation
// comment the above settings of DOCUMENT_ROOT, SPAW_ROOT and SPAW_DIR
// and use this block if the above fails.
// set SPAW_DIR manually. If you access demo page by http://domain.com/spaw2/demo/demo.php
// then set SPAW_DIR to /spaw2/
SpawConfig::setStaticConfigItem('SPAW_DIR', '/spaw2/');
// and the following settings will be calculated automaticly
SpawConfig::setStaticConfigItem('SPAW_ROOT', str_replace("\\","/",realpath(dirname(__FILE__)."/..").'/'));
SpawConfig::setStaticConfigItem('DOCUMENT_ROOT', substr(SpawConfig::getStaticConfigValue('SPAW_ROOT'),0,strlen(SpawConfig::getStaticConfigValue('SPAW_ROOT'))-strlen(SpawConfig::getStaticConfigValue('SPAW_DIR'))));
*/

/*
// under IIS you will probably need to setup the above paths manually. it would be something like this
SpawConfig::setStaticConfigItem('DOCUMENT_ROOT', 'c:/inetpub/wwwroot/');
SpawConfig::setStaticConfigItem('SPAW_ROOT', 'c:/inetpub/wwwroot/spaw2/');
SpawConfig::setStaticConfigItem('SPAW_DIR', '/spaw2/');
*/

// DEFAULTS used when no value is set from code
// language 
SpawConfig::setStaticConfigItem('default_lang','en');
// output charset (empty strings means charset specified in language file)
SpawConfig::setStaticConfigItem('default_output_charset','');
// theme 
SpawConfig::setStaticConfigItem('default_theme','spaw2lite');
// toolbarset 
SpawConfig::setStaticConfigItem('default_toolbarset','all');
// stylesheet
SpawConfig::setStaticConfigItem('default_stylesheet',SpawConfig::getStaticConfigValue('SPAW_DIR').'wysiwyg.css');
// width 
SpawConfig::setStaticConfigItem('default_width','100%');
// height 
SpawConfig::setStaticConfigItem('default_height','200px');

// specifies if language subsystem should use iconv functions to convert strings to the specified charset
SpawConfig::setStaticConfigItem('USE_ICONV',true);
// specifies rendering mode to use: "xhtml" - renders using spaw's engine, "builtin" - renders using browsers engine
SpawConfig::setStaticConfigItem('rendering_mode', 'builtin', SPAW_CFG_TRANSFER_JS);
// specifies that xhtml rendering engine should indent it's output
SpawConfig::setStaticConfigItem('beautify_xhtml_output', true, SPAW_CFG_TRANSFER_JS);
// specifies host and protocol part (like http://mydomain.com) that should be added to urls returned from file manager (and probably other places in the future) 
SpawConfig::setStaticConfigItem('base_href', '', SPAW_CFG_TRANSFER_JS);
// specifies if spaw should strip domain part from absolute urls (IE makes all links absolute)
SpawConfig::setStaticConfigItem('strip_absolute_urls', true, SPAW_CFG_TRANSFER_JS);
// specifies in which directions resizing is allowed (values: none, horizontal, vertical, both)
SpawConfig::setStaticConfigItem('resizing_directions', 'both', SPAW_CFG_TRANSFER_JS);
// specifies that special characters should be converted to the respective html entities
SpawConfig::setStaticConfigItem('convert_html_entities', true, SPAW_CFG_TRANSFER_JS);

// Indlæser styles fra default.css
$array_class = array("" => "Normal");
if (isset($_SESSION["sess_spaw_css"]) and is_file($_document_root . $_SESSION["sess_spaw_css"]))
{
	$tmpcss = "\n" . str_replace("\r", "", file_get_contents($_document_root . $_SESSION["sess_spaw_css"]));
	while (preg_match("/\n\.([a-zA-Z0-9_\-]+)[\t\s]+\/\*([^\*]+)\*\//", $tmpcss, $tmparray))
	{
		$tmpcss = str_replace($tmparray[0], "", $tmpcss);
		$array_class[$tmparray[1]] = $tmparray[2];
	}
}

// data for style (css class) dropdown list
SpawConfig::setStaticConfigItem("dropdown_data_core_style", $array_class);

// data for style (css class) dropdown in table properties dialog
SpawConfig::setStaticConfigItem("table_styles",
  array(
    '' => 'Normal'
  )
);
// data for style (css class) dropdown in table cell properties dialog
SpawConfig::setStaticConfigItem("table_cell_styles",
  array(
    '' => 'Normal'
  )
);
// data for fonts dropdown list
SpawConfig::setStaticConfigItem("dropdown_data_core_fontname",
  array(
    'Arial' => 'Arial',
    'Courier' => 'Courier',
    'Tahoma' => 'Tahoma',
    'Times New Roman' => 'Times',
    'Verdana' => 'Verdana'
  )
);
// data for fontsize dropdown list
SpawConfig::setStaticConfigItem("dropdown_data_core_fontsize",
  array(
    '1' => '1',
    '2' => '2',
    '3' => '3',
    '4' => '4',
    '5' => '5',
    '6' => '6'
  )
);
// data for paragraph dropdown list
SpawConfig::setStaticConfigItem("dropdown_data_core_formatBlock",
  array(
    'Normal' => 'Normal',
    '<H1>' => 'Overskrift 1',
    '<H2>' => 'Overskrift 2',
    '<H3>' => 'Overskrift 3',
    '<H4>' => 'Overskrift 4',
    '<H5>' => 'Overskrift 5',
    '<H6>' => 'Overskrift 6',
    '<pre>' => 'Fast bredde',
    '<address>' => 'Addresse',
    '<p>' => 'Afsnit'    
  )
);
// data for link targets drodown list in hyperlink dialog
SpawConfig::setStaticConfigItem("a_targets",
  array(
    '_self' => 'Self',
    '_blank' => 'Blank',
    '_top' => 'Top',
    '_parent' => 'Parent'
  )
);


// toolbar sets (should start with "toolbarset_"
// standard core toolbars
SpawConfig::setStaticConfigItem('toolbarset_standard',
  array(
    "format" => "format",
    "style" => "style",
    "edit" => "edit",
    "table" => "table",
    "plugins" => "plugins",
    "insert" => "insert",
    "tools" => "tools",
    "font" => "font"
  ) 
);
// all core toolbars
SpawConfig::setStaticConfigItem('toolbarset_all',
  array(
    "format" => "format",
    "style" => "style",
    "edit" => "edit",
    "table" => "table",
    "plugins" => "plugins",
    "insert" => "insert",
    "tools" => "tools",
    "font" => "font",    
    "stadel" => "stadel"
  ) 
);
// mini core toolbars
SpawConfig::setStaticConfigItem('toolbarset_mini',
  array(
    "format" => "format_mini",
    "edit" => "edit",
    "tools" => "tools"
  ) 
);

// colorpicker config
SpawConfig::setStaticConfigItem('colorpicker_predefined_colors',
  array(
    'black',
    'silver',
    'gray',
    'white',
    'maroon',
    'red',
    'purple',
    'fuchsia',
    'green',
    'lime',
    'olive',
    'yellow',
    'navy',
    'blue',
    '#fedcba',
    'aqua'
  ),
  SPAW_CFG_TRANSFER_SECURE
);

// data for paragraph dropdown list
SpawConfig::setStaticConfigItem("dropdown_data_core_formatBlock",
  array(
    'Normal' => 'Normal',
    '<H1>' => 'Overskrift 1',
    '<H2>' => 'Overskrift 2',
    '<H3>' => 'Overskrift 3',
    '<H4>' => 'Overskrift 4',
    '<H5>' => 'Overskrift 5',
    '<H6>' => 'Overskrift 6',
    '<pre>' => 'Fast bredde',
    '<address>' => 'Addresse',
    '<p>' => 'Afsnit'    
  )
);

// SpawFm plugin config:

// Session check
error_reporting(E_ERROR);
if (!session_id()) session_start();
if (!isset($_SESSION)) $_SESSION = false;

// global filemanager settings
SpawConfig::setStaticConfigItem(
  'PG_SPAWFM_SETTINGS',
  array(
    'allow_upload'        => $_SESSION["sess_spaw_upload_allowed"],         // allow uploading new files in directory
    'allow_modify'        => $_SESSION["sess_spaw_upload_allowed"],         // allow edit filenames/delete files in directory
    'max_upload_filesize' => 0,             // max upload file size allowed in bytes, or 0 to ignore
    'max_img_width'       => 0,             // max uploaded image width allowed, or 0 to ignore
    'max_img_height'      => 0,             // max uploaded image height allowed, or 0 to ignore
    'chmod_to'            => false,         // change the mode of an uploaded file, of false to leave default
    'allowed_filetypes'   => array('none'),  // allowed filetypes groups/extensions
    //'view_mode'           => 'list',      // directory view mode: list/details/thumbnails - TO DO
    //'thumbnails_enabled'  => true,        // enable thumbnails view mode - TO DO
    //'allow_create_subdir' => true,        // allow creating subdirectories - TO DO
    //'recursive'           => true,        // allow entering subdirectories - TO DO
  ),
  SPAW_CFG_TRANSFER_SECURE
);

// Function, der tilføjer undermapper til array
function spaw_add_dir(&$array, $dir)
{
	global $_document_root;
	$dir2 = substr($dir, strlen($_document_root));
	$array[count($array)] = array(
	      'dir'     => $dir2,
	      'caption' => ($dir2 == "/upl/" ? "$dir2 (Fælles mappe)" : $dir2),
	      'params'  => array(
	      	'default_dir' => (count($array) == 0),
	        'allowed_filetypes' => array('images','flash','documents','audio','video','archives')
	      )
	    );

	// Under-mapper
	if (is_readable($dir))
	{
		$mapper = array();
		$mappe = dir($dir);
		while ($fil = $mappe->read())
		{
			if (is_dir($dir . $fil) and $fil <> "." and $fil <> "..") $mapper[] = $fil;
		}
		sort($mapper);
		for ($i = 0; $i < count($mapper); $i++) spaw_add_dir($array, $dir . $mapper[$i] . "/");
	}
}

$array = array();

// Fælles mappe
if ($_SESSION["sess_spaw_upload_shared_allowed"]) spaw_add_dir($array, $_document_root . "/upl/");

// Privat mappe
if ($_SESSION["sess_spaw_upload_allowed"] and is_dir(SpawConfig::getStaticConfigValue('DOCUMENT_ROOT') . "/" . preg_replace("/^\//", "", $_SESSION["sess_spaw_imglibs"])))
	spaw_add_dir($array, $_document_root . "/" . preg_replace("/^\//", "", $_SESSION["sess_spaw_imglibs"]));

/*
{
	$array[count($array)] = array(
	      'dir'     => "/upl/",
	      'caption' => 'Fælles mappe (/upl/)',
	      'params'  => array(
	      	'default_dir' => true,
	        'allowed_filetypes' => array('images','flash','documents','audio','video','archives')
	      )
	    );
}
if ($_SESSION["sess_spaw_upload_allowed"])
{
	if (is_dir(SpawConfig::getStaticConfigValue('DOCUMENT_ROOT') . ereg_replace("^/", "", $_SESSION["sess_spaw_imglibs"])))
	{
		$array[count($array)] = array(
		      'dir'     => $_SESSION["sess_spaw_imglibs"],
		      'caption' => 'Modul mappe (' . ereg_replace("^([^/]{1})", "/\\1", $_SESSION["sess_spaw_imglibs"]) . ')', 
		      'params'  => array(
		      	'default_dir' => false,
		        'allowed_filetypes' => array('images','flash','documents','audio','video','archives')
		      )
		    );
	}  
}
*/

SpawConfig::setStaticConfigItem(
  'PG_SPAWFM_DIRECTORIES',
  $array,
  SPAW_CFG_TRANSFER_SECURE
);
?>