<?php


namespace App\Application\Actions\Titles;


use App\Domain\BicBucStriim\AppConstants;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewTitlesAction extends TitlesAction
{

    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        $index = 0;
        if ($this->hasQueryParam('index'))
            $index = (int) $this->resolveQueryParam('index');
        // parameter checking
        if ($index < 0) {
            $this->logger->warning('ViewAuthorsAction: invalid page id ' . $index);
            throw new HttpBadRequestException($this->request);
        }

        $search = $this->checkAndGenSearchOptions();
        $lang = $this->l10n->user_lang;
        $pg_size = $this->config[AppConstants::PAGE_SIZE];
        $filter = $this->getFilter();

        $sort = '';
        if ($this->hasQueryParam('sort')) {
            $sort = $this->resolveQueryParam('sort');
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
                        $this->logger->error('ViewTitlesAction: invalid sort order ' . $this->config[AppConstants::TITLE_TIME_SORT]);
                        $tl = $this->calibre->timestampOrderedTitlesSlice($lang, $index, $pg_size, $filter, $search);
                        break;
                }
            } else {
                $tl = $this->calibre->titlesSlice($lang, $index, $pg_size, $filter, $search);
            }
        } else
            $tl = $this->calibre->titlesSlice($lang, $index, $pg_size, $filter, $search);
        $books = array_map(array($this, 'checkThumbnail'), $tl['entries']);
        return $this->respondWithPage('titles.twig', array(
            'page' => $this->mkPage($this->getMessageString('titles'), 2, 1),
            'url' => 'titles',
            'books' => $books,
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search,
            'sort' => $sort));
    }
}