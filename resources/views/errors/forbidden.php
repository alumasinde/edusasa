<?php
declare(strict_types=1);
?>

<div class="page-error">
    <h1>Access denied</h1>
    <p><?= e($message ?? 'You do not have permission to access this resource.') ?></p>
    <p><a href="/">Return to dashboard</a></p>
</div>
