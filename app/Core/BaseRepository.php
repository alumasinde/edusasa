<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Tenant;
use App\Core\NotFoundException;

abstract class BaseRepository
{
    protected Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    abstract protected function table(): string;

    protected function tenantColumn(): string
    {
        return 'school_id';
    }

    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db->select('SELECT * FROM '.$this->table().' WHERE '.$this->tenantColumn().' = :school_id AND deleted_at IS NULL ORDER BY '.$orderBy, ['school_id' => Tenant::id()]);
    }

    public function where(array $conditions, string $orderBy = 'id DESC'): array
    {
        [$where, $params] = $this->conditions($conditions);
        return $this->db->select('SELECT * FROM '.$this->table().' '.$where.' ORDER BY '.$orderBy, $params);
    }

    public function whereFirst(array $conditions): ?array
    {
        [$where, $params] = $this->conditions($conditions);
        return $this->db->selectOne('SELECT * FROM '.$this->table().' '.$where.' LIMIT 1', $params);
    }

    public function find(int $id): ?array
    {
        return $this->whereFirst(['id' => $id]);
    }

    public function findOrFail(int $id): array
    {
        $row = $this->find($id);
        if ($row === null) {
            throw new NotFoundException(ucfirst($this->table()).' record not found.');
        }
        return $row;
    }

    public function create(array $data): int
    {
        $data[$this->tenantColumn()] ??= Tenant::id();
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $column): string => ':'.$column, $columns);
        $sql = 'INSERT INTO '.$this->table().' ('.implode(',', $columns).') VALUES ('.implode(',', $placeholders).')';
        return (int) $this->db->insert($sql, $data);
    }

    public function update(int $id, array $data): int
    {
        if ($data === []) return 0;
        $assignments = [];
        $params = ['id' => $id, 'school_id' => Tenant::id()];
        foreach ($data as $column => $value) {
            $assignments[] = $column.' = :set_'.$column;
            $params['set_'.$column] = $value;
        }
        return $this->db->execute('UPDATE '.$this->table().' SET '.implode(',', $assignments).' WHERE id=:id AND '.$this->tenantColumn().'=:school_id', $params);
    }

    public function delete(int $id): int
    {
        return $this->db->execute('UPDATE '.$this->table().' SET deleted_at=CURRENT_TIMESTAMP WHERE id=:id AND '.$this->tenantColumn().'=:school_id', ['id'=>$id,'school_id'=>Tenant::id()]);
    }

    private function conditions(array $conditions): array
    {
        $conditions[$this->tenantColumn()] ??= Tenant::id();
        $parts = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $key = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
            $parts[] = $key.' = :where_'.$key;
            $params['where_'.$key] = $value;
        }
        return ['WHERE '.implode(' AND ', $parts), $params];
    }
}
