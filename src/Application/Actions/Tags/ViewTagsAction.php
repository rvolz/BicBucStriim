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
            [$pos, $total] = $this->calibre->tagsCalcNamePos($jumpTarget, $search);
            $max_pgs = (int)($total / $pg_size);
            if ($total % $pg_size > 0) {
                $max_pgs += 1;
            }
            $index = (int)($pos / $pg_size);
            if ($index >= $max_pgs) {
                $index -= 1;
            }
        }

        $tl = $this->calibre->tagsSlice($index, $pg_size, $search);

        return $this->respondWithPage('tags.twig', [
            'page' => $this->mkPage($this->getMessageString('tags'), 4, 1),
            'url' => 'tags',
            'tags' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search->getSearchTerm(),
            'search_options' => $search->toMask()]);
    }
}
