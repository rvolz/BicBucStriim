<?php


namespace App\Application\Actions;


use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\Calibre\CalibreRepository;
use App\Domain\User\User;
use Psr\Log\LoggerInterface;
use Slim\HttpCache\CacheProvider;

abstract class BasicAction extends Action
{
    /**
     * @var BicBucStriimRepository
     */
    protected BicBucStriimRepository $bbs;
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
     * @param Configuration $config
     * @param User $user
     */
    public function __construct(LoggerInterface $logger,
                                BicBucStriimRepository $bbs,
                                Configuration $config,
                                User $user)
    {
        parent::__construct($logger);
        $this->bbs = $bbs;
        $this->user = $user;
        $this->config = $config;
    }
}