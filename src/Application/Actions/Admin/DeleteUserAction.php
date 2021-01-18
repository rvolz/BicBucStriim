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
        if (!$this->is_admin())
            return $this->refuseNonAdmin();
        $id = (int) $this->resolveArg('id');
        // parameter checking
        if (!is_numeric($id)) {
            $this->logger->warning('admin_delete_user: invalid user id ' . $id);
            throw new HttpBadRequestException($this->request);
        }

        $flash = [];
        $this->logger->debug('admin_delete_user: ' . var_export($id, true));
        $success = $this->bbs->deleteUser($id);
        if ($success) {
            $flash['info'] = $this->getMessageString('admin_modified');
        } else {
            $flash['error'] = $this->getMessageString('admin_modify_error');
        }
        $users = $this->bbs->users();
        return $this->respondWithPage('admin_users.twig', array(
            'page' => $this->mkPage($this->getMessageString('admin_users'), 0, 2),
            'users' => $users,
            'flash' => $flash,
            'isadmin' => $this->is_admin()));
    }
}