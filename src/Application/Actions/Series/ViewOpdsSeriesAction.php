<?php


namespace App\Application\Actions\Series;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewOpdsSeriesAction extends \App\Application\Actions\CalibreOpdsAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $initial = (int) $this->resolveArg('initial');
        $id = (int) $this->resolveArg('id');
        $index = (int) $this->resolveQueryParam('index');
        // parameter checking
        if ($id < 0 || $index < 0) {
            $this->logger->warning('opdsBySeries: invalid tag id ' . $id . ' or page id ' . $index);
            throw new HttpBadRequestException($this->request);
        }

        $filter = $this->getFilter();
        $lang = $this->l10n->user_lang;
        $pg_size = $this->config[AppConstants::PAGE_SIZE];
        $tl = $this->calibre->seriesDetailsSlice($lang, $id, $index, $pg_size, $filter);
        $books1 = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map(array($this, 'checkThumbnailOpds'), $books1);
        $cat = $this->gen->booksForSeriesCatalog(null, $books, $initial, $tl['tag'], false,
            $tl['page'], $this->getNextSearchPage($tl), $this->getLastSearchPage($tl));
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }
}