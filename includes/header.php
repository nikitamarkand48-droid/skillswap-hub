<?php
require_once __DIR__ . '/../config/db.php';
$flash = get_flash();
$pageTitle = $pageTitle ?? 'Peer-to-Peer Learning Platform';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle); ?> | SkillSwap</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= app_path('assets/css/style.css'); ?>">
    <script>
        window.SKILLSWAP_BASE_URL = "<?= app_path(''); ?>";
    </script>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>

<main class="py-4">
    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']); ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= sanitize($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
