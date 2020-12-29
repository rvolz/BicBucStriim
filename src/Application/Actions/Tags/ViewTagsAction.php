<?php


namespace App\Application\Actions\Tags;


use App\Domain\BicBucStriim\AppConstants;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewTagsAction extends TagsAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $index = $this->getIndexParam(__CLASS__);
        $jumpTarget = $this->getJumpTargetParam(__CLASS__);
        $search = $this->checkAndGenSearchOptions();
        $pg_size = $this->config[AppConstants::PAGE_SIZE];

        // Jumping overrides normal navigation
        if (!empty($jumpTarget)) {
            $pos = $this->calibre->calcInitialPos('name', 'tags', $jumpTarget, $search);
            $index = $pos / $pg_size;
        }

        $tl = $this->calibre->tagsSlice($index, $pg_size, $search);

        return $this->respondWithPage('tags.twig', array(
            'page' => $this->mkPage($this->getMessageString('tags'), 4, 1),
            'url' => 'tags',
            'tags' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search->getSearchTerm(),
            'search_options' => $search->toMask()));
    }
}