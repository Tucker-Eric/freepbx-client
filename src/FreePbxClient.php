<?php

namespace FreePbx;

use FreePbx\Ami\AmiClient;
use FreePbx\Ari\AriClient;
use FreePbx\RestApi\RestApiClient;

class FreePbxClient
{
    /**
     * @var AriClient
     */
    protected $ari;

    /**
     * @var AmiClient
     */
    protected $ami;

    /**
     * @var RestApiClient
     */
    protected $rest;

    public function __construct(array $config = [])
    {
        $this->ari = new AriClient([
            'host'     => $config['host'],
            'username' => $config['ari']['username'],
            'password' => $config['ari']['password']
        ]);

        $this->ami = new AmiClient([
            'host'     => $config['host'],
            'username' => $config['ami']['username'],
            'password' => $config['ami']['password']
        ]);

        $this->rest = new RestApiClient($config['rest']);

    }

    /**
     * @return AmiClient
     */
    public function getAmiClient()
    {
        return $this->ami;
    }

    /**
     * @return AriClient
     */
    public function getAriClient()
    {
        return $this->ari;
    }

    /**
     * @param array $routine
     * @return string
     */
    protected function runAmi(array $routine = [])
    {
        foreach ($routine as $method => $params) {
            method_exists($this->ami, $method) ? $this->ami->$method(...$params) : $this->ami->action($method, $params);
        }

        return $this->ami->execute();
    }

    /**
     * @param $ext
     * @param string|int|array $queue
     * @param int $priority
     */
    public function addExtToQueue($ext, $queue, $priority = 0)
    {
        $this->setExtQueuePenalty($ext, $queue, $priority);
        $currentQueues = $this->getExtQueues($ext);
        $this->setExtQueueHints($ext, array_merge($currentQueues, (array)$queue));
        $this->ami->execute();
    }

    /**
     * @param $ext
     * @return array
     */
    public function getExtQueueHints($ext)
    {
        return explode('&', trim($this->ami->dbGet('AMPUSER', "$ext/queuehint"), '&'));
    }

    /**
     * @param $ext
     * @return array
     */
    public function getExtQueues($ext)
    {
        preg_match_all('/\*(\d+)/m', $this->ami->dbGet('AMPUSER', "$ext/queuehint"), $matches);

        return isset($matches[1]) ? $matches[1] : [];
    }

    /**
     * @param $ext
     * @param string|array $queues
     */
    public function setExtQueueHints($ext, $queues = [])
    {
        $hints = [];
        foreach ((array)$queues as $queue) {
            $hints[] = $this->createQueueHint($ext, $queue);
        }

        $this->ami->dbPut('AMPUSER', "$ext/queuehint", implode('&', array_unique($hints)));
    }

    /**
     * @param $ext
     * @param $queues
     * @param int $priority
     * @return string
     */
    public function setExtQueuePenalty($ext, $queues, $priority = 0)
    {
        foreach ((array)$queues as $index => $queue) {
            $this->ami->dbPut('QPENALTY', "$queue/agents/$ext", $priority);
        }

        return $this;
    }

    /**
     * @param $ext
     * @param $penalty
     */
    public function updateUserPenalties($ext, $penalty)
    {
        // If it's not a number we can safely assume it's blank and set to 0
        if (! is_numeric($penalty)) {
            $penalty = 0;
        }
        $this->setExtQueuePenalty($ext, $this->getExtQueues($ext), $penalty);
        $this->ami->execute();
    }

    public function removeFromAllQueues($ext)
    {
        // Remove each queue individually
        foreach ($this->getExtQueues($ext) as $queue) {
            $this->ami->dbDel('QPENALTY', "$queue/agents/$ext");
        }
        // Remove all the hints
        $this->ami->dbDel('AMPUSER', "$ext/queuehint");

        return $this->ami->execute();
    }

    /**
     * Removes an agent from many queues
     * @param $ext
     * @param array $queues
     * @return string
     */
    public function removeFromQueues($ext, $queues = [])
    {
        if (empty($queues)) {
            return $this->removeFromAllQueues($ext);
        }

        $queues = (array)$queues;

        $currentQueues = $this->getExtQueues($ext);
        // Removes the user from the queue
        foreach ($queues as $queue) {
            $this->ami->dbDel('QPENALTY', "$queue/agents/$ext");
        }
        // These are the hints we are keeping
        $remainingQueues = array_diff($currentQueues, $queues);
        $this->setExtQueueHints($ext, $remainingQueues);
        $this->ami->execute();
    }

    /**
     * @param $ext
     * @param $queue
     * @return string
     */
    protected function createQueueHint($ext, $queue)
    {
        return "Custom:QUEUE$ext*$queue";
    }

    /**
     * @return array
     */
    public function getQueues()
    {
        return $this->rest->get('queues')->toArray();
    }

    /**
     * Originate a call
     * @param $from
     * @param $to
     */
    public function call($from, $to)
    {
        $this->ari->originate($from, $to);
    }

    /**
     * @param $ext
     * @return Ari\AriResponse
     */
    public function toggleLogin($ext)
    {
        return $this->ari->toggleLogin($ext);
    }

    /**
     * @param $ext
     * @return array
     */
    public function getExtPenalties($ext)
    {
        $resp = $this->ami->command('database show QPENALTY')->execute();

        preg_match_all('/\/QPENALTY\/(\d+)\/agents\/'.$ext.'\s+:\s?(\d+)/', $resp, $matches);

        return array_combine($matches[1], $matches[2]);
    }

    /**
     * @param $ext
     * @return bool
     */
    public function isOnline($ext)
    {
        return $this->ari->extensionStatus($ext) === 'online';
    }
}
