<?php $layout='layouts.app'; use App\Components\Breadcrumb; use App\Components\Button; use App\Components\Select; use App\Components\Input; ?>
<?= Breadcrumb::render([['label'=>'Timetable','href'=>'/timetable'],['label'=>'Create']]) ?>
<h1>Create timetable</h1>
<div class="card" style="max-width:760px;margin-top:var(--space-4)"><div class="card-body"><form action="/timetable" method="POST"><?= csrf() ?>
<div class="form-row"><?= Select::render('academic_year_id',array_column($years,'name','id'),'Academic year',old('academic_year_id',''),placeholder:'Select year',errors:$errors) ?><?= Select::render('term_id',array_column($terms,'name','id'),'Term',old('term_id',''),placeholder:'Select term',errors:$errors) ?></div>
<?= Input::render('name','Timetable name',value:old('name','Term timetable'),required:true,errors:$errors) ?>
<div style="margin-top:var(--space-5)"><?= Button::primary('Create timetable',type:'submit') ?> <?= Button::outline('Cancel',href:'/timetable') ?></div></form></div></div>
