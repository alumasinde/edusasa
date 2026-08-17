<?php
declare(strict_types=1);
namespace App\Core;
final class LogChannel { public function send(mixed $notifiable,array $payload=[]):void{Logger::info('Notification', ['notifiable'=>$notifiable,'payload'=>$payload]);} }
