<?php
namespace demo;
require 'vendor/autoload.php';

use writethesky\WebSocketClient\Client;
use writethesky\WebSocketClient\Frame;



// 需要在协程内
go(function(){
    // 创建连接客户端，uri，超时（0，永不超时）
    $client = new Client("ws://message.xxx.com/websocket", 0);


    // 消息处理回调函数
    $onMessage = function($frame) use ($client)
    {
        // 输出消息内容
        var_dump($frame->getPayload());

    };

    // 绑定消息事件（会开启一个协程, 触发消息回调函数）
    $client->on('message', $onMessage);

    // 连接
    $client->connect();

    // 发送文本类型数据
    $data = '{"appKey":"aaa","costInIsv":0,"pubTime":1569466912760,"sign":"7F23945DDC29B1B3B89B0AF2CE3521A1","type":"CONNECT"}';
    // 数据，类型（text、bin、ping、pong、close），是否掩码处理
    $frame = new Frame($data, "text", true);
    $client->send($frame);

});
