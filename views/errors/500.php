<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper" style="background: var(--primary);">
        <div class="auth-card text-center">
            <h1 style="font-size: 4rem; color: var(--danger);">500</h1>
            <h2>Server Error</h2>
            <p class="text-muted">Something went wrong. Please try again later.</p>
            <a href="index.php" class="btn btn-primary mt-4"><i class="fas fa-home"></i> Go to Dashboard</a>
            <p class="text-muted mt-4" style="font-size: 0.75rem;">If this problem persists, please contact IT support.</p>
        </div>
    </div>
</body>
</html>
