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
        $id = (int) $this->resolveArg('id');
        $details = $this->calibre->authorDetails($id);
        if (is_null($details)) {
            $msg = sprintf("ViewAuthorAction: no author data found for id %d",$id);
            $this->logger->error($msg);
            throw new DomainRecordNotFoundException($msg);
        }
        $this->respondWithPage('author_detail.html', array(
            'page' => $this->mkPage($this->getMessageString('author_details'), 3, 2),
            'author' => $details['author'],
            'books' => $details['books']));
    }
}