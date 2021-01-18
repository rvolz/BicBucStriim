<?php


namespace App\Application\Actions\Admin;


use App\Application\Actions\ActionPayload;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;

class CreateUserAction extends AdminAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        if (!$this->is_admin())
            return $this->refuseNonAdmin();
        $flash = [];
        $user_data = $this->request->getParsedBody();
        if ($user_data['function'] == 'createuser') {
            $this->logger->debug('admin_add_user: ' . var_export($user_data, true), [__FILE__]);
            try {
                $user = $this->bbs->addUser($user_data['newuser_name'], $user_data['newuser_password']);
            } catch (Exception $e) {
                $this->logger->error('admin_add_user: error for adding user ' . var_export($user_data, true), [__FILE__]);
                $this->logger->error('admin_add_user: exception ' . $e->getMessage(), [__FILE__]);
                $user = null;
            }
            if (isset($user)) {
                $flash['info'] = $this->getMessageString('admin_modified');
            } else {
                $flash['error'] = $this->getMessageString('admin_modify_error');
            }
        } elseif ($user_data['function'] == 'deleteuser') {
            $id = $user_data['userid'];
            $this->logger->debug('admin_delete_user: ' . var_export($id, true), [__FILE__]);
            $success = $this->bbs->deleteUser($id);
            if ($success) {
                $flash['info'] = $this->getMessageString('admin_modified');
            } else {
                $flash['error'] = $this->getMessageString('admin_modify_error');
            }
        } else {
            $this->logger->error('unknown function: ' . var_export($user_data, true), [__FILE__]);
            $flash['error'] = $this->getMessageString('unknown_error1');
        }

        $users = $this->bbs->users();
        return $this->respondWithPage('admin_users.twig', array(
            'page' => $this->mkPage($this->getMessageString('admin_users'), 0, 2),
            'users' => $users,
            'flash' => $flash,
            'isadmin' => $this->is_admin()));
    }
}