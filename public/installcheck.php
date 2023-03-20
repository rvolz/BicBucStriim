<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\Infrastructure\InstUtils;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Infrastructure/InstUtils.php';
require __DIR__ . '/../src/Application/Version.php';

$loader = new FilesystemLoader(__DIR__ . '/../app/templates');
$twig = new Environment($loader, [ 'cache' => __DIR__ . '/../var/cache']);

function check_php_version(int $version): bool
{
    return (PHP_VERSION_ID >= $version);
}

/**
 * Check if we are running in an Apache environment
 * @param string $srv
 * @return bool
 */
function is_apache(string $srv): bool
{
    return preg_match('/apache/i', $srv);
}

/**
 * Check if we are running in an Ngnix environment
 * @param string $srv
 * @return bool
 */
function is_ngnix(string $srv): bool
{
    return preg_match('/nginx/i', $srv);
}


/**
 * see http://christian.roy.name/blog/detecting-modrewrite-using-php
 * @return bool
 */
function mod_rewrite_enabled(): bool
{
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        return in_array('mod_rewrite', $modules);
    } else {
        # Recent Apache versions (Synology DSM5) prefix env vars with the module name
        return getenv('HTTP_MOD_REWRITE') == 'On' || getenv('REDIRECT_HTTP_MOD_REWRITE') == 'On';
    }
}

function queryValue(string $query): string
{
    $mydb = new PDO('sqlite::memory:', null, null, []);
    $stmt = $mydb->query($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $stmt->closeCursor();
    return (string) $result[0];
}

function has_sqlite_version(string $version): bool
{
    try {
        $sql_version = queryValue('select sqlite_version();');
        return (str_starts_with($sql_version, $version));
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if the FTS5 extension is part of SQLite
 * @return bool
 */
function has_sqlite_fts5(): bool
{
    try {
        $status = queryValue("select sqlite_compileoption_used('SQLITE_ENABLE_FTS5');");
        return $status == '1';
    } catch (PDOException $e) {
        return false;
    }
}

function fw($file): bool
{
    return (file_exists($file) && is_writeable($file));
}

function get_gd_version(): string
{
    ob_start();
    phpinfo(8);
    $module_info = ob_get_contents();
    ob_end_clean();
    return InstUtils::find_gd_version($module_info);
}

function check_modules(array $modules): array
{
    $loaded = array_map('extension_loaded', $modules);
    return array_combine($modules, $loaded);
}

function check_calibre($dir): array
{
    clearstatcache();
    $ret = ['status' => 2, 'dir_exists' => false, 'dir_is_readable' => false, 'dir_is_executable' => false, 'realpath' => '', 'library_ok' => false];
    if (file_exists($dir)) {
        $ret['dir_exists'] = true;
        if (is_readable($dir)) {
            $ret['dir_is_readable'] = true;
            $ret['dir_is_executable'] = is_executable($dir);
            $mdb = realpath($dir) . '/metadata.db';
            $ret['realpath'] = $mdb;
            if (file_exists($mdb)) {
                $ret['status'] = 1;
                try {
                    $mydb = new PDO('sqlite:' . $mdb, null, null, []);
                    $ret['library_ok'] = true;
                } catch (PDOException $e) {
                    ;
                }
            }
        }
    }
    return $ret;
}


if (isset($_POST['calibre_dir'])) {
    $calibre_dir = $_POST['calibre_dir'];
    $cd = check_calibre($calibre_dir);
} else {
    $calibre_dir = null;
    $cd = null;
}

$srv = $_SERVER['SERVER_SOFTWARE'];
$is_a = is_apache($srv);
if ($is_a) {
    $mre = mod_rewrite_enabled();
} else {
    $mre = false;
}
$is_n = is_ngnix($srv);

$gdv = get_gd_version();
$gde = ($gdv >= 2);

// TODO check, probably too many
// php7-sodium necessary?
$composerData = json_decode(file_get_contents(__DIR__ . '/../composer.json'));
$php_modules = [];
foreach ($composerData->require as $dependency => $version) {
    if (substr($dependency, 0, 4) === 'ext-') {
        $php_modules[] = substr($dependency, 4);
    }
}


$template = $twig->load('installcheck.twig');
$template->display(
    [
        'page' => [
            'rot' => '',
            'version' => APP_VERSION,
        ],
        'sqlite' => [
            'hsql' => has_sqlite_version('3.'),
            'hfts5' => has_sqlite_fts5(),
        ],
        'php' => [
            'php' => check_php_version(70400),
            'phpv' => phpversion(),
        ],
        'is_a' => $is_a,
        'srv' => $srv,
        'mre' => $mre,
        'calibre_dir' => $calibre_dir,
        'cd' => $cd,
        'htaccess' => file_exists('./.htaccess'),
        'hgd2' => $gde,
        'hgd2v' => $gdv,
        'dwrit' => fw(__DIR__ . '/../data'),
        'modules' => check_modules($php_modules),
        'mwrit' => fw(__DIR__ . '/../data/data.db'),
        'opd' => ini_get('open_basedir'),
    ]
);

// echo phpinfo();
