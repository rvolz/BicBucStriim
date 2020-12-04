<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace App\Domain\BicBucStriim;

use Exception;
use PDO;
use RedBeanPHP\OODBBean;
use RedBeanPHP\R;
use Utilities;

class BicBucStriim implements BicBucStriimRepository
{
    # bbs sqlite db
    var ?PDO $mydb = NULL;
    # calibre library dir
    var string $calibre_dir = '';
    # calibre library file, last modified date
    var $calibre_last_modified;
    # last sqlite error
    var $last_error = 0;
    # dir for bbs db
    var string $data_dir = '';
    # dir for generated title thumbs
    var string $thumb_dir = '';
    # dir for generated title thumbs
    var string $authors_dir = '';

    /**
     * Try to open the BBS DB. If the DB file does not exist we do nothing.
     * Creates also the subdirectories for thumbnails etc. if they don't exist.
     *
     * We open it first as PDO, because we need that for the
     * authentication library, then we initialize RedBean.
     *
     * @param string  	$dataPath 	Path to BBS DB, default = data/data.db
     * @param boolean	$freeze 	if true the DB schema is fixed,
     * 								else RedBeanPHP adapt the schema
     * 								default = true
     */
    function __construct($dataPath = '../../data/data.db', $freeze = true)
    {
        $rp = realpath($dataPath);
        $this->data_dir = dirname($rp);
        $this->thumb_dir = $this->data_dir . '/thumbnails/titles';
        $this->authors_dir = $this->data_dir . '/thumbnails/authors';
        if (file_exists($rp) && is_writeable($rp)) {
            $this->mydb = new PDO('sqlite:' . $rp, NULL, NULL, array());
            $this->mydb->setAttribute(1002, 'SET NAMES utf8');
            $this->mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->mydb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->last_error = $this->mydb->errorCode();
            if (!R::hasDatabase('default')) {
                R::setup('sqlite:' . $rp);
            }
            R::setAutoResolve(true);
            //R::getRedBean()->setBeanHelper(new BbsBeanHelper());
            R::freeze($freeze);
            if (!file_exists($this->thumb_dir))
                mkdir($this->thumb_dir, 0777, true);
            if (!file_exists($this->authors_dir))
                mkdir($this->authors_dir, 0777, true);
        } else {
            $this->mydb = NULL;
        }
    }

    public function createDataDb($dataPath = 'data/data.db')
    {
        $schema = file(dirname($dataPath) . '/schema.sql');
        $this->mydb = new PDO('sqlite:' . $dataPath, NULL, NULL, array());
        $this->mydb->setAttribute(1002, 'SET NAMES utf8');
        $this->mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->mydb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        for ($i = 0; $i < count($schema); $i++) {
            if (strpos($schema[$i], '--') == false)
                $this->mydb->exec($schema[$i]);
        }
        $mdp = password_hash('admin', PASSWORD_BCRYPT);
        $this->mydb->exec('insert into user (username, password, role) values ("admin", "' . $mdp . '",1)');
        $this->mydb->exec('insert into config (name, val) values ("db_version", "3")');
        $this->mydb = null;
    }


    public function dbOk(): bool
    {
        return (!is_null($this->mydb));
    }


    var array $globalSettings = array();

    public function initSettings(): array
    {
        # Init admin settings with std values, for upgrades or db errors
        $globalSettings[AppConstants::CALIBRE_DIR] = '';
        $globalSettings[AppConstants::DB_VERSION] = AppConstants::DB_SCHEMA_VERSION;
        $globalSettings[AppConstants::KINDLE] = 0;
        $globalSettings[AppConstants::KINDLE_FROM_EMAIL] = '';
        $globalSettings[AppConstants::THUMB_GEN_CLIPPED] = 1;
        $globalSettings[AppConstants::PAGE_SIZE] = 30;
        // TODO appname
        // $globalSettings[AppConstants::DISPLAY_APP_NAME] = $appname;
        $globalSettings[AppConstants::SMTP_USER] = '';
        $globalSettings[AppConstants::SMTP_PASSWORD] = '';
        $globalSettings[AppConstants::SMTP_SERVER] = '';
        $globalSettings[AppConstants::SMTP_PORT] = 25;
        $globalSettings[AppConstants::SMTP_ENCRYPTION] = 0;
        $globalSettings[AppConstants::METADATA_UPDATE] = 0;
        $globalSettings[AppConstants::TITLE_TIME_SORT] = AppConstants::TITLE_TIME_SORT_TIMESTAMP;
        $globalSettings[AppConstants::RELATIVE_URLS] = 1;
        return $globalSettings;
    }

    var array $knownConfigs = array(
        AppConstants::CALIBRE_DIR,
        AppConstants::DB_VERSION,
        AppConstants::KINDLE,
        AppConstants::KINDLE_FROM_EMAIL,
        AppConstants::THUMB_GEN_CLIPPED,
        AppConstants::PAGE_SIZE,
        AppConstants::DISPLAY_APP_NAME,
        AppConstants::MAILER,
        AppConstants::SMTP_SERVER,
        AppConstants::SMTP_PORT,
        AppConstants::SMTP_USER,
        AppConstants::SMTP_PASSWORD,
        AppConstants::SMTP_ENCRYPTION,
        AppConstants::METADATA_UPDATE,
        AppConstants::LOGIN_REQUIRED,
        AppConstants::TITLE_TIME_SORT,
        AppConstants::RELATIVE_URLS
    );


    public function configs(): array
    {
        $this->initSettings();
        return R::findAll('config');
    }

    public function config(string $name)
    {
        return R::findOne('config', ' name = :name', array(':name' => $name));
    }

    public function saveConfigs(array $configs)
    {
        foreach ($configs as $name => $val) {
            $config = $this->config($name);
            if (is_null($config)) {
                $config = R::dispense('config');
                $config->name = $name;
                $config->val = $val;
            } else {
                $config->val = $val;
            }
            if ($config->getMeta('tainted'))
                R::store($config);
        }
    }

    public function users(): array
    {
        return R::findAll('user');
    }

    public function user($userid): ?OODBBean
    {
        $user = R::load('user', $userid);
        if (!$user->id)
            return null;
        else
            return $user;
    }

    public function userByName(string $username)
    {
        $user = R::findOne('user', ' username = :name', array(':name' => $username));
        return $user;
    }

    public function addUser(string $username, string $password)
    {
        if (empty($username) || empty($password))
            return null;
        $other = R::findOne('user', ' username = :name', array(':name' => $username));
        if (!is_null($other))
            return null;
        $mdp = password_hash($password, PASSWORD_BCRYPT);
        $user = R::dispense('user');
        $user->username = $username;
        $user->password = $mdp;
        $user->tags = null;
        $user->languages = null;
        $user->role = 0;
        $id = R::store($user);
        return $user;
    }

    public function deleteUser($userid): bool
    {
        if ($userid == 1)
            return false;
        else {
            $user = R::load('user', $userid);
            if (!$user->id)
                return false;
            else {
                R::trash($user);
                return true;
            }
        }
    }

    public function changeUser($userid, $password, $languages, $tags, $role)
    {
        $user = $this->user($userid);
        if (is_null($user))
            return null;
        if (empty($password))
            return null;
        else {
            $mdp = password_hash($password, PASSWORD_BCRYPT);
            if ($password != $user->password)
                $user->password = $mdp;
            $user->languages = $languages;
            $user->tags = $tags;
            if (strcasecmp($role, "admin") == 0)
                $user->role = "1";
            else
                $user->role = "0";
            try {
                $id = R::store($user);
                return $user;
            } catch (Exception $e) {
                return null;
            }
        }
    }

    public function idTemplates(): array
    {
        return R::findAll('idtemplate', ' order by name');
    }

    public function idTemplate($name)
    {
        return R::findOne('idtemplate', ' name = :name', array(':name' => $name));
    }

    public function addIdTemplate($name, $value, $label)
    {
        $template = R::dispense('idtemplate');
        $template->name = $name;
        $template->val = $value;
        $template->label = $label;
        $id = R::store($template);
        return $template;
    }

    public function deleteIdTemplate($name): bool
    {
        $template = $this->idtemplate($name);
        if (!is_null($template)) {
            R::trash($template);
            return true;
        } else
            return false;
    }

    public function changeIdTemplate($name, $value, $label)
    {
        $template = $this->idtemplate($name);
        if (!is_null($template)) {
            $template->val = $value;
            $template->label = $label;
            try {
                $id = R::store($template);
                return $template;
            } catch (Exception $e) {
                return null;
            }
        } else {
            return null;
        }
    }

    public function getCalibreThing($calibreType, $calibreId): OODBBean
    {
        return R::findOne('calibrething',
            ' ctype = :type and cid = :id',
            array(
                ':type' => $calibreType,
                'id' => $calibreId
            )
        );
    }

    public function addCalibreThing($calibreType, $calibreId, $calibreName)
    {
        $calibreThing = R::dispense('calibrething');
        $calibreThing->ctype = $calibreType;
        $calibreThing->cid = $calibreId;
        $calibreThing->cname = $calibreName;
        $calibreThing->ownArtefact = array();
        $calibreThing->refctr = 0;
        $id = R::store($calibreThing);
        return $calibreThing;
    }

    public function deleteAuthorThumbnail($authorId): bool
    {
        $ret = true;
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (!is_null($calibreThing)) {
            $artefact = $this->getFirstArtefact($calibreThing);
            if (!is_null($artefact)) {
                $ret = unlink($artefact->url);
                unset($calibreThing->ownArtefact[$artefact->id]);
                $calibreThing->refctr -= 1;
                R::trash($artefact);
                if ($calibreThing->refctr == 0)
                    R::trash($calibreThing);
                else
                    R::store($calibreThing);
            }
        }
        return $ret;
    }

    public function getFirstArtefact($calibreThing): ?OODBBean
    {
        $artefacts = array_values(array_filter($calibreThing->ownArtefact, function ($artefact) {
            return ($artefact->atype == DataConstants::AUTHOR_THUMBNAIL_ARTEFACT);
        }));
        if (empty($artefacts))
            return null;
        else
            return $artefacts[0];
    }

    public function getAuthorThumbnail($authorId): ?OODBBean
    {
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing)) {
            return null;
        } else {
            return $this->getFirstArtefact($calibreThing);
        }
    }

    public function editAuthorThumbnail($authorId, $authorName, $clipped, $file, $mime)
    {
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing))
            $calibreThing = $this->addCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId, $authorName);

        if (($mime == 'image/jpeg')
            || ($mime == "image/jpg")
            || ($mime == "image/pjpeg"))
            $png = false;
        else
            $png = true;

        $fname = $this->authors_dir . '/author_' . $calibreThing->id . '_thm.png';
        if (file_exists($fname))
            unlink($fname);

        if ($clipped)
            $created = $this->thumbnailClipped($file, $png, self::THUMB_RES, self::THUMB_RES, $fname);
        else
            $created = $this->thumbnailStuffed($file, $png, self::THUMB_RES, self::THUMB_RES, $fname);

        $artefact = $this->getFirstArtefact($calibreThing);
        if (is_null($artefact)) {
            $artefact = R::dispense('artefact');
            $artefact->atype = DataConstants::AUTHOR_THUMBNAIL_ARTEFACT;
            $artefact->url = $fname;
            $calibreThing->ownArtefact[] = $artefact;
            $calibreThing->refctr += 1;
            R::store($calibreThing);
        }
        return $created;
    }

    private function mk_thumb_path($id): string
    {
        $thumb_name = 'thumb_' . $id . '.png';
        return $this->thumb_dir . '/' . $thumb_name;
    }

    public function getExistingTitleThumbnail($id)
    {
        return $this->mk_thumb_path($id);
    }

    public function isTitleThumbnailAvailable($id): bool
    {
        $thumb_name = 'thumb_' . $id . '.png';
        $thumb_path = $this->thumb_dir . '/' . $thumb_name;
        return file_exists($thumb_path);
    }

    public function titleThumbnail($id, $cover, $clipped): string
    {
        $thumb_name = 'thumb_' . $id . '.png';
        $thumb_path = $this->thumb_dir . '/' . $thumb_name;
        if (!file_exists($thumb_path)) {
            if (is_null($cover))
                $thumb_path = NULL;
            else {
                if ($clipped)
                    $created = $this->thumbnailClipped($cover, false, self::THUMB_RES, self::THUMB_RES, $thumb_path);
                else
                    $created = $this->thumbnailStuffed($cover, false, self::THUMB_RES, self::THUMB_RES, $thumb_path);
                if (!$created)
                    $thumb_path = NULL;
            }
        }
        return $thumb_path;
    }

    public function clearThumbnails(): bool
    {
        $cleared = true;
        if ($dh = opendir($this->thumb_dir)) {
            while (($file = readdir($dh)) !== false) {
                $fn = $this->thumb_dir . '/' . $file;
                if (preg_match("/^thumb.*\\.png$/", $file) && file_exists($fn)) {
                    if (!@unlink($fn)) {
                        $cleared = false;
                        break;
                    }
                }
            }
            closedir($dh);
        } else
            $cleared = false;
        return $cleared;
    }

    public function getLinks($calibreThing): array
    {
        return array_values(array_filter($calibreThing->ownLink, function ($link) {
            return ($link->ltype == DataConstants::AUTHOR_LINK);
        }));
    }

    public function authorLinks($authorId): array
    {
        $links = array();
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (!is_null($calibreThing)) {
            $links = $this->getLinks($calibreThing);
        }
        return $links;
    }

    public function addAuthorLink($authorId, $authorName, $label, $url)
    {
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing))
            $calibreThing = $this->addCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId, $authorName);
        $link = R::dispense('link');
        $link->ltype = DataConstants::AUTHOR_LINK;
        $link->label = $label;
        $link->url = $url;
        $calibreThing->ownLink[] = $link;
        $calibreThing->refctr += 1;
        R::store($calibreThing);
        return $link;
    }

    public function deleteAuthorLink($authorId, $linkId): bool
    {
        $ret = false;
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (!is_null($calibreThing)) {
            try {
                $link = $calibreThing->ownLink[$linkId];
            } catch (Exception $e) {
                $link = null;
            }
            if (!is_null($link)) {
                unset($calibreThing->ownLink[$link->id]);
                R::trash($link);
                $calibreThing->refctr -= 1;
                if ($calibreThing->refctr == 0)
                    R::trash($calibreThing);
                else
                    R::store($calibreThing);
                $ret = true;
            }
        }
        return $ret;
    }

    public function getFirstNote($calibreThing): ?OODBBean
    {
        $notes = array_values(array_filter($calibreThing->ownNote, function ($note) {
            return ($note->ntype == DataConstants::AUTHOR_NOTE);
        }));
        if (empty($notes))
            return null;
        else
            return $notes[0];
    }

    public function authorNote($authorId): ?OODBBean
    {
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing)) {
            return null;
        } else {
            return $this->getFirstNote($calibreThing);
        }
    }

    public function editAuthorNote($authorId, $authorName, $mime, $noteText)
    {
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (is_null($calibreThing))
            $calibreThing = $this->addCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId, $authorName);
        $note = $this->getFirstNote($calibreThing);
        if (is_null($note)) {
            $note = R::dispense('note');
            $note->ntype = DataConstants::AUTHOR_NOTE;
            $note->mime = $mime;
            $note->ntext = $noteText;
            $calibreThing->ownNote[] = $note;
            $calibreThing->refctr += 1;
            R::store($calibreThing);
        } else {
            $note->mime = $mime;
            $note->ntext = $noteText;
            R::store($note);
        }
        return $note;
    }

    public function deleteAuthorNote($authorId): bool
    {
        $ret = false;
        $calibreThing = $this->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, $authorId);
        if (!is_null($calibreThing)) {
            $note = $this->getFirstNote($calibreThing);
            if (!is_null($note)) {
                unset($calibreThing->ownNote[$note->id]);
                $calibreThing->refctr -= 1;
                R::trash($note);
                if ($calibreThing->refctr == 0)
                    R::trash($calibreThing);
                else
                    R::store($calibreThing);
            }
            $ret = true;
        }
        return $ret;
    }


    ############################### internal stuff ##############################################

    /**
     * Create a square thumbnail by clipping the largest possible square from the cover
     * @param  string    cover        path to input image
     * @param  bool    png        true if the input is a PNG file, false = JPEG
     * @param  int        newwidth    required thumbnail width
     * @param  int        newheight    required thumbnail height
     * @param  string    thumb_path    path for thumbnail storage
     * @return bool                true = thumbnail created, else false
     */
    private function thumbnailClipped($cover, $png, $newwidth, $newheight, $thumb_path): bool
    {
        list($width, $height) = getimagesize($cover);
        $thumb = imagecreatetruecolor($newwidth, $newheight);
        if ($png)
            $source = imagecreatefrompng($cover);
        else
            $source = imagecreatefromjpeg($cover);
        $minwh = min(array($width, $height));
        $newx = ($width / 2) - ($minwh / 2);
        $newy = ($height / 2) - ($minwh / 2);
        $inbetween = imagecreatetruecolor($minwh, $minwh);
        imagecopy($inbetween, $source, 0, 0, $newx, $newy, $minwh, $minwh);
        imagecopyresized($thumb, $inbetween, 0, 0, 0, 0, $newwidth, $newheight, $minwh, $minwh);
        $created = imagepng($thumb, $thumb_path);
        return $created;
    }

    /**
     * Create a square thumbnail by stuffing the cover at the edges
     * @param  string    cover        path to input image
     * @param  bool    png        true if the input is a PNG file, false = JPEG
     * @param  int        newwidth    required thumbnail width
     * @param  int        newheight    required thumbnail height
     * @param  string    thumb_path    path for thumbnail storage
     * @return bool                true = thumbnail created, else false
     */
    private function thumbnailStuffed($cover, $png, $newwidth, $newheight, $thumb_path): bool
    {
        list($width, $height) = getimagesize($cover);
        $thumb = $this->transparentImage($newwidth, $newheight);
        if ($png)
            $source = imagecreatefrompng($cover);
        else
            $source = imagecreatefromjpeg($cover);
        $dstx = 0;
        $dsty = 0;
        $maxwh = max(array($width, $height));
        if ($height > $width) {
            $diff = $maxwh - $width;
            $dstx = (int)$diff / 2;
        } else {
            $diff = $maxwh - $height;
            $dsty = (int)$diff / 2;
        }
        $inbetween = $this->transparentImage($maxwh, $maxwh);
        imagecopy($inbetween, $source, $dstx, $dsty, 0, 0, $width, $height);
        imagecopyresampled($thumb, $inbetween, 0, 0, 0, 0, $newwidth, $newheight, $maxwh, $maxwh);
        $created = imagepng($thumb, $thumb_path);
        imagedestroy($thumb);
        imagedestroy($inbetween);
        imagedestroy($source);
        return $created;
    }

    /**
     * Create an image with transparent background.
     *
     * see http://stackoverflow.com/questions/279236/how-do-i-resize-pngs-with-transparency-in-php#279310
     *
     * @param int $width
     * @param int $height
     * @return resource
     */
    private function transparentImage(int $width, int $height)
    {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $backgr = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefilledrectangle($img, 0, 0, $width, $height, $backgr);
        return $img;
    }
}
