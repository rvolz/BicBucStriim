<?php
namespace Aura\Session;

// a session handler that does nothing, for testing purposes only
class FakeSessionHandler
{
    public $data;

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        $this->data = null;
        return true;
    }

    public function gc($maxlifetime)
    {
        return true;
    }

    public function open($save_path, $session_id)
    {
        return true;
    }

    public function read($session_id)
    {
        return $this->data;
    }

    public function write($session_id, $session_data)
    {
        $this->data = $session_data;
        return true;
    }
}
