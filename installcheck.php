<?php

/**
 * BicBucStriim installation check
 *
 * Copyight 2012 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 
require 'vendor/autoload.php';

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array());

# Check for Apache server
function is_apache($srv) {
	if (preg_match('/apache/i',$srv))
		return true;
	else
		return false;
}

# see http://christian.roy.name/blog/detecting-modrewrite-using-php
function mod_rewrite_enabled() {
	if (function_exists('apache_get_modules')) {
	  $modules = apache_get_modules();
	  $mod_rewrite = in_array('mod_rewrite', $modules);
	} else {
	  $mod_rewrite =  getenv('HTTP_MOD_REWRITE')=='On' ? true : false ;
	}	
	return $mod_rewrite;
}

function has_sqlite() {
	$version = false;
	try {
		$mydb = new PDO('sqlite:data/data.db', NULL, NULL, array());
		return true;
	} catch (PDOException $e) {
		return false;
	}
}

function fw($file) {
	return (file_exists($file) && is_writeable($file));
}

function get_gd_version() { 
  ob_start(); 
  phpinfo(8); 
  $module_info = ob_get_contents(); 
  ob_end_clean(); 
  if (preg_match("/\bgd\s+version\b[^\d\n\r]+?([\d\.]+)/i", $module_info,$matches)) { 
      $gd_version_number = $matches[1]; 
  } else { 
      $gd_version_number = 0; 
  } 
  return $gd_version_number; 
} 


$srv = $_SERVER['SERVER_SOFTWARE'];
$is_a = is_apache($srv) ;
if ($is_a)
	$mre =  mod_rewrite_enabled();
else
	$mre = false;
$gdv = get_gd_version();
if ($gdv >= 2)
	$gde = true;
else 
	$gde = false;

$template = $twig->loadTemplate('installcheck.html');
echo $template->render(array(
	'page' => array(
		'rot' => '', #$app->request()->getRootUri(),
		'version' => '0.9.4'
	),
	'is_a' => $is_a,
	'srv' => $srv,
	'mre' => $mre,
	'hsql' => has_sqlite(),
	'hgd2' => $gde,
	'hgd2v' => $gdv,
	'dwrit' => fw('./data'),
	'mwrit' => fw('./data/data.db'),
	'opd' => ini_get('open_basedir'),
	'phpv' => phpversion(),	
	));

#echo phpinfo();
?>
