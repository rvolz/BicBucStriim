<?php


namespace App\Application\Actions\Admin;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewVersionCheckAction extends AdminAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $versionAnswer = array();
        $contents = file_get_contents(AppConstants::VERSION_URL);
        if ($contents == false) {
            $versionClass = 'error';
            $versionAnswer = sprintf($this->getMessageString('admin_new_version_error'), APP_VERSION);
        } else {
            $versionInfo = json_decode($contents);
            $version = APP_VERSION;
            if (strpos(APP_VERSION, '-') === false) {
                $v = preg_split('/-/', APP_VERSION);
                $version = $v[0];
            }
            $result = version_compare($version, $versionInfo->{'version'});
            if ($result === -1) {
                $versionClass = 'success';
                $msg1 = sprintf($this->getMessageString('admin_new_version'), $versionInfo->{'version'}, APP_VERSION);
                $msg2 = sprintf("<a href=\"%s\">%s</a>", $versionInfo->{'url'}, $versionInfo->{'url'});
                $msg3 = sprintf($this->getMessageString('admin_check_url'), $msg2);
                $versionAnswer = $msg1 . '. ' . $msg3;
            } else {
                $versionClass = '';
                $versionAnswer = sprintf($this->getMessageString('admin_no_new_version'), APP_VERSION);
            }
        }
        return $this->respondWithPage('admin_version.html', array(
            'page' => $this->mkPage($this->getMessageString('admin_check_version'), 0, 2),
            'versionClass' => $versionClass,
            'versionAnswer' => $versionAnswer,
            'isadmin' => true,
        ));
    }
}