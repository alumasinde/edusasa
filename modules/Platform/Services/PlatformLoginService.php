<?php

declare(strict_types=1);
namespace Modules\Platform\Services;
use App\Core\Database;
use App\Core\Session;
use InvalidArgumentException;
final class PlatformLoginService {
 public function __construct(private readonly Database $db){}
 public function login(string $email,string $password):void{
  $user=$this->db->fetchOne('SELECT id,password_hash,status FROM platform_users WHERE email=:email LIMIT 1',['email'=>strtolower(trim($email))]);
  if($user===null||!password_verify($password,(string)$user['password_hash'])||(string)$user['status']!=='active')throw new InvalidArgumentException('Invalid platform administrator credentials.');
  Session::regenerate();Session::set('platform_user_id',(int)$user['id']);
  $this->db->execute('UPDATE platform_users SET last_login_at=NOW() WHERE id=:id',['id'=>(int)$user['id']]);
 }
}
