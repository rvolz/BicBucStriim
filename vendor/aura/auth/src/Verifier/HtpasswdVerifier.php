<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Verifier;

/**
 *
 * Verfies passwords from htpasswd files; supports APR1/MD5, DES, SHA1, and
 * Bcrypt.
 *
 * The APR1/MD5 implementation was originally written by Mike Wallner
 * <mike@php.net>; any flaws are the fault of Paul M. Jones
 * <pmjones88@gmail.com>.
 *
 * @package Aura.Auth
 *
 */
class HtpasswdVerifier implements VerifierInterface
{
    /**
     *
     * Verifies a plaintext password against a hash.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue Comparison hash.
     *
     * @param array $extra Optional array if used by verify.
     *
     * @return bool
     *
     */
    public function verify($plaintext, $hashvalue, array $extra = array())
    {
        $hashvalue = trim($hashvalue);

        if (substr($hashvalue, 0, 4) == '$2y$') {
            return password_verify($plaintext, $hashvalue);
        }

        if (substr($hashvalue, 0, 5) == '{SHA}') {
            return $this->sha($plaintext, $hashvalue);
        }

        if (substr($hashvalue, 0, 6) == '$apr1$') {
            return $this->apr1($plaintext, $hashvalue);
        }

        return $this->des($plaintext, $hashvalue);
    }

    /**
     *
     * Verify using SHA1 hashing.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue Comparison hash.
     *
     * @return bool
     *
     */
    protected function sha($plaintext, $hashvalue)
    {
        $hex = sha1($plaintext, true);
        $computed_hash = '{SHA}' . base64_encode($hex);
        return $computed_hash === $hashvalue;
    }

    /**
     *
     * Verify using APR compatible MD5 hashing.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue Comparison hash.
     *
     * @return bool
     *
     */
    protected function apr1($plaintext, $hashvalue)
    {
        $salt = preg_replace('/^\$apr1\$([^$]+)\$.*/', '\\1', $hashvalue);
        $context = $this->computeContext($plaintext, $salt);
        $binary = $this->computeBinary($plaintext, $salt, $context);
        $p = $this->computeP($binary);
        $computed_hash = '$apr1$' . $salt . '$' . $p
                       . $this->convert64(ord($binary[11]), 3);
        return $computed_hash === $hashvalue;
    }

    /**
     *
     * Compute the context.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $salt The salt.
     *
     * @return string
     *
     */
    protected function computeContext($plaintext, $salt)
    {
        $length = strlen($plaintext);
        $hash = hash('md5', $plaintext . $salt . $plaintext, true);
        $context = $plaintext . '$apr1$' . $salt;

        for ($i = $length; $i > 0; $i -= 16) {
            $context .= substr($hash, 0, min(16, $i));
        }

        for ($i = $length; $i > 0; $i >>= 1) {
            $context .= ($i & 1) ? chr(0) : $plaintext[0];
        }

        return $context;
    }

    /**
     *
     * Compute the binary.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $salt The salt.
     *
     * @param string $context The context.
     *
     * @return string
     *
     */
    protected function computeBinary($plaintext, $salt, $context)
    {
        $binary = hash('md5', $context, true);
        for ($i = 0; $i < 1000; $i++) {
            $new = ($i & 1) ? $plaintext : $binary;
            if ($i % 3) {
                $new .= $salt;
            }
            if ($i % 7) {
                $new .= $plaintext;
            }
            $new .= ($i & 1) ? $binary : $plaintext;
            $binary = hash('md5', $new, true);
        }
        return $binary;
    }

    /**
     *
     * Compute the P value for a binary.
     *
     * @param string $binary The binary.
     *
     * @return string
     *
     */
    protected function computeP($binary)
    {
        $p = array();
        for ($i = 0; $i < 5; $i++) {
            $k = $i + 6;
            $j = $i + 12;
            if ($j == 16) {
                $j = 5;
            }
            $p[] = $this->convert64(
                (ord($binary[$i]) << 16) |
                (ord($binary[$k]) << 8) |
                (ord($binary[$j])),
                5
            );
        }
        return implode($p);
    }

    /**
     *
     * Convert to allowed 64 characters for encryption.
     *
     * @param string $value The value to convert.
     *
     * @param int $count The number of characters.
     *
     * @return string The converted value.
     *
     */
    protected function convert64($value, $count)
    {
        $charset = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $result = '';
        while (--$count) {
            $result .= $charset[$value & 0x3f];
            $value >>= 6;
        }
        return $result;
    }

    /**
     *
     * Verify using DES hashing.
     *
     * Note that crypt() will only check up to the first 8
     * characters of a password; chars after 8 are ignored. This
     * means that if the real password is "atecharsnine", the
     * word "atechars" would be valid.  This is bad.  As a
     * workaround, if the password provided by the user is
     * longer than 8 characters, this method will *not* verify
     * it.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue Comparison hash.
     *
     * @return bool
     *
     */
    protected function des($plaintext, $hashvalue)
    {
        if (strlen($plaintext) > 8) {
            return false;
        }

        $computed_hash = crypt($plaintext, $hashvalue);
        return $computed_hash === $hashvalue;
    }
}
