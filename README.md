# websocket client websocket客户端

基于swoole4实现的协程版websocket客户端，直接使用httpclient发送http请求，然后再通过upgrade的方式发送websocket请求，在一些场景下会出现握手成功，一发送消息就被断开连接的情况，本库基于TCP，按照websocket协议标准实现，可避免此类问题的出现

## 特性

- 基于swoole4实现
- 协程
- 基于TCP实现了标准的websocket协议
- 支持消息事件绑定


## 代码示例

```
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
```

## 类说明

### Client

- new Client(string $uri, float $timeout)
  -- uri 连接地址
  -- timeout 超时时间，单位秒，0，永不超时
- connect() 连接
- send(Frame $frame) 发送数据
  -- frame websocket帧
- setOrigin(string $origin) 设置来源
- on(string $event, function $callbak) 绑定事件
  -- event 事件名称，暂支持message，其他后续完善
  -- callbak 回调函数

### Frame
- new Frame(string $payload, string $type = 'text', bool $mask = true)
  -- payload 数据
  -- type 类型（text、bin、ping、pong、close）
  -- mask 是否掩码处理，如果服务端严格实现了websocket协议，客户端必须设置掩码
- new Frame() 创建帧对象的时候不初始化数据，后续手动设置，但是目前暂时未提供手动设置的方法，不建议采用这种方式创建对象
- Frame::decode($response) 解码websocket服务端返回的原始数据，返回Frame对象
- getPayload() 获取帧内数据
- getType() 获取帧类型
- getMask() 获取是否掩码
- getFrame() 获取帧内数据，如果mask为false，与Payload相同，否则为掩码处理后的Payload
