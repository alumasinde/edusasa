<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

class Database
{
    private static ?Database $instance=null;
    private PDO $pdo;
    private function __construct(){
        $dsn=sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',Config::env('DB_HOST','127.0.0.1'),Config::env('DB_PORT','3306'),Config::env('DB_DATABASE','edusasa'),Config::env('DB_CHARSET','utf8mb4'));
        $this->pdo=new PDO($dsn,(string)Config::env('DB_USERNAME','root'),(string)Config::env('DB_PASSWORD',''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_STRINGIFY_FETCHES=>false]);
    }
    public static function getInstance():self{return self::$instance??=new self();}
    public function pdo():PDO{return$this->pdo;}
    public function query(string $sql,array $params=[]):PDOStatement{$statement=$this->pdo->prepare($sql);$statement->execute($params);return$statement;}
    public function select(string $sql,array $params=[]):array{return$this->query($sql,$params)->fetchAll();}
    public function selectOne(string $sql,array $params=[]):?array{$row=$this->query($sql,$params)->fetch();return$row===false?null:$row;}
    public function fetchAll(string $sql,array $params=[]):array{return$this->select($sql,$params);}
    public function fetchOne(string $sql,array $params=[]):?array{return$this->selectOne($sql,$params);}

    /** Supports both insert($sql,$params) and insert($table,$data). */
    public function insert(string $sqlOrTable,array $params=[]):string
    {
        if(preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$sqlOrTable)===1){
            if($params===[])throw new \InvalidArgumentException('Insert data cannot be empty.');
            $columns=array_keys($params);$placeholders=[];$bound=[];
            foreach($columns as $column){$safe=preg_replace('/[^A-Za-z0-9_]/','',(string)$column);$placeholders[]=':'.$safe;$bound[$safe]=$params[$column];}
            $sql='INSERT INTO '.$sqlOrTable.' ('.implode(',',$columns).') VALUES ('.implode(',',$placeholders).')';
            $this->query($sql,$bound);
        }else{$this->query($sqlOrTable,$params);}
        return$this->pdo->lastInsertId();
    }
    public function execute(string $sql,array $params=[]):int{return$this->query($sql,$params)->rowCount();}
    public function update(string $sql,array $params=[]):int{return$this->execute($sql,$params);}
    public function delete(string $sql,array $params=[]):int{return$this->execute($sql,$params);}
    public function transaction(callable $callback):mixed{$this->pdo->beginTransaction();try{$result=$callback($this);$this->pdo->commit();return$result;}catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw$e;}}
}
