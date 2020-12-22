<?php

use App\Domain\BicBucStriim\BicBucStriim;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\Calibre\Calibre;
use App\Domain\Calibre\CalibreFilter;
use App\Domain\Opds\OpdsGenerator;
use App\Domain\User\User;
use \Psr\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Calculate an ETag for a file resource
 * @param string $fname path to file resource
 * @return string MD5 Hash
 */
function calcEtag(string $fname): string
{
    $mtime = filemtime($fname);
    return md5("{$fname}-{$mtime}");
}

/**
 * Check for all books if thumbnails are available, if not try to create them
 * @param array $books book records
 * @param BicBucStriim $bbs
 * @param Calibre $calibre
 * @param bool $clipped
 * @return array
 */
function checkThumbnail(array $books, BicBucStriim $bbs, Calibre $calibre, bool $clipped): array
{
    $checkThumbnail = function ($book) use ($bbs, $calibre, $clipped) {
        $book->thumbnail = checkAndCreateThumbnail($book->id, $bbs, $calibre, $clipped);
        return $book;
    };
    return array_map($checkThumbnail, $books);
}

/**
 * Check for all books if thumbnails are available, if not try to create them
 * @param array $books book records
 * @param BicBucStriim $bbs
 * @param Calibre $calibre
 * @param bool $clipped
 * @return array
 */
function checkThumbnailOpds(array $books, BicBucStriim $bbs, Calibre $calibre, bool $clipped): array
{
    $checkThumbnailOpds = function ($record) use ($bbs, $calibre, $clipped) {
        $record['book']->thumbnail = checkAndCreateThumbnail($record['book']->id, $bbs, $calibre, $clipped);
        return $record;
    };
    return array_map($checkThumbnailOpds, $books);
}

/**
 * Check if a title thumbnail exists, if not try to create it.
 * @param int $id book id
 * @param BicBucStriim $bbs
 * @param Calibre $calibre
 * @param bool $clipped should the thumbnail be clipped?
 * @return bool true if thumbnail exists, else false
 */
function checkAndCreateThumbnail(int $id, BicBucStriim $bbs, Calibre $calibre, bool $clipped): bool
{
    $thum_exists = $bbs->isTitleThumbnailAvailable($id);
    if ($thum_exists) {
        return true;
    } else {
        $cover_path = $calibre->titleCover($id);
        if (is_null($cover_path)) {
            return false;
        } else {
            $thumb_path = $bbs->titleThumbnail($id, $cover_path, $clipped);
            return is_null($thumb_path);
        }
    }
}

/**
 * Retrieve the current configuration
 *
 * @param ContainerInterface $c
 * @return mixed    Array of key-value pairs
 */
function getConfig(ContainerInterface $c)
{
    $sconfig = $c['bbs']->configs();
    $config = $c['config'];
    foreach ($sconfig as $sc) {
        $config[$sc->name] = $sc->val;
    }
    return $config;
}


/**
 * Return a tag/language filter for Calibre according to user data, an empty filter if there is no user
 * @param ContainerInterface $container
 * @return CalibreFilter
 */
function getFilter(ContainerInterface $container): CalibreFilter
{
    $lang = null;
    $tag = null;
    $user = $container['user'];
    if (isset($user)) {
        $container[LoggerInterface::class]->debug('getFilter: ' . var_export($user, true));
        if (!empty($user['languages']))
            $lang = $container['calibre']->getLanguageId($user->languages);
        if (!empty($user['tags']))
            $tag = $container['calibre']->getTagId($user->tags);
        $container[LoggerInterface::class]->debug('getFilter: Using language ' . $lang . ', tag ' . $tag);
    }
    return new CalibreFilter($lang, $tag);
}

/**
 * Calculate the next page number for search results
 * @param array $tl search result
 * @return int       page index or NULL
 */
function getNextSearchPage(array $tl): ?int
{
    if ($tl['page'] < $tl['pages'] - 1)
        $nextPage = $tl['page'] + 1;
    else
        $nextPage = null; // TODO null or 0? -> return type!
    return $nextPage;
}

/**
 * Caluclate the last page numberfor search results
 * @param array $tl search result
 * @return int            page index
 */
function getLastSearchPage(array $tl): int
{
    if ($tl['pages'] == 0)
        $lastPage = 0;
    else
        $lastPage = $tl['pages'] - 1;
    return $lastPage;
}

/**
 * Get a query parameter's value, or a defalut value if not set
 * @param array $params query parameters
 * @param string $name parameter name
 * @param mixed $default default value
 * @return mixed
 *
 * @deprecated because unused?
 */
function getQueryParam(array $params, string $name, $default)
{
    if (array_key_exists($name, $params))
        return $params[$name];
    else
        return $default;
}

/**
 * Initialize the OPDS generator
 * @param ContainerInterface $container
 * @param ServerRequestInterface $request current request
 * @return OpdsGenerator
 */
function mkOpdsGenerator(ContainerInterface $container, ServerRequestInterface $request): OpdsGenerator
{
    $root = mkRootUrl($request, (bool)$container['config'][AppConstants::RELATIVE_URLS]);
    $version = $container['settings']['bbs']['version'];
    $cdir = $container['calibre']->calibre_dir;
    $clm = $container['calibre']->calibre_last_modified;
    $gen = new OpdsGenerator($root,
        $version,
        $cdir,
        date(DATE_ATOM, $clm),
        $container['l10n']);
    return $gen;
}


/**
 * Create and send the typical OPDS response
 * @param ResponseInterface $response
 * @param string $content
 * @param string $type
 * @return ResponseInterface
 */
function mkOpdsResponse(ResponseInterface $response, string $content, string $type): ResponseInterface
{
    $response->getBody()->write($content);
    return $response->withStatus(200)
        ->withHeader('Content-Type', $type)
        ->withHeader('Content-Length', strlen($content));
}

/**
 * @param ServerRequestInterface $request
 * @param string $basePath from Slim App::getBasePath() https://discourse.slimframework.com/t/slim-4-get-base-url/3406
 * @param bool $relativeUrls
 * @return string root URL
 *
 * @deprecated deprecated, is this method still required?
 */
function mkRootUrl(ServerRequestInterface $request, string $basePath, $relativeUrls = true): string
{
    $uri = $request->getUri();
    if ($relativeUrls) {
        $root = rtrim($basePath, "/");
    } else {
        $root = rtrim($basePath . $uri->getPath(), "/");
    }
    return $root;
}

/**
 * Validate and process important configuration changes
 *
 * @param ContainerInterface $c Container
 * @param Configuration $config current configuration
 * @param Configuration $newConfig new configuration
 * @param string $key config key
 * @param mixed $value config value
 * @return int          0 = no error, >0 error
 */
function processNewConfig(ContainerInterface $c, Configuration $config, Configuration $newConfig, string $key, $value): int
{
    switch ($key) {
        case AppConstants::CALIBRE_DIR:
            ## Check for consistency - calibre directory
            # Calibre dir is  empty --> error
            if (empty($value)) {
                return AppConstants::ERROR_NO_CALIBRE_PATH;
            }
            # Calibre dir changed, check it for existence, delete thumbnails of old calibre library
            if ($value != $config[AppConstants::CALIBRE_DIR]) {
                if (!Calibre::checkForCalibre($value)) {
                    return AppConstants::ERROR_BAD_CALIBRE_DB;
                } else {
                    $c['bbs']->clearThumbnails();
                }
            }
            break;
        case AppConstants::KINDLE:
            # Switch off Kindle feature, if no valid email address supplied
            if ($value == "1") {
                if (empty($newConfig[AppConstants::KINDLE_FROM_EMAIL])) {
                    return AppConstants::ERROR_NO_KINDLEFROM;
                } elseif (!isEMailValid($newConfig[AppConstants::KINDLE_FROM_EMAIL])) {
                    return AppConstants::ERROR_BAD_KINDLEFROM;
                }
            }
            break;
        case AppConstants::THUMB_GEN_CLIPPED:
            ## Check for a change in the thumbnail generation method
            if ($value != $config[AppConstants::THUMB_GEN_CLIPPED]) {
                # Delete old thumbnails if necessary
                $c['bbs']->clearThumbnails();
            }
            break;
        case AppConstants::PAGE_SIZE:
            ## Check for a change in page size, min 1, max 100
            if ($value != $config[AppConstants::PAGE_SIZE]) {
                if ($value < 1 || $value > 100) {
                    return AppConstants::ERROR_BAD_PAGESIZE;
                }
            }
            break;
        default:
            return 0;
    }
    return 0;
}

# Check for valid email address format
function isEMailValid(string $mail): bool
{
    return (filter_var($mail, FILTER_VALIDATE_EMAIL) !== FALSE);
}

/**
 * Check if a title is available to the current user
 * @param bool $login_required
 * @param array $user
 * @param array $book_details output of BicBucStriim::title_details()
 * @return  bool      true if the title is not available for the user, else false
 */
function title_forbidden(bool $login_required, User $user, array $book_details): bool
{
    if (!$login_required) {
        return false;
    } else {
        if (empty($user->languages) && empty($user->tags)) {
            return false;
        } else {
            if (!empty($user->languages)) {
                $lang_found = false;
                foreach ($book_details['langcodes'] as $langcode) {
                    if ($langcode === $user->languages) {
                        $lang_found = true;
                        break;
                    }
                }
                if (!$lang_found) {
                    return true;
                }
            }
            if (!empty($user->tags)) {
                $tag_found = false;
                foreach ($book_details['tags'] as $tag) {
                    if ($tag->name === $user->tags) {
                        $tag_found = true;
                        break;
                    }
                }
                if ($tag_found) {
                    return true;
                }
            }
            return false;
        }
    }
}
