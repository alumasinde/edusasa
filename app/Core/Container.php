<?php

declare(strict_types=1);
namespace App\Core;
use ReflectionClass;
use ReflectionNamedType;
final class Container {
 private static ?self $instance=null; private array $bindings=[]; private array $instances=[];
 public static function getInstance():self{return self::$instance??=new self();}
 public function bind(string $abstract,callable|string|null $concrete=null,bool $singleton=false):void{$this->bindings[$abstract]=['concrete'=>$concrete??$abstract,'singleton'=>$singleton];}
 public function singleton(string $abstract,callable|string|null $concrete=null):void{$this->bind($abstract,$concrete,true);}
 public function instance(string $abstract,object $instance):void{$this->instances[$abstract]=$instance;}
 public function make(string $abstract):object{if(isset($this->instances[$abstract]))return $this->instances[$abstract];$b=$this->bindings[$abstract]??['concrete'=>$abstract,'singleton'=>false];$o=is_callable($b['concrete'])?$b['concrete']($this):$this->build((string)$b['concrete']);if($b['singleton'])$this->instances[$abstract]=$o;return $o;}
 private function build(string $class):object{$r=new ReflectionClass($class);if(!$r->isInstantiable())throw new \RuntimeException("Class [{$class}] is not instantiable.");$c=$r->getConstructor();if($c===null)return $r->newInstance();$a=[];foreach($c->getParameters() as $p){$t=$p->getType();if($t instanceof ReflectionNamedType&&!$t->isBuiltin())$a[]=$this->make($t->getName());elseif($p->isDefaultValueAvailable())$a[]=$p->getDefaultValue();else throw new \RuntimeException("Unable to resolve dependency [{$p->getName()}] for [{$class}].");}return $r->newInstanceArgs($a);}
}
