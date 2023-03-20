<?php

declare(strict_types=1);

namespace App\Application\Actions\Statics;

use App\Application\Actions\CalibreHtmlAction;
use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\BicBucStriim\L10n;
use App\Domain\Calibre\CalibreRepository;
use App\Domain\User\User;
use Psr\Log\LoggerInterface;
use Slim\HttpCache\CacheProvider;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

/**
 * Class StaticsAction covers static resources like book files, images.
 * @package App\Application\Actions\Statics
 */
abstract class StaticsAction extends CalibreHtmlAction
{
    /**
     * @var CacheProvider HTTP-Caching codes
     */
    protected CacheProvider $cache;


    /**
     * @param LoggerInterface $logger
     * @param BicBucStriimRepository $bbs
     * @param CalibreRepository $calibre
     * @param Configuration $config
     * @param Twig $twig
     * @param L10n $l10n
     */
    public function __construct(
        LoggerInterface $logger,
        BicBucStriimRepository $bbs,
        CalibreRepository $calibre,
        Configuration $config,
        Twig $twig,
        L10n $l10n
    )
    {
        parent::__construct($logger, $bbs, $calibre, $config, $twig, $l10n);
        $this->cache = new CacheProvider();
    }

    /**
     * Calculate an ETag for a file resource.
     * @param string $fname path to file resource
     * @return string MD5 Hash
     */
    public function calcEtag(string $fname): string
    {
        $mtime = filemtime($fname);
        return md5("{$fname}-{$mtime}");
    }

    /**
     * Send a small file (cover, thumbnail) with etag header.
     * @param Stream $stream
     * @param string $etag
     * @param string $type
     * @return Response
     */
    public function respondWithArtefact(Stream $stream, string $etag, string $type): Response
    {
        $responseE = $this->cache->withEtag($this->response, $etag);
        $responseC = $this->cache->allowCache($responseE, 'private', time()+60, true);

        return $responseC
            ->withHeader('Content-Type', $type)
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withBody($stream)
            ->withStatus(200);
        // will be done by middleware
        //->withHeader('Content-Length', $stream->getSize())
    }

    /**
     * * Send a large file (book) as a download.
     * @param LazyOpenStream $stream
     * @param string $filename
     * @param string $type
     * @return Response
     */
    public function respondWithDownload(LazyOpenStream $stream, string $filename, string $type): Response
    {
        return $this->response
            ->withHeader('Content-Type', $type)
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withStatus(200)
            ->withBody($stream);
        //->withHeader('Content-Length', $stream->getSize());
        // will be done by middleware
    }

    /**
     * Check if a title is available for a user
     * @param string $languages user language filter
     * @param string $tags user tag filter
     * @param array $book_details output of BicBucStriim::title_details()
     * @return  bool      true if the title is not available for the user, else false
     */
    public function title_forbidden(string $languages, string $tags, array $book_details): bool
    {
        if (!empty($languages)) {
            $lang_found = false;
            foreach ($book_details['langcodes'] as $langcode) {
                if ($langcode === $languages) {
                    $lang_found = true;
                    break;
                }
            }
            if (!$lang_found) {
                return true;
            }
        }
        if (!empty($tags)) {
            $tag_found = false;
            foreach ($book_details['tags'] as $tag) {
                if ($tag->name === $tags) {
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
