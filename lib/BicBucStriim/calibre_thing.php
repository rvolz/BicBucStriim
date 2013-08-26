<?php

class Model_CalibreThing extends RedBean_SimpleModel {
	

	public function getAuthorThumbnail() {
		$artefacts = $this->ownArtefact;
		foreach ($artefacts as $id => $artefact) {
			if ($artefact->atype == DataConstants::AUTHOR_THUMBNAIL_ARTEFACT) {
				return $artefact;
			}
		}
		return null;
	}	
}

?>