<?php $esc=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); ?>
<div class="page-header">
    <div>
        <h1>Examination Analytics</h1>
        <p><?= $esc($data['exam']['name'] ?? 'Examination') ?></p>
    </div>
    <a href="/exams/<?= (int)$data['exam']['id'] ?>/analytics/export" class="btn">Export CSV</a>
</div>

<section class="stats-grid">
<?php foreach ([
    'Students' => (int)($data['summary']['students'] ?? 0),
    'Average' => ($data['summary']['average'] ?? 0).'% ',
    'Highest' => ($data['summary']['highest'] ?? 0).'% ',
    'Lowest' => ($data['summary']['lowest'] ?? 0).'% ',
    'Passed' => (int)($data['summary']['passed'] ?? 0),
    'Below 50%' => (int)($data['summary']['failed'] ?? 0),
] as $label => $value): ?>
    <div class="card"><strong><?= $esc(trim((string)$value)) ?></strong><span><?= $esc($label) ?></span></div>
<?php endforeach; ?>
</section>

<section class="card">
<h2>Subject Performance</h2>
<div class="table-wrap"><table>
<thead><tr><th>Subject</th><th>Students</th><th>Average</th><th>Highest</th><th>Lowest</th><th>Passed</th></tr></thead>
<tbody>
<?php foreach (($data['subjects'] ?? []) as $row): ?>
<tr>
<td><?= $esc($row['subject']) ?></td>
<td><?= (int)$row['students'] ?></td>
<td><?= $esc((string)$row['average']) ?>%</td>
<td><?= $esc((string)$row['highest']) ?>%</td>
<td><?= $esc((string)$row['lowest']) ?>%</td>
<td><?= (int)$row['passed'] ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</section>

<section class="card">
<h2>Grade Distribution</h2>
<div class="table-wrap"><table>
<thead><tr><th>Grade</th><th>Students</th></tr></thead>
<tbody>
<?php foreach (($data['grades'] ?? []) as $row): ?>
<tr><td><?= $esc((string)$row['grade']) ?></td><td><?= (int)$row['students'] ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
</section>

<section class="card">
<h2>Top Performers</h2>
<div class="table-wrap"><table>
<thead><tr><th>Admission No.</th><th>Student</th><th>Percentage</th><th>Grade</th><th>Points</th></tr></thead>
<tbody>
<?php foreach (($data['top'] ?? []) as $row): ?>
<tr>
<td><?= $esc($row['admission_no']) ?></td>
<td><?= $esc(trim($row['first_name'].' '.$row['middle_name'].' '.$row['last_name'])) ?></td>
<td><?= $esc((string)$row['percentage']) ?>%</td>
<td><?= $esc((string)$row['grade']) ?></td>
<td><?= $esc((string)$row['points']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</section>

<section class="card">
<h2>Students Needing Support</h2>
<div class="table-wrap"><table>
<thead><tr><th>Admission No.</th><th>Student</th><th>Percentage</th><th>Grade</th><th>Remark</th></tr></thead>
<tbody>
<?php foreach (($data['support'] ?? []) as $row): ?>
<tr>
<td><?= $esc($row['admission_no']) ?></td>
<td><?= $esc(trim($row['first_name'].' '.$row['middle_name'].' '.$row['last_name'])) ?></td>
<td><?= $esc((string)$row['percentage']) ?>%</td>
<td><?= $esc((string)$row['grade']) ?></td>
<td><?= $esc((string)($row['remark'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</section>
