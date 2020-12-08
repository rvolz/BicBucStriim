<?php
declare(strict_types=1);

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


    /**
     * Check for admin permissions. Currently this is only the user
     * <em>admin</em>, ID 1.
     * @return boolean  true if admin user, else false
     */
    protected function is_admin(): bool
    {
        if ($this->is_authenticated()) {
            return $this->user->isAdmin();
        } else {
            return false;
        }
    }

    /**
     * Check if the current user was authenticated
     * @return boolean  true if authenticated, else false
     */
    protected function is_authenticated(): bool
    {
        return $this->user->isValid();
    }

    /**
     * Checks if a thumbnail image is available for a book
     * @param $book
     * @return bool
     */
    protected function checkThumbnail($book): bool
    {
        return $this->bbs->isTitleThumbnailAvailable($book->id);
    }
}