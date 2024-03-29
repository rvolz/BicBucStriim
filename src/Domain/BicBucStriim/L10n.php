<?php

declare(strict_types=1);

namespace App\Domain\BicBucStriim;

use ArrayObject;

class L10n extends ArrayObject
{
    /**
     * User language
     * @var string
     */
    public string $user_lang;
    /**
     * Messages of primary localization language
     * @var array
     */
    public array $langa;
    /**
     * Messages of fallback localization language
     * @var array
     */
    public array $langb;

    /**
     * Find the user language, either one of the allowed languages or
     * English as a fallback. Store the English messages as an alternative
     * for incomplete translations.
     *
     * @param string $lang user language (according to client)
     */
    public function __construct($lang='en')
    {
        parent::__construct();
        $this->loadLanguage($lang);
    }

    public function loadLanguage(string $lang)
    {
        global $langde, $langen, $langes, $langfr, $langgl, $langhu, $langit, $langnl;
        include("langs.php");
        if ($lang == 'de') {
            $this->langa = $langde;
        } elseif ($lang == 'es') {
            $this->langa = $langes;
        } elseif ($lang == 'fr') {
            $this->langa = $langfr;
        } elseif ($lang == 'gl') {
            $this->langa = $langgl;
        } elseif ($lang == 'hu') {
            $this->langa = $langhu;
        } elseif ($lang == 'it') {
            $this->langa = $langit;
        } elseif ($lang == 'nl') {
            $this->langa = $langnl;
        } else {
            $this->langa = $langen;
        }
        $this->langb = $langen;
        $this->user_lang = $lang;
    }

    /**
     * Implement this part of the Array interface for easier
     * access by the templates.
     *
     * Always return true, because then ::message will return an
     * error string for undefined IDs.
     * @param $id
     * @return bool
     */
    public function offsetExists($id): bool
    {
        return true;
    }

    /**
     * Implement this part of the Array interface for easier
     * access by the templates.
     *
     * Just call ::message.
     * @param $id
     * @return string
     */
    public function offsetGet($id): string
    {
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
    public function message($id)
    {
        if (empty($id)) {
            return '';
        }
        if (array_key_exists($id, $this->langa)) {
            return $this->langa[$id];
        } else {
            if (array_key_exists($id, $this->langb)) {
                return $this->langb[$id];
            } else {
                return 'Undefined message!';
            }
        }
    }
}
