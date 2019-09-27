<?php
namespace writethesky\WebSocketClient;

class FrameType
{
    const MAPS = [
        '0001' => 'text',
        '0010' => 'bin',
        '1000' => 'close',
        '1001' => 'ping',
        '1010' => 'pong',
    ];

    public static function bin2string($bin)
    {
        return self::MAPS[$bin];
    }

    public static function string2bin($string)
    {
        $maps = array_flip(self::MAPS);
        return $maps[$string];
    }
}
