<?php

namespace BicBucStriim;

use Aura\Auth\Session\SessionInterface;
use Aura\Session\Session as AuraSession;

/**
 *
 * Session that integrates Aura Auth and Session.
 *
 */
class Session extends AuraSession implements SessionInterface
{
}
