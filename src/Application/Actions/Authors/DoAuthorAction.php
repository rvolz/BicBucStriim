<?php

namespace App\Application\Actions\Authors;

use App\Application\Actions\ActionPayload;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

/**
 * Class DoAuthorAction
 * Temporary fix to process thumbnails and links
 * @package App\Application\Actions\Authors
 */
class DoAuthorAction extends AuthorsAction
{

    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        $flash = array();
        $id = (int) $this->resolveArg('id');
        // parameter checking
        if (!is_object($this->calibre->author($id))) {
            $msg = sprintf("no author data found for id %d",$id);
            $this->logger->error($msg, [__FILE__]);
            throw new DomainRecordNotFoundException($msg);
        }

        $author = $this->calibre->author($id);

        // TODO replace with https://www.slimframework.com/docs/v4/cookbook/uploading-files.html?
        if (array_key_exists('file', $_FILES)) {
            $allowedExts = array("jpeg", "jpg", "png");
            #$temp = explode(".", $_FILES["file"]["name"]);
            #$extension = end($temp);
            $extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
            $this->logger->debug('upload files: ' . $_FILES["file"]["name"], [__FILE__]);
            if ((($_FILES["file"]["type"] == "image/jpeg")
                    || ($_FILES["file"]["type"] == "image/jpg")
                    || ($_FILES["file"]["type"] == "image/pjpeg")
                    || ($_FILES["file"]["type"] == "image/x-png")
                    || ($_FILES["file"]["type"] == "image/png"))
                && ($_FILES["file"]["size"] < 3145728)
                && in_array($extension, $allowedExts)
            ) {
                $this->logger->debug('filetype ' . $_FILES["file"]["type"] . ', size ' . $_FILES["file"]["size"], [__FILE__]);
                if ($_FILES["file"]["error"] > 0) {
                    $this->logger->error('upload error ' . $_FILES["file"]["error"], [__FILE__]);
                    $flash['error'] = $this->getMessageString('author_thumbnail_upload_error1' . ': ' . $_FILES["file"]["error"]);
                } else {
                    $this->logger->debug('upload ok, converting', [__FILE__]);
                    $created = $this->bbs->editAuthorThumbnail($id,
                        $author->name,
                        $this->config[AppConstants::THUMB_GEN_CLIPPED],
                        $_FILES["file"]["tmp_name"],
                        $_FILES["file"]["type"]);
                    $this->logger->debug('converted', [__FILE__]);
                    $flash['info'] = $this->getMessageString('admin_modified');
                }
            } else {
                $this->logger->error('Uploaded thumbnail too big or wrong type', [__FILE__]);
                $flash['error'] = $this->getMessageString('author_thumbnail_upload_error2');
            }
        } else {
            $post_data = $this->request->getParsedBody();
            switch ($post_data['function']) {
                case 'deleteImage':
                    $this->logger->debug('DeleteAuthorThumbnail:author ' . $id, [__FILE__]);
                    $del = $this->bbs->deleteAuthorThumbnail($id);
                    if ($del)
                        $flash['info'] = $this->getMessageString('admin_modified');
                    else
                        $flash['error'] = $this->getMessageString('admin_modify_error');
                    break;
                case 'createLink':
                    $this->logger->debug('CreateAuthorLink: ' . var_export($post_data, true), [__FILE__]);
                    $this->bbs->addAuthorLink($id, $author->name, $post_data['link-description'], $post_data['link-url']);
                    $flash['info'] = $this->getMessageString('admin_modified');
                    break;
                case 'deleteLinks':
                    $this->logger->debug('DeleteAuthorLinks: ' . var_export($post_data, true), [__FILE__]);
                    $worked = true;
                    foreach ($post_data as $k => $v) {
                        if (str_starts_with($k, 'link-')) {
                            $del = $this->bbs->deleteAuthorLink($id, $v);
                            if (!$del) {
                                $this->logger->debug('DeleteAuthorLinks: deleting link faild, ID=' . $v , [__FILE__]);
                                $worked = false;
                            }
                        }
                    }
                    if ($worked == true) {
                        $flash['info'] = $this->getMessageString('admin_modified');
                    } else {
                        $flash['error'] = $this->getMessageString('admin_modify_error');
                    }
                    break;
                default:
                    $this->logger->error('unknown function: ' . var_export($post_data, true), [__FILE__]);
                    $flash['error'] = $this->getMessageString('unknown_error1');
            }
        }

        $index = 0;
        $filter = $this->getFilter();
        $tl = $this->calibre->authorDetailsSlice(
            $this->l10n->user_lang,
            $id,
            $index,
            $this->config[AppConstants::PAGE_SIZE],
            $filter);
        if (empty($tl)) {
            $msg = sprintf("ViewAuthorAction: no title data found for id %d", $id);
            $this->logger->error($msg);
            throw new DomainRecordNotFoundException($msg);
        }

        $books = array_map(array($this, 'checkThumbnail'), $tl['entries']);

        $series = $this->calibre->authorSeries($id, $books);

        $author = $tl['author'];
        $author->thumbnail = $this->bbs->getAuthorThumbnail($id);
        $author->links = $this->bbs->authorLinks($id);
        return $this->respondWithPage('author_detail.twig', array(
            'page' => $this->mkPage($this->getMessageString('author_details'), 3, 2),
            'url' => 'authors/' . $id,
            'author' => $tl['author'],
            'books' => $books,
            'series' => $series,
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'flash' => $flash,
            'isadmin' => $this->is_admin()));
    }
}