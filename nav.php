<?php
/**
 * Shared Navigation Component for Notioneers Internal Apps
 *
 * Usage:
 * require_once __DIR__ . '/path/to/internal-shared/components/nav.php';
 * renderInternalNav('Design System'); // Pass active page name
 */

function renderInternalNav(string $activePage = ''): void {
    $apps = [
        'Admintool' => '/admintool/',
        'Design System' => '/design-system/',
        // Add more internal apps here as they are created
    ];
    ?>
    <nav class="navbar navbar-expand-lg bg-depth sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center text-bloom" href="/admintool/">
                <svg width="32" height="32" class="me-2" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="45" fill="#92EF9A"/>
                </svg>
                <span class="fw-medium">Notioneers Internal</span>
            </a>

            <button class="navbar-toggler border-bloom" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php foreach ($apps as $name => $url): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage === $name ? 'active text-bloom fw-medium' : 'text-sage' ?>"
                               href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>
    <?php
}
