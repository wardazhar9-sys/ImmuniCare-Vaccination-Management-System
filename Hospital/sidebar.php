<?php $hospital_page = basename($_SERVER["PHP_SELF"]); ?>
<aside class="dashboard-sidebar">
    <div class="sidebar-brand">
        <img src="../assets/images/immunicare-logo-hospital-sidebar.svg" alt="ImmuniCare Hospital Portal" class="sidebar-brand-image">
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">MAIN MENU</div>
        <a href="dashboard.php" class="sidebar-link <?php echo $hospital_page === "dashboard.php" ? "active" : ""; ?>"><span class="sidebar-icon">⌂</span><span>Dashboard</span></a>
        <a href="appointments.php" class="sidebar-link <?php echo $hospital_page === "appointments.php" ? "active" : ""; ?>"><span class="sidebar-icon">▤</span><span>Appointments</span></a>
        <a href="vaccinations.php" class="sidebar-link <?php echo $hospital_page === "vaccinations.php" ? "active" : ""; ?>"><span class="sidebar-icon">✓</span><span>Vaccinations</span></a>
        <a href="schedule.php" class="sidebar-link <?php echo $hospital_page === "schedule.php" ? "active" : ""; ?>"><span class="sidebar-icon">▣</span><span>Vaccination Schedule</span></a>
        <a href="slots.php" class="sidebar-link <?php echo $hospital_page === "slots.php" ? "active" : ""; ?>"><span class="sidebar-icon">◷</span><span>Appointment Slots</span></a>
        <div class="nav-section-title dashboard-nav-spacing">ACCOUNT</div>
        <a href="profile.php" class="sidebar-link <?php echo $hospital_page === "profile.php" ? "active" : ""; ?>"><span class="sidebar-icon">◯</span><span>My Profile</span></a>
    </nav>
    <div class="sidebar-bottom"><a href="logout.php" class="logout-link">Logout</a></div>
</aside>
