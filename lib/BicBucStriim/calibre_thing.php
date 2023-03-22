<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

class Model_CalibreThing extends RedBean_SimpleModel
{
    public $ownLink;
    public $ownNote;
    public $ownArtefact;

    /**
     * Return author links releated to this Calibre entitiy.
     * @return array 	all available author links
     */
    public function getAuthorLinks()
    {
        return array_values(array_filter($this->ownLink, function ($link) {
            return($link->ltype == DataConstants::AUTHOR_LINK);
        }));
    }

    /**
     * Return the author note text related to this Calibre entitiy.
     * @return ?RedBean_SimpleModel 	text or null
     */
    public function getAuthorNote()
    {
        $notes = array_values(array_filter($this->ownNote, function ($note) {
            return($note->ntype == DataConstants::AUTHOR_NOTE);
        }));
        if (empty($notes)) {
            return null;
        } else {
            return $notes[0];
        }
    }

    /**
     * Return the author thumbnail file related to this Calibre entitiy.
     * @return ?RedBean_SimpleModel 	Path to thumbnail file or null
     */
    public function getAuthorThumbnail()
    {
        $artefacts = array_values(array_filter($this->ownArtefact, function ($artefact) {
            return($artefact->atype == DataConstants::AUTHOR_THUMBNAIL_ARTEFACT);
        }));
        if (empty($artefacts)) {
            return null;
        } else {
            return $artefacts[0];
        }
    }
}
