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
    public const DEFINED_LANGUAGES = ['de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl', 'pl'];
    public const FALLBACK_LANGUAGE = 'en';
}
