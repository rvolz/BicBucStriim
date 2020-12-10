<?php

namespace App\Application\Actions\Authors;

use App\Application\Actions\ActionPayload;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

/**
 * Class CreateAuthorThumbnailAction
 * Upload an author thumbnail picture. Works only with JPG/PNG, max. size 3MB.
 * @package App\Application\Actions\Authors
 */
class CreateAuthorThumbnailAction extends AuthorsAction
{

    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        // parameter checking
        if (!is_object($this->calibre->author($id))) {
            $msg = sprintf("CreateAuthorThumbnailAction: no author data found for id %d",$id);
            $this->logger->error($msg);
            throw new DomainRecordNotFoundException($msg);
        }

        // TODO replace with https://www.slimframework.com/docs/v4/cookbook/uploading-files.html?
        $allowedExts = array("jpeg", "jpg", "png");
        #$temp = explode(".", $_FILES["file"]["name"]);
        #$extension = end($temp);
        $extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
        $this->logger->debug('CreateAuthorThumbnailAction: ' . $_FILES["file"]["name"]);
        if ((($_FILES["file"]["type"] == "image/jpeg")
                || ($_FILES["file"]["type"] == "image/jpg")
                || ($_FILES["file"]["type"] == "image/pjpeg")
                || ($_FILES["file"]["type"] == "image/x-png")
                || ($_FILES["file"]["type"] == "image/png"))
            && ($_FILES["file"]["size"] < 3145728)
            && in_array($extension, $allowedExts)
        ) {
            $this->logger->debug('CreateAuthorThumbnailAction: filetype ' . $_FILES["file"]["type"] . ', size ' . $_FILES["file"]["size"]);
            if ($_FILES["file"]["error"] > 0) {
                $this->logger->error('CreateAuthorThumbnailAction: upload error ' . $_FILES["file"]["error"]);
                $ap = new ActionPayload(500, array(
                    'msg' => $this->getMessageString('author_thumbnail_upload_error1'. ': ' . $_FILES["file"]["error"])
                ));
            } else {
                $this->logger->debug('CreateAuthorThumbnailAction: upload ok, converting');
                $author = $this->calibre->author($id);
                $created = $this->bbs->editAuthorThumbnail($id,
                    $author->name,
                    $this->config[AppConstants::THUMB_GEN_CLIPPED],
                    $_FILES["file"]["tmp_name"],
                    $_FILES["file"]["type"]);
                $this->logger->debug('CreateAuthorThumbnailAction: converted, redirecting');
                $ap = new ActionPayload(200, array(
                    'msg' => $this->getMessageString('admin_modified')
                ));
            }
        } else {
            $this->logger->error('CreateAuthorThumbnailAction: Uploaded thumbnail too big or wrong type');
            $ap = new ActionPayload(400, array(
                'msg' => $this->getMessageString('author_thumbnail_upload_error2')
            ));
        }
        return $this->respondWithData($ap);
    }
}