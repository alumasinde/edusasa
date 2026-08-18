<?php

declare(strict_types=1);

namespace Modules\Finance\Providers;

interface PaymentProvider
{
    public function initiate(array $transaction, array $channel): array;
}
