<?php
/** @var array $receipt */
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Receipt <?= htmlspecialchars((string)$receipt['receipt_no'],ENT_QUOTES,'UTF-8') ?></title>
<style>body{font-family:Arial,sans-serif;background:#f5f7fa;margin:0;padding:32px;color:#172033}.receipt{max-width:760px;margin:auto;background:#fff;padding:40px;border:1px solid #e4e7ec}.head{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid #172033;padding-bottom:20px}.muted{color:#667085}.row{display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:12px 0}.total{font-size:22px;font-weight:700}.actions{margin:20px auto;max-width:760px}.actions button{padding:10px 16px}@media print{body{background:#fff;padding:0}.receipt{border:0}.actions{display:none}}</style></head>
<body><div class="actions"><button onclick="window.print()">Print / Save as PDF</button></div><main class="receipt">
<div class="head"><div><h1><?= htmlspecialchars((string)$receipt['school_name'],ENT_QUOTES,'UTF-8') ?></h1><div class="muted"><?= htmlspecialchars((string)($receipt['school_email']??''),ENT_QUOTES,'UTF-8') ?> <?= htmlspecialchars((string)($receipt['school_phone']??''),ENT_QUOTES,'UTF-8') ?></div></div><div><strong>PAYMENT RECEIPT</strong><br><span class="muted"><?= htmlspecialchars((string)$receipt['receipt_no'],ENT_QUOTES,'UTF-8') ?></span></div></div>
<p><strong>Student</strong><br><?= htmlspecialchars(trim((string)$receipt['first_name'].' '.(string)$receipt['last_name']),ENT_QUOTES,'UTF-8') ?><br><span class="muted">Admission No: <?= htmlspecialchars((string)$receipt['admission_no'],ENT_QUOTES,'UTF-8') ?></span></p>
<div class="row"><span>Payment date</span><strong><?= htmlspecialchars((string)$receipt['issued_at'],ENT_QUOTES,'UTF-8') ?></strong></div>
<div class="row"><span>Payment method</span><strong><?= htmlspecialchars((string)$receipt['method'],ENT_QUOTES,'UTF-8') ?></strong></div>
<div class="row"><span>Reference</span><strong><?= htmlspecialchars((string)($receipt['reference']??'N/A'),ENT_QUOTES,'UTF-8') ?></strong></div>
<div class="row"><span>Payer</span><strong><?= htmlspecialchars((string)($receipt['payer_name']??'N/A'),ENT_QUOTES,'UTF-8') ?></strong></div>
<div class="row total"><span>Amount received</span><strong><?= htmlspecialchars((string)$receipt['currency'],ENT_QUOTES,'UTF-8') ?> <?= number_format((float)$receipt['amount'],2) ?></strong></div>
<p class="muted">This receipt confirms that the payment has been recorded by EduSasa for the school shown above.</p>
</main></body></html>
