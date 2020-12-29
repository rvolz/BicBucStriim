<?php


namespace App\Application\Actions\Titles;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Calibre\SearchOptions;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewTitlesAction extends TitlesAction
{

    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        $index = $this->getIndexParam(__CLASS__);

        $sort = AppConstants::TITLE_ALPHA_SORT;
        if ($this->hasQueryParam('sort')) {
            $sortP = $this->resolveQueryParam('sort');
            if ($sortP == 'byReverseDate') {
                $sort = AppConstants::TITLE_TIME_SORT;
            } elseif ($sortP == 'byTitle') {
                $sort = AppConstants::TITLE_ALPHA_SORT;
            } else {
                $this->logger->warning('invalid sort id ' . $sortP, [__FILE__]);
                throw new HttpBadRequestException($this->request);
            }
        }

        $jumpTarget = $this->getJumpTargetParam(__CLASS__);
        $search = $this->checkAndGenSearchOptions();
        $pg_size = $this->config[AppConstants::PAGE_SIZE];
        // Jumping overrides normal navigation
        if (!empty($jumpTarget)) {
            if ($sort == AppConstants::TITLE_ALPHA_SORT)
                $pos = $this->calibre->calcInitialPos('sort', 'books', $jumpTarget, $search);
            else
                $pos = $this->calibre->titlesCalcYearPos($jumpTarget, $search, $sort);
            $index = $pos / $pg_size;
        }

        $tl = $this->getTitles($index, $sort, $search, $pg_size);
        $books = array_map(array($this, 'checkThumbnail'), $tl['entries']);
        return $this->respondWithPage('titles.twig', array(
            'page' => $this->mkPage($this->getMessageString('titles'), 2, 1),
            'url' => 'titles',
            'books' => $books,
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search->getSearchTerm(),
            'search_options' => $search->toMask(),
            'sort' => $sort));
    }

    protected function getTitles(int $index, string $sort, SearchOptions $search, int $pg_size): array
    {
        $lang = $this->l10n->user_lang;
        $filter = $this->getFilter();

        if ($sort == 'byReverseDate') {
            switch ($this->config[AppConstants::TITLE_TIME_SORT]) {
                case AppConstants::TITLE_TIME_SORT_TIMESTAMP:
                    $tl = $this->calibre->timestampOrderedTitlesSlice($lang, $index, $pg_size, $filter, $search);
                    break;
                case AppConstants::TITLE_TIME_SORT_PUBDATE:
                    $tl = $this->calibre->pubdateOrderedTitlesSlice($lang, $index, $pg_size, $filter, $search);
                    break;
                case AppConstants::TITLE_TIME_SORT_LASTMODIFIED:
                    $tl = $this->calibre->lastmodifiedOrderedTitlesSlice($lang, $index, $pg_size, $filter, $search);
                    break;
                default:
                    $this->logger->error('invalid sort order ' . $this->config[AppConstants::TITLE_TIME_SORT], [__CLASS__]);
                    $tl = $this->calibre->timestampOrderedTitlesSlice($lang, $index, $pg_size, $filter, $search);
                    break;
            }
        } else {
            $tl = $this->calibre->titlesSlice($lang, $index, $pg_size, $filter, $search);
        }
        return $tl;
    }
}