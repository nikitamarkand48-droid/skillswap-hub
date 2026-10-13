<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-2 shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= app_path('index.php'); ?>">
            <div class="p-2 bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-arrow-left-right fs-5"></i>
            </div>
            <span class="fs-4 fw-bold">Skill<span class="text-primary">Swap</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-query="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'index.php') ? 'active' : ''; ?>" href="<?= app_path('index.php'); ?>">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'search.php') ? 'active' : ''; ?>" href="<?= app_path('student/search.php'); ?>">
                        <i class="bi bi-search me-1"></i> Search Skills
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'match.php') ? 'active' : ''; ?>" href="<?= app_path('student/match.php'); ?>">
                        <i class="bi bi-stars text-warning me-1"></i> Skill Matching
                    </a>
                </li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'dashboard.php') ? 'active' : ''; ?>" href="<?= app_path('student/dashboard.php'); ?>">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'my_skills.php') ? 'active' : ''; ?>" href="<?= app_path('student/my_skills.php'); ?>">
                            <i class="bi bi-collection me-1"></i> My Skills
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'swap_requests.php') ? 'active' : ''; ?>" href="<?= app_path('student/swap_requests.php'); ?>">
                            <i class="bi bi-arrow-repeat me-1"></i> Swaps
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-2">
                <?php if (is_logged_in()): ?>
                    <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-danger btn-sm rounded-pill px-3" href="<?= app_path('admin/dashboard.php'); ?>">
                                <i class="bi bi-shield-lock me-1"></i> Admin Panel
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-dark" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar-circle avatar-circle-sm">
                                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <span class="fw-semibold small d-none d-sm-inline"><?= sanitize($_SESSION['user_name'] ?? 'Account'); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userMenu">
                            <li>
                                <div class="px-3 py-2 border-bottom">
                                    <p class="mb-0 fw-bold"><?= sanitize($_SESSION['user_name'] ?? ''); ?></p>
                                    <small class="text-muted"><?= sanitize($_SESSION['user_email'] ?? ''); ?></small>
                                </div>
                            </li>
                            <li><a class="dropdown-item py-2" href="<?= app_path('student/profile.php'); ?>"><i class="bi bi-person me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item py-2" href="<?= app_path('student/my_skills.php'); ?>"><i class="bi bi-plus-circle me-2"></i> Manage Skills</a></li>
                            <li><a class="dropdown-item py-2" href="<?= app_path('student/swap_requests.php'); ?>"><i class="bi bi-arrow-left-right me-2"></i> Swap Requests</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?= app_path('auth/logout.php'); ?>"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= app_path('auth/login.php'); ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary rounded-pill px-4 shadow-sm" href="<?= app_path('auth/register.php'); ?>">Register</a>
                    </li>
                    <li class="nav-item dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle rounded-pill" type="button" id="demoQuickLogin" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Demo
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="demoQuickLogin">
                            <li><h6 class="dropdown-header">1-Click Demo Accounts</h6></li>
                            <li><a class="dropdown-item" href="<?= app_path('auth/login.php?demo=aarav'); ?>"><i class="bi bi-person-fill text-primary me-2"></i> Aarav Sharma (Tech: Python)</a></li>
                            <li><a class="dropdown-item" href="<?= app_path('auth/login.php?demo=priya'); ?>"><i class="bi bi-person-fill text-success me-2"></i> Priya Patel (Tech: Design/Figma)</a></li>
                            <li><a class="dropdown-item" href="<?= app_path('auth/login.php?demo=admin'); ?>"><i class="bi bi-shield-fill text-danger me-2"></i> Admin Panel</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
