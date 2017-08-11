<?php

namespace FreePbx\Ami;

class AmiClient
{
    /**
     * @var AmiSocket
     */
    protected $socket;

    public function __construct(array $config = [])
    {
        $this->socket = new AmiSocket($config);
    }

    /**
     * @return string
     */
    public function showChannels()
    {
        return $this->socket->action('CoreShowChannels')->execute();
    }

    /**
     * @return string
     */
    public function queueStatus()
    {
        return $this->socket->action('QueueStatus')->execute();
    }

    /**
     * @param $family
     * @param $key
     * @return string
     */
    public function dbGet($family, $key)
    {
        $response = $this->action('DBGet', compact('family', 'key'))->getOutput();

        return preg_match('/Val:([^\n]+)/m', $response, $matches) ? trim($matches[1]) : '';
    }

    /**
     * @param $family
     * @param $key
     * @param $val
     * @return AmiClient
     */
    public function dbPut($family, $key, $val)
    {
        return $this->action('DBPut', compact('family', 'key', 'val'));
    }

    /**
     * @param $family
     * @param $key
     * @return AmiClient
     */
    public function dbDel($family, $key)
    {
        return $this->action('DBDel', compact('family', 'key'));
    }

    /**
     * @param $action
     * @param array $params
     * @return $this
     */
    public function action($action, array $params = [])
    {
        $this->socket->action($action, $params);

        return $this;
    }

    /**
     * @param $command
     * @return AmiClient
     */
    public function command($command)
    {
        return $this->action('Command', compact('command'));
    }

    /**
     * @return string
     */
    public function execute()
    {
        return $this->socket->execute();
    }

    /**
     * @return $this
     */
    public function runCommand()
    {
        return $this->socket->runCommand();
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->socket->runCommand()->getOutput();
    }

    /**
     * @return AmiSocket
     */
    public function getSocket()
    {
        return $this->socket;
    }
}
