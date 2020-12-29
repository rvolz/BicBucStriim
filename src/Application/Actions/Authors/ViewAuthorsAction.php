<?php

namespace App\Application\Actions\Authors;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Calibre\Author;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewAuthorsAction extends AuthorsAction
{

    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        $index = $this->getIndexParam(__CLASS__);
        $jumpTarget = $this->getJumpTargetParam(__CLASS__);
        $search = $this->checkAndGenSearchOptions();
        $pg_size = $this->config[AppConstants::PAGE_SIZE];

        // Jumping overrides normal navigation
        if (!empty($jumpTarget)) {
            $pos = $this->calibre->calcInitialPos('sort', 'authors', $jumpTarget, $search);
            $index = $pos / $pg_size;
        }

        $tl = $this->calibre->authorsSlice($index, $pg_size, $search);

        foreach ($tl['entries'] as $author) {
            $author->thumbnail = $this->bbs->getAuthorThumbnail($author->id);
        }
        return $this->respondWithPage('authors.twig', array(
            'page' => $this->mkPage($this->getMessageString('authors'), 3, 1),
            'url' => 'authors',
            'authors' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search->getSearchTerm(),
            'search_options' => $search->toMask()));
    }
}