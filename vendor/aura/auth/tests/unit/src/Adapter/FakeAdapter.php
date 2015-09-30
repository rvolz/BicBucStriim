<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Auth;
use Aura\Auth\Exception;

class FakeAdapter extends AbstractAdapter
{
    protected $accounts = array();

    public function __construct(array $accounts = array())
    {
        $this->accounts = $accounts;
    }

    public function login(array $input)
    {
        return array($input['username'], array());
    }
}
