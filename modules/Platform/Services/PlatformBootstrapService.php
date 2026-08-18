<?php

declare(strict_types=1);
namespace Modules\Platform\Services;
use App\Core\Database;
use InvalidArgumentException;
use RuntimeException;
final class PlatformBootstrapService {
 public function __construct(private readonly Database $db){}
 public function available():bool{$row=$this->db->fetchOne('SELECT COUNT(*) AS total FROM platform_users');return (int)($row['total']??0)===0;}
 public function create(string $first,string $last,string $email,string $password,string $confirm):int{
  $first=trim($first);$last=trim($last);$email=strtolower(trim($email));
  if(!$this->available())throw new RuntimeException('Initial platform setup has already been completed.');
  if($first===''||$last==='')throw new InvalidArgumentException('First name and last name are required.');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('A valid email address is required.');
  if($password!==$confirm)throw new InvalidArgumentException('Passwords do not match.');
  if(strlen($password)<12||!preg_match('/[A-Z]/',$password)||!preg_match('/[a-z]/',$password)||!preg_match('/\d/',$password))throw new InvalidArgumentException('Password must be at least 12 characters and include upper-case, lower-case and a number.');
  return (int)$this->db->transaction(function(Database $db)use($first,$last,$email,$password):int{
   if($db->fetchOne('SELECT id FROM platform_users WHERE email=:email LIMIT 1',['email'=>$email]))throw new InvalidArgumentException('An account already exists for this email.');
   $id=(int)$db->insert('INSERT INTO platform_users(first_name,last_name,email,password_hash,status) VALUES(:first_name,:last_name,:email,:password_hash,\'active\')',['first_name'=>$first,'last_name'=>$last,'email'=>$email,'password_hash'=>password_hash($password,PASSWORD_DEFAULT)]);
   $role=$db->fetchOne('SELECT id FROM platform_roles WHERE code=\'super_admin\' AND is_active=1 LIMIT 1');
   if($role===null)throw new RuntimeException('The super admin role is missing. Run the platform migrations first.');
   $db->insert('INSERT INTO platform_user_roles(platform_user_id,role_id) VALUES(:user_id,:role_id)',['user_id'=>$id,'role_id'=>(int)$role['id']]);
   $db->insert('INSERT INTO platform_audit_logs(platform_user_id,action,resource_type,resource_id,metadata_json) VALUES(:user_id,\'platform.bootstrap.created\',\'platform_user\',:resource_id,:metadata)',['user_id'=>$id,'resource_id'=>$id,'metadata'=>json_encode(['email'=>$email],JSON_THROW_ON_ERROR)]);
   return $id;
  });
 }
}
