<?php
declare(strict_types=1);

namespace App\Application\Actions\Statics;



use App\Application\Actions\Action;
use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\Calibre\CalibreRepository;
use App\Domain\User\User;
use Psr\Log\LoggerInterface;
use Slim\HttpCache\CacheProvider;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Class StaticsAction covers static resources like book files, images.
 * @package App\Application\Actions\Statics
 */
abstract class StaticsAction extends Action
{

    /**
     * @var CacheProvider HTTP-Caching codes
     */
    protected CacheProvider $cache;
    /**
     * @var BicBucStriimRepository
     */
    protected BicBucStriimRepository $bbs;
    /**
     * @var CalibreRepository
     */
    protected CalibreRepository $calibre;
    /**
     * @var User
     */
    protected User $user;
    /**
     * @var Configuration
     */
    protected Configuration $config;

    /**
     * @param LoggerInterface $logger
     * @param BicBucStriimRepository $bbs
     * @param CalibreRepository $calibre
     * @param Configuration $config
     * @param User $user
     */
    public function __construct(LoggerInterface $logger,
                                BicBucStriimRepository $bbs,
                                CalibreRepository $calibre,
                                Configuration $config,
                                User $user)
    {
        parent::__construct($logger);
        $this->bbs = $bbs;
        $this->calibre = $calibre;
        $this->user = $user;
        $this->config = $config;
        $this->cache = new CacheProvider();
    }

    /**
     * Send a small file (cover, thumbnail) with etag header.
     * @param Stream $stream
     * @param string $etag
     * @param string $type
     * @return Response
     */
    public function respondWithArtefact(Stream $stream, string $etag, string $type): Response {
        $responseE = $this->cache->withEtag($this->response, $etag);

        return $responseE
            ->withHeader('Content-Type', $type)
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Content-Length', $stream->getSize())
            ->withBody($stream)
            ->withStatus(200);
    }

    /**
     * * Send a large file (book) as a download.
     * @param LazyOpenStream $stream
     * @param string $filename
     * @param string $type
     * @return Response
     */
    public function respondWithDownload(LazyOpenStream $stream, string $filename, string $type): Response {
        return $this->response
            ->withHeader('Content-Type', $type)
            ->withHeader('Content-Disposition',  "attachment; filename=\"${$filename}\"")
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Content-Length', $stream->getSize())
            ->withBody($stream)
            ->withStatus(200);
    }
}