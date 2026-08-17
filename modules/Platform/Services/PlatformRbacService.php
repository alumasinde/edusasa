<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\Database;
use App\Core\Session;
use RuntimeException;

final class PlatformRbacService
{
    public function __construct(private readonly Database $db) {}

    public function can(string $permission, ?int $userId = null): bool
    {
        $userId ??= (int) Session::get('platform_user_id');
        if ($userId <= 0) return false;
        $row = $this->db->fetchOne(
            'SELECT 1 FROM platform_users u
             JOIN platform_user_roles ur ON ur.platform_user_id=u.id
             JOIN platform_roles r ON r.id=ur.role_id AND r.is_active=1
             JOIN platform_role_permissions rp ON rp.role_id=r.id
             JOIN platform_permissions p ON p.id=rp.permission_id
             WHERE u.id=:user_id AND u.status=\'active\' AND p.code=:permission LIMIT 1',
            ['user_id'=>$userId,'permission'=>$permission]
        );
        return $row !== null;
    }

    public function assert(string $permission): void
    {
        if (!$this->can($permission)) {
            throw new RuntimeException('Platform permission required: '.$permission);
        }
    }

    public function catalog(): array
    {
        $roles = $this->db->fetchAll('SELECT id,code,name,description,is_system,is_active FROM platform_roles ORDER BY is_system DESC,name');
        $permissions = $this->db->fetchAll('SELECT id,code,name,module_code,description FROM platform_permissions ORDER BY module_code,name');
        $assignments = $this->db->fetchAll('SELECT role_id,permission_id FROM platform_role_permissions');
        $map=[];
        foreach ($assignments as $a) $map[(int)$a['role_id'].':'.(int)$a['permission_id']]=true;
        foreach ($roles as &$role) {
            $role['permissions']=[];
            foreach ($permissions as $permission) if (isset($map[(int)$role['id'].':'.(int)$permission['id']])) $role['permissions'][]=$permission['code'];
        }
        unset($role);
        return compact('roles','permissions');
    }

    public function saveRole(array $input, ?int $roleId=null): int
    {
        $code=preg_replace('/[^a-z0-9_]+/','_',strtolower(trim((string)($input['code']??''))));
        $name=trim((string)($input['name']??''));
        if ($code===''||$name==='') throw new RuntimeException('Role code and name are required.');
        if ($roleId) {
            $role=$this->db->fetchOne('SELECT id,is_system FROM platform_roles WHERE id=:id',['id'=>$roleId]);
            if (!$role) throw new RuntimeException('Role not found.');
            if ((int)$role['is_system']===1) throw new RuntimeException('System roles cannot be renamed.');
            $this->db->update('UPDATE platform_roles SET code=:code,name=:name,description=:description,is_active=:active WHERE id=:id',[
                'id'=>$roleId,'code'=>$code,'name'=>$name,'description'=>trim((string)($input['description']??''))?:null,'active'=>!empty($input['is_active'])?1:0]);
            return $roleId;
        }
        return (int)$this->db->insert('INSERT INTO platform_roles(code,name,description,is_active) VALUES(:code,:name,:description,:active)',[
            'code'=>$code,'name'=>$name,'description'=>trim((string)($input['description']??''))?:null,'active'=>!empty($input['is_active'])?1:0]);
    }

    public function setPermissions(int $roleId, array $permissionIds): void
    {
        $role=$this->db->fetchOne('SELECT id,is_system FROM platform_roles WHERE id=:id',['id'=>$roleId]);
        if (!$role) throw new RuntimeException('Role not found.');
        if ((int)$role['is_system']===1 && (string)$this->db->fetchOne('SELECT code FROM platform_roles WHERE id=:id',['id'=>$roleId])['code']==='super_admin') return;
        $permissionIds=array_values(array_unique(array_filter(array_map('intval',$permissionIds))));
        $this->db->transaction(function() use($roleId,$permissionIds):void{
            $this->db->delete('DELETE FROM platform_role_permissions WHERE role_id=:role_id',['role_id'=>$roleId]);
            foreach($permissionIds as $permissionId) {
                $this->db->insert('INSERT INTO platform_role_permissions(role_id,permission_id) SELECT :role_id,id FROM platform_permissions WHERE id=:permission_id',[
                    'role_id'=>$roleId,'permission_id'=>$permissionId]);
            }
        });
    }

    public function users(): array
    {
        return $this->db->fetchAll('SELECT u.id,u.first_name,u.last_name,u.email,u.status,GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR \' | \') roles
            FROM platform_users u LEFT JOIN platform_user_roles ur ON ur.platform_user_id=u.id LEFT JOIN platform_roles r ON r.id=ur.role_id
            GROUP BY u.id ORDER BY u.first_name,u.last_name');
    }

    public function assignRoles(int $userId,array $roleIds): void
    {
        if (!$this->db->fetchOne('SELECT id FROM platform_users WHERE id=:id',['id'=>$userId])) throw new RuntimeException('Platform user not found.');
        $roleIds=array_values(array_unique(array_filter(array_map('intval',$roleIds))));
        $this->db->transaction(function()use($userId,$roleIds):void{
            $this->db->delete('DELETE FROM platform_user_roles WHERE platform_user_id=:id',['id'=>$userId]);
            foreach($roleIds as $roleId) $this->db->insert('INSERT INTO platform_user_roles(platform_user_id,role_id) SELECT :user_id,id FROM platform_roles WHERE id=:role_id', ['user_id'=>$userId,'role_id'=>$roleId]);
        });
    }
}
