<?php

namespace App\Application\Actions\Admin;

use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class DeleteUserAction extends AdminAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        // parameter checking
        if (!is_numeric($id)) {
            $this->logger->warning('admin_delete_user: invalid user id ' . $id);
            throw new HttpBadRequestException($this->request);
        }

        $this->logger->debug('admin_delete_user: ' . var_export($id, true));
        $success = $this->bbs->deleteUser($id);
        if ($success) {
            $ap = new ActionPayload(
                200,
                [
                    'msg' => $this->getMessageString('admin_modified'),
                ]
            );
        } else {
            $ap = new ActionPayload(
                500,
                [
                    'msg' => $this->getMessageString('admin_modify_error'),
                ]
            );
        }
        return $this->respondWithData($ap);
    }
}
