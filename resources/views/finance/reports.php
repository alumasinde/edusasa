<?php
/** @var array $filters */
/** @var array $summary */
/** @var array $payments */
/** @var array $outstanding */
/** @var array $daily */
/** @var array $adjustments */
/** @var array $refunds */
?>
<div class="page-header"><div><h1>Finance Reports</h1><p>Collections, outstanding fees and adjustments for the selected period.</p></div></div>
<form method="get" action="/finance/reports" class="card filter-grid">
    <label>From<input type="date" name="from" value="<?=htmlspecialchars($filters['from'])?>"></label>
    <label>To<input type="date" name="to" value="<?=htmlspecialchars($filters['to'])?>"></label>
    <label>Payment method<input name="method" value="<?=htmlspecialchars($filters['method'])?>" placeholder="M-Pesa, Bank..."></label>
    <div><button type="submit">Apply filters</button><a href="/finance/reports/export?from=<?=urlencode($filters['from'])?>&to=<?=urlencode($filters['to'])?>&method=<?=urlencode($filters['method'])?>" class="button">Export JSON</a></div>
</form>
<div class="stats-grid">
    <div class="card"><small>Billed</small><strong>KES <?=number_format((float)($summary['billed']??0),2)?></strong></div>
    <div class="card"><small>Collected</small><strong>KES <?=number_format((float)($summary['collected']??0),2)?></strong></div>
    <div class="card"><small>Outstanding</small><strong>KES <?=number_format((float)($summary['outstanding']??0),2)?></strong></div>
    <div class="card"><small>Refunded</small><strong>KES <?=number_format((float)($refunds['refunded']??0),2)?></strong></div>
</div>
<div class="grid-2">
<section class="card"><h2>Collections by payment method</h2><table><thead><tr><th>Method</th><th>Transactions</th><th>Amount</th></tr></thead><tbody><?php foreach($payments as $row): ?><tr><td><?=htmlspecialchars((string)$row['method'])?></td><td><?=number_format((int)$row['transactions'])?></td><td>KES <?=number_format((float)$row['amount'],2)?></td></tr><?php endforeach; ?></tbody></table></section>
<section class="card"><h2>Outstanding by class</h2><table><thead><tr><th>Class</th><th>Students</th><th>Outstanding</th></tr></thead><tbody><?php foreach($outstanding as $row): ?><tr><td><?=htmlspecialchars((string)$row['class_name'])?></td><td><?=number_format((int)$row['students'])?></td><td>KES <?=number_format((float)$row['outstanding'],2)?></td></tr><?php endforeach; ?></tbody></table></section>
<section class="card"><h2>Daily collections</h2><table><thead><tr><th>Date</th><th>Transactions</th><th>Amount</th></tr></thead><tbody><?php foreach($daily as $row): ?><tr><td><?=htmlspecialchars((string)$row['payment_date'])?></td><td><?=number_format((int)$row['transactions'])?></td><td>KES <?=number_format((float)$row['amount'],2)?></td></tr><?php endforeach; ?></tbody></table></section>
<section class="card"><h2>Adjustments</h2><table><tbody><tr><th>Discounts</th><td>KES <?=number_format((float)($adjustments['discounts']??0),2)?></td></tr><tr><th>Waivers</th><td>KES <?=number_format((float)($adjustments['waivers']??0),2)?></td></tr><tr><th>Credits</th><td>KES <?=number_format((float)($adjustments['credits']??0),2)?></td></tr></tbody></table></section>
</div>
