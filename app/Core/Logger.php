<?php

declare(strict_types=1);
namespace App\Core;
final class Logger {
 public static function info(string $message,array $context=[]):void{self::write('INFO',$message,$context);} public static function warning(string $message,array $context=[]):void{self::write('WARNING',$message,$context);} public static function error(string $message,array $context=[]):void{self::write('ERROR',$message,$context);}
 private static function write(string $level,string $message,array $context=[]):void{$dir=dirname(__DIR__,2).'/storage/logs';if(!is_dir($dir))@mkdir($dir,0775,true);$line=date('c')." {$level} {$message}".($context?' '.json_encode($context,JSON_UNESCAPED_SLASHES):'').PHP_EOL;@file_put_contents($dir.'/'.date('Y-m-d').'.log',$line,FILE_APPEND|LOCK_EX);}
}
