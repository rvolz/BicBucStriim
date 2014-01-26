<?php
/**
* Localization helper 
*/
require_once 'langs.php';

class L10n extends ArrayObject {
	/**
	 * User language
	 * @var string
	 */
	var $user_lang;
	/**
	 * Messages of primary localization language
	 * @var array
	 */
	var $langa;
	/**
	 * Messages of fallback localization language
	 * @var array
	 */
	var $langb;

	/**
	 * Find the user language, either one of the allowed languages or 
	 * English as a fallback. Store the English messages as an alternative 
	 * for incomplete translations.
	 * 
	 * @param string $lang  user language (according to client)
	 */
	function __construct($lang) {		
		global $langde, $langen, $langfr, $langit, $langnl;
		if ($lang == 'de')
			$this->langa = $langde;
		elseif ($lang == 'fr')
			$this->langa = $langfr;
		elseif ($lang == 'it')
			$this->langa = $langit;
		elseif ($lang == 'nl')
			$this->langa = $langnl;
		else
			$this->langa = $langen;
		$this->langb = $langen;
		$this->user_lang = $lang;
	}


	/**
	 * Implement this part of the Array interface for easier 
	 * access by the templates.
	 *
	 * Always return true, because then ::message will return an
	 * error string for undefined IDs.
	 */
	public function offsetExists($id) {
		return true;
	}

	/**
	 * Implement this part of the Array interface for easier 
	 * access by the templates.
	 *
	 * Just call ::message.
	 */
	public function offsetGet($id) {
		return $this->message($id);
	}

	/**
	 * Return a localized message string for $id. 
	 *
	 * If there is no defined message for $id in the current language the function
	 * looks for an alterantive in English. If that also fails an error message 
	 * is returned.
	 *
	 * If $id is NULL or '' the empty string will be returned.
	 * 
	 * @param  string $id message id
	 * @return string     localized message string
	 */
	function message($id) {
		if (empty($id)) return '';
		if (array_key_exists($id, $this->langa))
			return $this->langa[$id];
		else {
			if (array_key_exists($id, $this->langb))
				return $this->langb[$id];
			else
				return 'Undefined message!';
		} 
	}

}
?>
