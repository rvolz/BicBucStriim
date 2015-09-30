<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Session;

/**
 *
 * Cross-site request forgery token tools.
 *
 * @package Aura.Session
 *
 */
class CsrfToken
{
    /**
     *
     * A cryptographically-secure random value generator.
     *
     * @var RandvalInterface
     *
     */
    protected $randval;

    /**
     *
     * Session segment for values in this class.
     *
     * @var Segment
     *
     */
    protected $segment;

    /**
     *
     * Constructor.
     *
     * @param Segment $segment A segment for values in this class.
     *
     * @param RandvalInterface $randval A cryptographically-secure random
     * value generator.
     *
     */
    public function __construct(Segment $segment, RandvalInterface $randval)
    {
        $this->segment = $segment;
        $this->randval = $randval;
        if (!$this->segment->get('value')) {
            $this->regenerateValue();
        }
    }

    /**
     *
     * Checks whether an incoming CSRF token value is valid.
     *
     * @param string $value The incoming token value.
     *
     * @return bool True if valid, false if not.
     *
     */
    public function isValid($value)
    {
        return $value === $this->getValue();
    }

    /**
     *
     * Gets the value of the outgoing CSRF token.
     *
     * @return string
     *
     */
    public function getValue()
    {
        return $this->segment->get('value');
    }

    /**
     *
     * Regenerates the value of the outgoing CSRF token.
     *
     * @return null
     *
     */
    public function regenerateValue()
    {
        $hash = hash('sha512', $this->randval->generate());
        $this->segment->set('value', $hash);
    }
}
