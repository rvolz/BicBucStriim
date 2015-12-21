<?php

/**
 * BicBucStriim installation check
 *
 * Copyight 2012 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 

# Use this instead of the Composer autoload for PHP 5.2 compatibility
# At least the PHP version check should run with PHP 5.2
require_once 'vendor/twig/twig/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

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
		# Recent Apache versions (Synology DSM5) prefix envvars with the module name
		$mod_rewrite =  (getenv('HTTP_MOD_REWRITE')=='On' || getenv('REDIRECT_HTTP_MOD_REWRITE')=='On') ? true : false ;
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

function check_calibre($dir) {
	clearstatcache();
	$ret = array('status' => 2, 'dir_exists' => false, 'dir_is_readable' => false, 'dir_is_executable' => false, 'realpath' => '', 'library_ok' => false);
	if (file_exists($dir)) {
		$ret['dir_exists'] = true;
		if (is_readable($dir)) {
	 		$ret['dir_is_readable'] = true;
	 		$ret['dir_is_executable'] = is_executable($dir);
			$mdb = realpath($dir).'/metadata.db';
			$ret['realpath'] = $mdb;
			if (file_exists($mdb)) {
				$ret['status'] = 1;	
				try {
					$mydb = new PDO('sqlite:'.$mdb, NULL, NULL, array());
					$ret['library_ok'] = true;
				} catch (PDOException $e) {
					;
				}			
			}
		}
	} 
	return $ret;
}

function check_php() {
	$pv = preg_split('/\./', phpversion());	
	$maj = intval($pv[0]);
	$min = intval($pv[1]);
	if ($maj == 5 && $min >= 3) 
		return true;
	elseif ($maj > 5) 
		return true;
	else
		return false;
}


if (isset($_POST['calibre_dir'])) {
	$calibre_dir = $_POST['calibre_dir'];
	$cd = check_calibre($calibre_dir);
} else {
	$calibre_dir = null;
	$cd = null;
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
		'rot' => '',
		'version' => '1.3.6'
	),
	'is_a' => $is_a,
	'srv' => $srv,
	'mre' => $mre,
	'calibre_dir'=> $calibre_dir,
	'cd' => $cd,
	'htaccess' => file_exists('./.htaccess'),
	'hsql' => has_sqlite(),
	'hgd2' => $gde,
	'hgd2v' => $gdv,
	'dwrit' => fw('./data'),
	'intl' => extension_loaded('intl'),
	'mcrypt' => extension_loaded('mcrypt'),
	'mwrit' => fw('./data/data.db'),
	'opd' => ini_get('open_basedir'),
	'php' => check_php(),
	'phpv' => phpversion(),
	));

#echo phpinfo();
?>
