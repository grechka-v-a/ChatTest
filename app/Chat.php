<?php
namespace ChatApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Redis;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $redis;
    protected $history;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "Server is running" . "\n";
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
        if($this->redis->ping()) {
            echo "Redis is running" . "\n";
        }else{
            echo "Redis is down" . "\n";
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $this->redis->lpush("chat", $msg);

        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}