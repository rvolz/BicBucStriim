<?php
declare(strict_types=1);

namespace App\Application\Actions;


use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\Calibre\SearchOptions;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

abstract class BasicAction extends Action
{
    protected BicBucStriimRepository $bbs;
    protected Configuration $config;

    /**
     * @param LoggerInterface $logger
     * @param BicBucStriimRepository $bbs
     * @param Configuration $config
     */
    public function __construct(LoggerInterface $logger,
                                BicBucStriimRepository $bbs,
                                Configuration $config)
    {
        parent::__construct($logger);
        $this->bbs = $bbs;
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
     * Checks if the current request/action includes query parameters for search
     * and returns them.
     * @return SearchOptions
     */
    protected function checkAndGenSearchOptions(): ?SearchOptions
    {
        $search = '';
        if ($this->hasQueryParam('search'))
            $search = trim($this->resolveQueryParam('search'));
        $options = 0;
        if ($this->hasQueryParam('options'))
            $options = (int) $this->resolveQueryParam('option');
        if (!empty($search))
            return SearchOptions::fromParams($search, $options);
        else
            return SearchOptions::genEmpty();
    }

    /**
     * Checks if a thumbnail image is available for a book, adds it and returns the book
     * @param object $book
     * @return object
     */
    protected function checkThumbnail(object $book): object
    {
        $book->thumbnail = $this->bbs->isTitleThumbnailAvailable($book->id);
        return $book;

    }

    /**
     * Checks if a thumbnail image is available for an author, adds it and returns the author
     * @param object $author
     * @return object
     */
    protected function checkAuthorThumbnail(object $author): object
    {
        $author->thumbnail = $this->bbs->getAuthorThumbnail($author->id);
        return $author;
    }

    /**
     * Check and return the query parameter 'index', default 0
     * @param string $actionName
     * @return int
     * @throws HttpBadRequestException if index < 0
     */
    protected function getIndexParam(string $actionName): int
    {
        $index = 0;
        if ($this->hasQueryParam('index'))
            $index = (int) $this->resolveQueryParam('index');
        if ($index < 0) {
            $this->logger->warning('invalid page id ' . $index, [$actionName]);
            throw new HttpBadRequestException($this->request);
        }
        return $index;
    }

    protected function getJumpTargetParam(string $actionName): string
    {
        $jumpTarget = '';
        if ($this->hasQueryParam('jumpTarget'))
            $jumpTarget = $this->resolveQueryParam('jumpTarget');
        return $jumpTarget;
    }

    /**
     * Calculate the next page number for search results
     * @param array $tl search result
     * @return int      page index or NULL
     */
    protected function getNextSearchPage(array $tl): int
    {
        if ($tl['page'] < $tl['pages'] - 1)
            $nextPage = $tl['page'] + 1;
        else
            $nextPage = 0;
        return $nextPage;
    }

    /**
     * Calculate the last page number for search results
     * @param array $tl search result
     * @return int      page index
     */
    protected function getLastSearchPage(array $tl): int
    {
        if ($tl['pages'] == 0)
            $lastPage = 0;
        else
            $lastPage = $tl['pages'] - 1;
        return $lastPage;
    }


}