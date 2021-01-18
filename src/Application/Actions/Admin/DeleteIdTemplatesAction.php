<?php


namespace App\Application\Actions\Admin;


use App\Application\Actions\ActionPayload;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class DeleteIdTemplatesAction extends AdminAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        if (!$this->is_admin())
            return $this->refuseNonAdmin();
        $id = (int) $this->resolveArg('id');
        // parameter checking
        if (!preg_match('/^\w+$/u',$id)) {
            $this->logger->warning('admin_clear_idtemplate: invalid template id ' . $id);
            throw new HttpBadRequestException($this->request);
        }

        $this->logger->debug('admin_clear_idtemplate: ' . var_export($id, true));
        $success = $this->bbs->deleteIdTemplate($id);
        if ($success) {
            $ap = new ActionPayload(200, array('msg' => $this->getMessageString('admin_modified')));
        } else {
            $ap = new ActionPayload(404, array('msg' => $this->getMessageString('admin_modify_error')));
        }
        return $this->respondWithData($ap);
    }
}