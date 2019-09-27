<?php
namespace writethesky\WebSocketClient;

class Frame
{
    private $payload;
    private $type;
    private $mask;
    private $frame;


    public function __construct()
    {
        $arguments = func_get_args();
        if(count($arguments) > 0){
            $this->encode(...$arguments);
        }
    }
    public function encode($payload, $type = 'text', $mask = true)
    {
        $this->payload   = $payload;
        $this->type      = $type;
        $this->mask    = $mask;

        $frameHead = [];
        $payloadLength = strlen($payload);

        // PIN 1,RSV1 0,RSV2 0,RSV3 0,
        $frameHead[] = bindec('1000' . FrameType::string2bin($type));
        $mask = ($mask ? 1 : 0);
        if($payloadLength < 126){ // 0-125
            $frameHead[] = bindec($mask . decbin($payloadLength));
        }elseif($payloadLength < 65536){ // 126-65535
            $frameHead[] = bindec($mask . decbin(126));
            $frameHead = array_merge($frameHead, str_split(sprintf('%016b', $payloadLength), 8));
        }else{ // 大于65535
            $frameHead[] = bindec($mask . decbin(127));
            $frameHead = array_merge($frameHead, str_split(sprintf('%064b', $payloadLength), 8));
        }


        // 转换为字符串
        foreach (array_keys($frameHead) as $i)
        {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        // 生成随即 mask key, 4byte:
        $mask = [];
        if ($this->mask === true)
        {
            for ($i = 0; $i < 4; $i++)
            {
                $mask[] = chr(rand(0, 255));
            }
            $frameHead = array_merge($frameHead, $mask);
        }

        $frame = implode('', $frameHead);
        // 添加 payload 到 frame:
        if($this->mask){
            for ($i = 0; $i < $payloadLength; $i++)
            {
                $frame .= $payload[$i] ^ $mask[$i % 4];
            }
        }else{
            for ($i = 0; $i < $payloadLength; $i++)
            {
                $frame .= $payload[$i];
            }
        }



        $this->frame = $frame;


    }


    public static function decode($response)
    {
        $bytes = Byte::string2bytes($response);
        $byteBin = sprintf("%08b", array_shift($bytes));
        $byteArr = str_split($byteBin, 4);
        $typeBin = end($byteArr);


        $type = FrameType::bin2string($typeBin);
        $mask = false;



        $byteBin = sprintf("%08b", array_shift($bytes));
        if(1 == $byteBin[0]){
            $mask = true;
        }

        $lenBin = substr($byteBin, 1);
        $len = bindec($lenBin);




        if($len > 125 && $len < 65536){ // 126-65535
            array_shift($bytes);
            array_shift($bytes);
        } elseif($len > 65535) { // 长度大于65535
            for ($i = 0; $i < 8; $i++)
            {
                array_shift($bytes);
            }
        }

        // 获取mask key
        $maskKey = [];
        if($mask){
            for ($i=0; $i < 4; $i++) {
                $maskKey[] = array_shift($bytes);
            }

            foreach ($bytes as $i => $byte) {
                $bytes[$i] = $bytes[$i] ^ $maskKey[$i % 4];
            }
        }



        $payload = Byte::bytes2string($bytes);

        $frame = new Frame;
        $frame->frame = $response;
        $frame->type = $type;
        $frame->mask = $mask;
        $frame->payload = $payload;
        return $frame;
    }


    public function getPayload()
    {
        return $this->payload;
    }


    public function getType()
    {
        return $this->type;
    }


    public function getFrame()
    {
        return $this->frame;
    }


    public function getMask()
    {
        return $this->mask;
    }

}
