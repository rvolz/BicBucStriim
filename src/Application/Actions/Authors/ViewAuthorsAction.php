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
            [$pos, $total] = $this->calibre->authorsCalcNamePos( $jumpTarget, $search);
            $max_pgs = (int)($total / $pg_size);
            if ($total % $pg_size > 0)
                $max_pgs += 1;
            $index = (int)($pos / $pg_size);
            if ($index >= $max_pgs)
                $index -= 1;
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