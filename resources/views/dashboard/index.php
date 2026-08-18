<?php
/** @var array $stats */
/** @var array $attendance */
/** @var array $recentAnnouncements */
/** @var array $user */

$labels = [
    'students' => 'Active Students',
    'teachers' => 'Active Teachers',
    'classes' => 'Classes',
    'attendance_today' => 'Attendance Marked Today',
    'published_exams' => 'Published Exams',
    'published_timetables' => 'Published Timetables',
    'unread_communications' => 'Unread Messages',
];
$firstName = trim((string)($user['first_name'] ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>EduSasa Dashboard</title>
<style>
body{font-family:system-ui,-apple-system,sans-serif;margin:0;background:#f5f7fb;color:#172033}.wrap{max-width:1280px;margin:auto;padding:28px}.top{display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:24px}.muted{color:#667085}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px}.value{font-size:28px;font-weight:750;margin-top:7px}.money{font-size:22px;font-weight:750;margin-top:7px}.layout{display:grid;grid-template-columns:1.2fr .8fr;gap:16px;margin-top:16px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px;border-bottom:1px solid #eee}a{color:inherit;text-decoration:none}.links{display:flex;flex-wrap:wrap;gap:9px}.links a{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:9px 12px}.bar{height:9px;background:#eef1f5;border-radius:99px;overflow:hidden}.bar span{display:block;height:100%}.row{display:flex;justify-content:space-between;margin:8px 0}.present{background:#16803c}.absent{background:#c62828}.late{background:#d97706}.excused{background:#475569}@media(max-width:800px){.layout{grid-template-columns:1fr}.top{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body><main class="wrap">
<div class="top"><div><h1>School Dashboard</h1><p class="muted"><?= $firstName!=='' ? 'Welcome back, '.htmlspecialchars($firstName).'.' : 'School operations overview.' ?></p></div><div class="links"><a href="/students">Students</a><a href="/teachers">Teachers</a><a href="/attendance">Attendance</a><a href="/finance">Finance</a><a href="/exams">Exams</a><a href="/timetable">Timetable</a><a href="/reports">Reports</a></div></div>
<section class="grid">
<?php foreach($labels as $key=>$label): ?><div class="card"><div class="muted"><?= htmlspecialchars($label) ?></div><div class="value"><?= htmlspecialchars((string)($stats[$key]??0)) ?></div></div><?php endforeach; ?>
<div class="card"><div class="muted">Outstanding Fees</div><div class="money">KES <?= number_format((float)($stats['outstanding_fees']??0),2) ?></div></div>
</section>
<div class="layout">
<section class="card"><h2>Today's Attendance</h2><?php $total=(int)($attendance['total']??0); foreach(['present'=>'Present','absent'=>'Absent','late'=>'Late','excused'=>'Excused'] as $key=>$label): $value=(int)($attendance[$key]??0); $percent=$total>0?round(($value/$total)*100,1):0; ?><div class="row"><span><?= $label ?></span><strong><?= $value ?> (<?= $percent ?>%)</strong></div><div class="bar"><span class="<?= $key ?>" style="width:<?= $percent ?>%"></span></div><?php endforeach; ?><?php if($total===0): ?><p class="muted">No attendance has been marked today.</p><?php endif; ?></section>
<section class="card"><h2>Recent Announcements</h2><?php if(!$recentAnnouncements): ?><p class="muted">No published announcements yet.</p><?php else: ?><table><thead><tr><th>Title</th><th>Type</th><th>Date</th></tr></thead><tbody><?php foreach($recentAnnouncements as $item): ?><tr><td><?= htmlspecialchars((string)$item['title']) ?></td><td><?= htmlspecialchars((string)$item['type']) ?></td><td><?= htmlspecialchars((string)$item['published_at']) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></section>
</div>
</main></body></html>
