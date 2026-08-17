<?php

declare(strict_types=1);
namespace App\Core;
final class Csrf { public static function token():string{Session::start();$token=Session::get('_csrf_token');if(!is_string($token)||$token===''){ $token=bin2hex(random_bytes(32));Session::set('_csrf_token',$token);}return $token;} public static function verify(?string $token):void{if(!hash_equals(self::token(),(string)$token))throw new ForbiddenException('Invalid CSRF token.');} }
