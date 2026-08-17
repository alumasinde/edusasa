<?php

declare(strict_types=1);
namespace App\Core;
final class Session {
 private static bool $started=false;
 public static function start():void{if(self::$started||session_status()===PHP_SESSION_ACTIVE){self::$started=true;return;}session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>Config::env('APP_ENV','production')==='production','httponly'=>true,'samesite'=>'Lax']);session_name(Config::env('SESSION_NAME','edusasa_session'));session_start();self::$started=true;}
 public static function get(string $key,mixed $default=null):mixed{self::start();return $_SESSION[$key]??$default;} public static function set(string $key,mixed $value):void{self::start();$_SESSION[$key]=$value;} public static function has(string $key):bool{self::start();return array_key_exists($key,$_SESSION);} public static function remove(string $key):void{self::start();unset($_SESSION[$key]);}
 public static function flash(string $key,mixed $value):void{self::start();$_SESSION['_flash'][$key]=$value;} public static function getFlash(string $key,mixed $default=null):mixed{self::start();$v=$_SESSION['_flash'][$key]??$default;unset($_SESSION['_flash'][$key]);return $v;} public static function regenerate():void{self::start();session_regenerate_id(true);} public static function destroy():void{self::start();$_SESSION=[];session_destroy();self::$started=false;}
 public static function flashOldInput(array $input):void{self::flash('_old_input',$input);}
}
