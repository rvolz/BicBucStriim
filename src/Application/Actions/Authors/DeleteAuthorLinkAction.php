<?php


namespace App\Application\Actions\Authors;


use App\Application\Actions\ActionPayload;
use App\Application\Actions\CalibreHtmlAction;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;

class DeleteAuthorLinkAction extends CalibreHtmlAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        $link = (int) $this->resolveArg('link');
        // parameter checking
        $author = $this->calibre->author($id);
        if (!is_object($author)) {
            $msg = sprintf("DeleteAuthorLinkAction: no author data found for id %d",$id);
            $this->logger->error($msg);
            throw new DomainRecordNotFoundException($msg);
        }

        $this->logger->debug('DeleteAuthorLinkAction:author ' . $id . ', link ' . $link);
        $link = $this->bbs->deleteAuthorLink($id, $link);
        if (!is_null($link)) {
            $ap = new ActionPayload(200, array(
                'msg' => $this->getMessageString('admin_modified')
            ));;
        } else {
            $ap = new ActionPayload(500, array(
                'msg' => $this->getMessageString('admin_modify_error')
            ));
        }
        return $this->respondWithData($ap);
    }
}