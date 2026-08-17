<?php

declare(strict_types=1);
namespace App\Core;
final class Notifications { private static array $channels=[]; public static function register(string $name,object $channel):void{self::$channels[$name]=$channel;} public static function send(string $channel,mixed $notifiable,array $payload=[]):void{if(isset(self::$channels[$channel])&&method_exists(self::$channels[$channel],'send'))self::$channels[$channel]->send($notifiable,$payload);} }
