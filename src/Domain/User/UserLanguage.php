<?php
declare(strict_types=1);

namespace App\Domain\User;

/**
 * Class UserLanguage
 * @package App\Domain\User
 *
 * Defines what languages are defined.
 */
class UserLanguage
{
    const DEFINED_LANGUAGES = array('de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl', 'pl');
    const FALLBACK_LANGUAGE = 'en';
}