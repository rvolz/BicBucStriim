<?php


namespace App\Application\Actions\Tags;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewTagAction extends \App\Application\Actions\CalibreHtmlAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        $index = 0;
        if ($this->hasQueryParam('index'))
            $index = (int) $this->resolveQueryParam('index');
        // parameter checking
        if ($index < 0) {
            $this->logger->warning('ViewASeriesAction: invalid page id ' . $index);
            throw new HttpBadRequestException($this->request);
        }

        $filter = $this->getFilter();
        $tl = $this->calibre->tagDetailsSlice(
            $this->l10n->user_lang,
            $id,
            $index,
            $this->config[AppConstants::PAGE_SIZE],
            $filter);
        if (empty($tl)) {
            $msg = sprintf("ViewTagAction: no tag data found for id %d", $id);
            $this->logger->error($msg);
            throw new DomainRecordNotFoundException($msg);
        }

        $books = array_map(array($this, 'checkThumbnail'), $tl['entries']);
        return $this->respondWithPage('tag_detail.html', array(
            'page' => $this->mkPage($this->getMessageString('tag_details'), 4, 2),
            'url' => 'tags/' . $id .'/',
            'tag' => $tl['tag'],
            'books' => $books,
            'curpage' => $tl['page'],
            'pages' => $tl['pages']));
    }
}