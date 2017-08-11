<?php

namespace FreePbx\Ami;

class AmiSocket
{
    /**
     * @var resource
     */
    private $_socket;

    /**
     * @var array
     */
    private $_command = [];

    /**
     * @var bool
     */
    private $authenticated = false;

    /**
     * @var array
     */
    protected $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Set an AMI Action
     * @param $action
     * @param array $params
     * @return $this
     */
    public function action($action, array $params = [])
    {
        $this->_command[] = array_merge(['Action' => $action], $params);

        return $this;
    }

    /**
     * @return $this
     */
    protected function login()
    {
        $username = $this->config['username'];
        $password = $this->config['password'];

        fwrite($this->_socket, "Action: Login\r\nUserName: $username\r\nSecret: $password\r\n\r\n");

        $this->authenticated = true;

        return $this;
    }

    /**
     * Open AMI connection
     * @return $this
     */
    protected function open()
    {
        // Make sure it's not a current resource
        if (! is_resource($this->_socket)) {
            $this->_socket = fsockopen($this->config['host'], 5038, $errno, $errstr, 300);
        }

        return $this;
    }

    /**
     * @return resource|null
     */
    public function getSocket()
    {
        return $this->_socket;
    }

    /**
     * @return $this
     */
    public function close()
    {
        fclose($this->_socket);

        return $this;
    }

    /**
     * @return string
     */
    public function execute()
    {
        return $this->runCommand()->getOutput();
    }

    /**
     * @return $this
     */
    public function logoff()
    {
        if ($this->isAuthenticated()) {
            fwrite($this->_socket, "Action: Logoff\r\n\r\n");
            $this->authenticated = false;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        $this->logoff();
        $buff = [];
        do {
            $buff[] = trim(fgets($this->_socket, 4096));
            $info = stream_get_meta_data($this->_socket);
        } while (! $info['timed_out'] && ! $info['eof']);
        // Close the connection because you cant log back in
        $this->close();

        return implode("\n", $buff);
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * Run command
     * @return $this
     */
    public function runCommand()
    {
        if (! $this->isAuthenticated()) {
            $this->open()->login();
        }

        foreach ($this->_command as $action) {
            $cmd = '';
            foreach ($action as $key => $value) {
                $key = ucfirst($key);
                if (is_array($value)) {
                    foreach ($value as $var => $val) {
                        $cmd .= "$key: $var=$val\r\n";
                    }
                } else {
                    $cmd .= "$key: $value\r\n";
                }
            }
            fwrite($this->_socket, "$cmd\r\n");
        }

        $this->_command = [];

        return $this;
    }

    /**
     * @return array
     */
    public function getCommand()
    {
        return $this->_command;
    }
}
