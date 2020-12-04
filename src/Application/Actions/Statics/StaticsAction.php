<?php
declare(strict_types=1);

namespace App\Application\Actions\Statics;



use App\Application\Actions\Action;
use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\Calibre\CalibreRepository;
use Psr\Log\LoggerInterface;
use Slim\HttpCache\CacheProvider;
use Slim\Psr7\Stream;
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
     * @param LoggerInterface $logger
     * @param BicBucStriimRepository $bbs
     * @param CalibreRepository $calibre
     */
    public function __construct(LoggerInterface $logger, BicBucStriimRepository $bbs, CalibreRepository $calibre)
    {
        parent::__construct($logger);
        $this->bbs = $bbs;
        $this->calibre = $calibre;
        $this->cache = new CacheProvider();
    }

    /**
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
}