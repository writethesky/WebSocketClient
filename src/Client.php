<?php
namespace writethesky\WebSocketClient;

use Sabre\Uri;

class Client
{
    const WEBSOCKET_VERSION = 13;
    const USER_AGENT = "writethesky-WebsocketClient";
    const VERSIONT = "1.0.1";
    private $timeout;
    private $uri;
    private $host;
    private $port;
    private $client;
    private $origin = "file://";
    private $sourceKey;
    private $connected = false;
    public function __construct($uri, $timeout)
    {
        $uriObj = Uri\parse($uri);
        $this->timeout = $timeout;
        $this->uri = $uri;
        $this->host = $uriObj['host'];
        $this->port = $uriObj['port'] ?? ($uriObj['scheme'] == 'wss' ? 443: 80);

    }

    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }

    public function connect()
    {
        $this->connected = false;

        $this->client = new \Co\Client(SWOOLE_SOCK_TCP);
        if(!$this->client->connect($this->host, $this->port, $this->timeout)){
            return false;
        }

        $headers = $this->getHeaders();
        if(!$this->client->send($headers)){
            return false;
        }

        $result = $this->client->recv();
        if(!$result){
            return false;
        }

        $response = $this->responseFormat($result);
        if(!$response){
            return false;
        }

        if(!$this->responseCheck($response)){
            // TODO 断开连接
            return false;
        }

        $this->connected = true;
        return true;

    }

    public function send(Frame $frame)
    {
        $this->client->send($frame->getFrame());


    }

    public function on($event, $callbak)
    {
        $event = 'on' . ucfirst($event);
        $this->$event = $callbak;

        go(function(){
            while (true) {
                \co::sleep(0.1);
                if(!$this->connected){
                    continue;
                }
                $result = $this->client->recv();
                $onMessage = $this->onMessage;
                $onMessage(Frame::decode($result));
            }
        });

    }

    private function responseCheck($response)
    {
        if(!array_key_exists("Sec-WebSocket-Accept", $response['headers'])){
            return false;
        }
        $accept = $response['headers']["Sec-WebSocket-Accept"];

        $acceptReal = base64_encode(sha1($this->sourceKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        if($accept != $acceptReal){
            return false;
        }

        return true;
    }

    private function responseFormat($result)
    {
        $header = current(explode("\r\n\r\n", $result));

        $arr = explode("\r\n", $header);
        $http = array_shift($arr);
        $http_arr = explode(" ", $http);
        if(3 > count($http_arr)){
            return false;
        }

        $return = [];
        $return['headers'] = [];
        $return['protocols'] = $http_arr[0];
        $return['statusCode'] = $http_arr[1];

        foreach ($arr as $key => $value) {
            $tmp = explode(": ", $value);
            $return['headers'][$tmp[0]] = $tmp[1];
        }
        return $return;
    }

    private function getHeaders()
    {
        $key = $this->getWebSocketKey();
        return
            "GET {$this->uri} HTTP/1.1\r\n".
            "Origin: {$this->origin}\r\n".
            "Host: {$this->host}\r\n".
            "User-Agent: " . self::USER_AGENT . "/" . self::VERSIONT . "\r\n".
            "Upgrade: Websocket" . "\r\n" .
            "Connection: Upgrade" . "\r\n" .
            "Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits\r\n" .
            "Sec-WebSocket-Key: {$key}\r\n" .
            "Sec-WebSocket-Version: " . self::WEBSOCKET_VERSION . "\r\n" . "\r\n";

    }

    private function getWebSocketKey()
    {
        $this->sourceKey = base64_encode(sha1(mt_rand(1000000000000000, 9999999999999999), true));
        return $this->sourceKey;
    }
}
