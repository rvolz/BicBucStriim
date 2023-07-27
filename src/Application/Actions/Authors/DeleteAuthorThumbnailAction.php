<?php

namespace App\Application\Actions\Authors;

use App\Application\Actions\ActionPayload;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class DeleteAuthorThumbnailAction extends \App\Application\Actions\CalibreHtmlAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        // parameter checking
        if (!is_object($this->calibre->author($id))) {
            $msg = sprintf("DeleteAuthorThumbnailAction: no author data found for id %d", $id);
            $this->logger->error($msg);
            throw new DomainRecordNotFoundException($msg);
        }

        $del = $this->bbs->deleteAuthorThumbnail($id);
        if ($del) {
            $ap = new ActionPayload(200, [
                'msg' => $this->getMessageString('admin_modified'),
            ]);
        } else {
            $ap = new ActionPayload(500, [
                'msg' => $this->getMessageString('admin_modify_error'),
            ]);
        }
        return $this->respondWithData($ap);
    }
}
