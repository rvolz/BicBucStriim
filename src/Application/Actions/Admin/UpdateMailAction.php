<?php


namespace App\Application\Actions\Admin;


use App\Domain\BicBucStriim\AppConstants;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateMailAction extends AdminAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $mail_data = $this->request->getParsedBody();
        $this->logger->debug('admin_change_smtp_configuration: ' . var_export($mail_data, true));
        $mail_config = array(
            AppConstants::SMTP_USER => $mail_data['username'],
            AppConstants::SMTP_PASSWORD => $mail_data['password'],
            AppConstants::SMTP_SERVER => $mail_data['smtpserver'],
            AppConstants::SMTP_PORT => $mail_data['smtpport'],
            AppConstants::SMTP_ENCRYPTION => $mail_data['smtpenc']);
        $this->bbs->saveConfigs($mail_config);
        return $this->respondWithPage('admin_mail.twig', array(
            'page' => $this->mkPage($this->getMessageString('admin_smtp'), 0, 2),
            'mail' => $mail_data,
            'encryptions' => $this->mkEncryptions(),
            'isadmin' => $this->is_admin()));
    }
}