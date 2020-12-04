<?php

namespace App\Infrastructure\Session;


use phpDocumentor\Reflection\Types\String_;

/**
 * Class Token
 * @package BicBucStriim
 *
 * The contens of a JSON Web Token (JWT).
 */
class Token
{

    private $uid;
    private $iat;
    private $exp;
    private $sub;

    /**
     * @return mixed
     */
    public function getSub()
    {
        return $this->sub;
    }

    /**
     * @param mixed $sub
     */
    public function setSub($sub)
    {
        $this->sub = $sub;
    }

    /**
     * @return mixed
     */
    public function getJti()
    {
        return $this->jti;
    }

    /**
     * @param mixed $jti
     */
    public function setJti($jti)
    {
        $this->jti = $jti;
    }

    private $jti;


    /**
     * Hydate options from given object/array
     *
     * @param object $data Array of options.
     * @return self
     */
    public function hydrate($data = [])
    {
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func(array($this, $method), $value);
            }
        }
        return $this;
    }

    /**
     * Get the user id
     * @return integer
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set the user id
     * @param integer $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * Get the "issued at" timestamp
     * @return integer
     */
    public function getIat()
    {
        return $this->iat;
    }

    /**
     * Set the "issued at" timestamp
     * @param integer $iat
     */
    public function setIat($iat)
    {
        $this->iat = $iat;
    }

    /**
     * Get the "expires at" timestamp
     * @return integer
     */
    public function getExp()
    {
        return $this->exp;
    }

    /**
     * Set the "expires at" timestamp
     * @param integer $exp
     */
    public function setExp($exp)
    {
        $this->exp = $exp;
    }

}