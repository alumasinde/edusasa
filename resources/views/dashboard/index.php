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
</head>
<body>
<header class="topbar">
    <a class="brand" href="/dashboard">EduSasa</a>
    <button class="mobile-menu btn-small" type="button" data-mobile-menu aria-label="Toggle navigation">Menu</button>
    <nav class="nav" data-nav>
        <a href="/dashboard">Dashboard</a><a href="/students">Students</a><a href="/teachers">Teachers</a>
        <a href="/attendance">Attendance</a><a href="/finance">Finance</a><a href="/exams">Exams</a><a href="/timetable">Timetable</a>
    </nav>
</header>
<aside class="sidebar">
    <div class="muted">School administration</div>
    <a href="/dashboard">Dashboard</a><a href="/students">Students</a><a href="/teachers">Teachers</a>
    <a href="/attendance">Attendance</a><a href="/exams">Examinations</a><a href="/timetable">Timetable</a>
    <a href="/finance">Finance</a><a href="/reports">Reports</a><a href="/communication">Communication</a>
    <a href="/settings">Settings</a>
</aside>
<main class="main-content">
<div class="page-header">
  <div><h1>School Dashboard</h1><p class="muted"><?= $firstName!=='' ? 'Welcome back, '.htmlspecialchars($firstName).'. Here is today’s operational overview.' : 'School operations overview.' ?></p></div>
  <div class="actions"><a class="btn" href="/students">Manage students</a><a class="btn btn-secondary" href="/teachers">Manage teachers</a></div>
</div>
<section class="grid grid-4" aria-label="School statistics">
<?php foreach($labels as $key=>$label): ?>
<article class="stat-card"><div class="muted"><?= htmlspecialchars($label) ?></div><div class="value"><?= htmlspecialchars((string)($stats[$key]??0)) ?></div></article>
<?php endforeach; ?>
<article class="stat-card"><div class="muted">Outstanding Fees</div><div class="value">KES <?= number_format((float)($stats['outstanding_fees']??0),2) ?></div></article>
</section>
<div class="grid grid-2" style="margin-top:16px">
<section class="card"><h2>Today's Attendance</h2>
<?php $total=(int)($attendance['total']??0); foreach(['present'=>'Present','absent'=>'Absent','late'=>'Late','excused'=>'Excused'] as $key=>$label): $value=(int)($attendance[$key]??0); $percent=$total>0?round(($value/$total)*100,1):0; $barColor=$key==='present'?'#16804b':($key==='absent'?'#c43232':($key==='late'?'#b54708':'#64748b')); ?>
<div style="display:flex;justify-content:space-between;margin:12px 0 6px"><span><?= $label ?></span><strong><?= $value ?> (<?= $percent ?>%)</strong></div>
<div style="height:8px;background:#eef2f6;border-radius:99px;overflow:hidden"><span style="display:block;height:100%;width:<?= $percent ?>%;background:<?= $barColor ?>"></span></div>
<?php endforeach; ?>
<?php if($total===0): ?><p class="muted" style="margin-top:14px">No attendance has been marked today.</p><?php endif; ?>
</section>
<section class="card"><div class="page-header" style="margin:0 0 12px"><div><h2>Recent Announcements</h2><p class="muted">Latest school communication.</p></div><a href="/communication">View all</a></div>
<?php if(!$recentAnnouncements): ?><div class="empty-state">No published announcements yet.</div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Title</th><th>Type</th><th>Date</th></tr></thead><tbody>
<?php foreach($recentAnnouncements as $item): ?><tr><td><strong><?= htmlspecialchars((string)$item['title']) ?></strong></td><td><span class="badge"><?= htmlspecialchars((string)$item['type']) ?></span></td><td><?= htmlspecialchars((string)$item['published_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
</div>
</main>
</body></html>
