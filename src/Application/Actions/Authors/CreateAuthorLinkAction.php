<?php

namespace App\Application\Actions\Authors;

use App\Application\Actions\ActionPayload;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

/**
 * Class CreateAuthorLinkAction
 * Add a link to an author.
 * @package App\Application\Actions\Authors
 */
class CreateAuthorLinkAction extends AuthorsAction
{
    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        // parameter checking
        $author = $this->calibre->author($id);
        if (!is_object($author)) {
            $msg = sprintf("CreateAuthorLinkAction: no author data found for id %d", $id);
            $this->logger->error($msg);
            throw new DomainRecordNotFoundException($msg);
        }

        $link_data = $this->request->getParsedBody();
        $this->logger->debug('CreateAuthorLinkAction: ' . var_export($link_data, true));
        $ret = $this->bbs->addAuthorLink($id, $author->name, $link_data['link-description'], $link_data['link-url']);
        if ($ret) {
            $ap = new ActionPayload(200, [
                'msg' => $this->getMessageString('admin_modified'),
            ]);
            ;
        } else {
            $ap = new ActionPayload(500, [
                'msg' => $this->getMessageString('admin_modify_error'),
            ]);
        }
        return $this->respondWithData($ap);
    }
}
