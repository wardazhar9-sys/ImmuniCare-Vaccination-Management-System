<?php
$admin_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="dashboard-sidebar">
    <div class="sidebar-brand">
        <img src="../assets/images/immunicare-logo-admin-sidebar.svg" alt="ImmuniCare" class="sidebar-brand-image">
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">MAIN MENU</div>
        <?php
        $links = [
            'dashboard.php' => ['⌂', 'Dashboard'],
            'users.php' => ['♧', 'Users'],
            'children.php' => ['♙', 'Children'],
            'hospitals.php' => ['♜', 'Hospitals'],
            'vaccines.php' => ['✚', 'Vaccines'],
            'vaccine_doses.php' => ['◈', 'Vaccine Doses'],
            'inventory.php' => ['▣', 'Inventory'],
            'reports.php' => ['▤', 'Reports'],
            'contact_messages.php' => ['✉', 'Messages'],
        ];
        foreach ($links as $href => [$icon, $label]):
        ?>
            <a href="<?php echo e($href); ?>" class="sidebar-link <?php echo $admin_page === $href ? 'active' : ''; ?>">
                <span class="sidebar-icon"><?php echo e($icon); ?></span>
                <span><?php echo e($label); ?></span>
            </a>
        <?php endforeach; ?>

        <div class="nav-section-title dashboard-nav-spacing">APPOINTMENTS</div>
        <a href="bookings.php" class="sidebar-link <?php echo $admin_page === 'bookings.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">▤</span><span>Bookings</span>
        </a>
        <a href="schedules.php" class="sidebar-link <?php echo $admin_page === 'schedules.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">▣</span><span>Schedules</span>
        </a>

        <div class="nav-section-title dashboard-nav-spacing">HEALTH RECORDS</div>
        <a href="vaccination_records.php" class="sidebar-link <?php echo $admin_page === 'vaccination_records.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">✓</span><span>Vaccination Records</span>
        </a>

        <div class="nav-section-title dashboard-nav-spacing">ACCOUNT</div>
        <a href="profile.php" class="sidebar-link <?php echo $admin_page === 'profile.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">◯</span><span>Profile</span>
        </a>
    </nav>
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link"><span class="sidebar-icon">↪</span><span>Logout</span></a>
    </div>
</aside>
