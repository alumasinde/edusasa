<?php
declare(strict_types=1);
namespace App\Core;
final class EmailChannel { public function send(mixed $notifiable,array $payload=[]):void{Logger::info('Email notification queued', ['notifiable'=>$notifiable]);} }
