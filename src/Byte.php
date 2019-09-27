<?php
namespace writethesky\WebSocketClient;

class Byte
{
    public static function bin2bytes($bin)
    {
        $z = strlen($bin) % 8;
        if($z){
            for ($i = 0; $i < 8 - $z; $i++) {
                $bin = '0' . $bin;
            }
        }

        $binArr = str_split($bin, 8);
        foreach ($binArr as $key => $value) {
            $binArr[$key] = bindec($value);
        }
        return $binArr;

    }

    public static function string2bytes($string)
    {
        $bytes = [];
        for ($i=0; $i < strlen($string); $i++) {
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
    }

    public static function bytes2string($bytes)
    {
        $string = "";
        foreach ($bytes as $key => $value) {
            $string .= chr($value);
        }
        return $string;
    }
}
