<?php declare(strict_types=1); ?>
<h1>Add Teacher</h1>
<form method="post" action="/teachers">
<?= csrf_field() ?>
<label>Employee No. <input name="employee_no"></label><br>
<label>First name <input name="first_name" required></label><br>
<label>Last name <input name="last_name" required></label><br>
<label>Email <input type="email" name="email"></label><br>
<label>Phone <input name="phone"></label><br>
<label>Status <select name="status"><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option></select></label><br>
<button type="submit">Save Teacher</button>
</form>
