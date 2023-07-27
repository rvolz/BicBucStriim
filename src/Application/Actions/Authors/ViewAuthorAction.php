<?php

namespace App\Application\Actions\Authors;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewAuthorAction extends AuthorsAction
{
    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        $id = (int)$this->resolveArg('id');
        $index = 0;
        if ($this->hasQueryParam('index')) {
            $index = (int)$this->resolveQueryParam('index');
        }
        // parameter checking
        if ($index < 0) {
            $this->logger->warning('ViewAuthorAction: invalid page id ' . $index);
            throw new HttpBadRequestException($this->request);
        }

        $filter = $this->getFilter();
        $tl = $this->calibre->authorDetailsSlice(
            $this->l10n->user_lang,
            $id,
            $index,
            $this->config[AppConstants::PAGE_SIZE],
            $filter
        );
        if (empty($tl)) {
            $msg = sprintf("ViewAuthorAction: no title data found for id %d", $id);
            $this->logger->error($msg);
            throw new DomainRecordNotFoundException($msg);
        }

        $books = array_map([$this, 'checkThumbnail'], $tl['entries']);

        $series = $this->calibre->authorSeries($id, $books);

        $author = $tl['author'];
        $author->thumbnail = $this->bbs->getAuthorThumbnail($id);
        $author->links = $this->bbs->authorLinks($id);
        return $this->respondWithPage('author_detail.html', [
            'page' => $this->mkPage($this->getMessageString('author_details'), 3, 2),
            'url' => 'authors/' . $id . '/',
            'author' => $tl['author'],
            'books' => $books,
            'series' => $series,
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'isadmin' => $this->is_admin()]);
    }
}
