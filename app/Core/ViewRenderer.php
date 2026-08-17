<?php

declare(strict_types=1);
namespace App\Core;
final class ViewRenderer {
 public static function render(string $view,array $data=[]):string{$base=dirname(__DIR__,2).'/resources/views/';$file=$base.str_replace('.','/',$view).'.php';if(!is_file($file))throw new \RuntimeException("View [{$view}] not found.");$layout=null;$content=self::capture($file,$data,$layout);if($layout!==null){$data['slot']=$content;return self::capture($base.str_replace('.','/',$layout).'.php',$data,$ignored);}return $content;}
 private static function capture(string $file,array $data,?string &$layout):string{extract($data,EXTR_SKIP);ob_start();include $file;return (string)ob_get_clean();}
}
