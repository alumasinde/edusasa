<?php

declare(strict_types=1);
namespace App\Core;
final class Authorization { public function __construct(private readonly Auth $auth,private readonly Database $db){} public function can(string $permission):bool{if(!$this->auth->check())return false;$row=$this->db->selectOne('SELECT COUNT(*) total FROM user_roles ur INNER JOIN role_permissions rp ON rp.role_id=ur.role_id INNER JOIN permissions p ON p.id=rp.permission_id WHERE ur.user_id=:id AND p.name=:permission',['id'=>$this->auth->id(),'permission'=>$permission]);return (int)($row['total']??0)>0;} public function authorize(string $permission):void{if(!$this->can($permission))throw new ForbiddenException("Missing permission [{$permission}].");} public function permissions():array{return [];}}
