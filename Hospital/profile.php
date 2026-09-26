<?php

require_once "../includes/app.php";

$user = require_role($conn, "hospital");
$user_id = (int)$user["id"];
$name = $user["name"];
$hospital_profile = null;
$profile_error = "";

/*
    The logged-in account is stored in $_SESSION["user_id"].
    This query connects users.id to hospitals.user_id, so the page
    only loads the hospital profile that belongs to this account.
*/
$profile_query = "SELECT
                    users.id AS user_id,
                    users.name,
                    users.email,
                    users.role,
                    users.created_at AS account_created_at,
                    hospitals.id AS hospital_id,
                    hospitals.hospital_name,
                    hospitals.phone,
                    hospitals.address,
                    hospitals.city,
                    hospitals.location,
                    hospitals.status,
                    hospitals.created_at AS hospital_created_at
                  FROM users
                  INNER JOIN hospitals
                  ON users.id = hospitals.user_id
                  WHERE users.id = ?
                  AND users.role = 'hospital'
                  LIMIT 1";

$profile_stmt = mysqli_prepare($conn, $profile_query);

if ($profile_stmt) {
    mysqli_stmt_bind_param($profile_stmt, "i", $user_id);
    mysqli_stmt_execute($profile_stmt);
    $profile_result = mysqli_stmt_get_result($profile_stmt);
    $hospital_profile = mysqli_fetch_assoc($profile_result);
    mysqli_stmt_close($profile_stmt);

    if (!$hospital_profile) {
        $profile_error = "Hospital profile not found.";
    }
} else {
    $profile_error = "Unable to load hospital profile right now.";
}

function display_value($value)
{
    if ($value === null || $value === "") {
        return "Not provided";
    }

    return htmlspecialchars($value);
}

function display_date($date)
{
    if ($date === null || $date === "") {
        return "Not provided";
    }

    return date("F d, Y", strtotime($date));
}

$display_name = $hospital_profile ? $hospital_profile["hospital_name"] : $name;
$status_text = $hospital_profile ? $hospital_profile["status"] : "";
$status_label = $status_text !== "" ? ucfirst($status_text) : "Not provided";

if ($status_text === "Inactive") {
    http_response_code(403);
    exit("This hospital account is inactive.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="parent-dashboard">

    <!-- ================= SIDEBAR ================= -->
<?php include "sidebar.php"; ?>

    <!-- ================= MAIN CONTENT ================= -->

    <main class="dashboard-main">

        <!-- HEADER -->

        <?php include "../includes/portal_header.php"; ?>

        <!-- PROFILE CONTENT -->

        <section class="dashboard-content">

            <?php if ($profile_error !== ""): ?>

                <div class="profile-error-message">
                    <?php echo htmlspecialchars($profile_error); ?>
                </div>

            <?php else: ?>

                <div class="profile-overview-card">

                    <div class="profile-large-avatar">
                        <?php echo strtoupper(substr($hospital_profile["hospital_name"], 0, 1)); ?>
                    </div>

                    <div class="profile-overview-info">

                        <h2>
                            <?php echo htmlspecialchars($hospital_profile["hospital_name"]); ?>
                        </h2>

                        <p>
                            <?php echo htmlspecialchars($hospital_profile["email"]); ?>
                        </p>

                        <span class="profile-status">
                            ● <?php echo htmlspecialchars($status_label); ?>
                        </span>

                    </div>

                </div>

                <div class="profile-section-card">

                    <div class="profile-section-header">

                        <div>

                            <h2>Hospital Information</h2>

                            <p>
                                Details registered for your hospital
                            </p>

                        </div>

                    </div>

                    <div class="profile-information-grid">

                        <div class="profile-information-item">
                            <span class="profile-information-label">Hospital Name</span>
                            <strong><?php echo display_value($hospital_profile["hospital_name"]); ?></strong>
                        </div>

                        <div class="profile-information-item">
                            <span class="profile-information-label">Phone</span>
                            <strong><?php echo display_value($hospital_profile["phone"]); ?></strong>
                        </div>

                        <div class="profile-information-item">
                            <span class="profile-information-label">Address</span>
                            <strong><?php echo display_value($hospital_profile["address"]); ?></strong>
                        </div>

                        <div class="profile-information-item">
                            <span class="profile-information-label">City</span>
                            <strong><?php echo display_value($hospital_profile["city"]); ?></strong>
                        </div>

                        <div class="profile-information-item">
                            <span class="profile-information-label">Location</span>
                            <strong><?php echo display_value($hospital_profile["location"]); ?></strong>
                        </div>

                        <div class="profile-information-item">
                            <span class="profile-information-label">Hospital Created</span>
                            <strong><?php echo display_date($hospital_profile["hospital_created_at"]); ?></strong>
                        </div>

                    </div>

                </div>

                <div class="profile-section-card">

                    <div class="profile-section-header">

                        <div>

                            <h2>Account Information</h2>

                            <p>
                                Login and account details
                            </p>

                        </div>

                    </div>

                    <div class="profile-information-grid">

                        <div class="profile-information-item">
                            <span class="profile-information-label">Name</span>
                            <strong><?php echo display_value($hospital_profile["name"]); ?></strong>
                        </div>

                        <div class="profile-information-item">
                            <span class="profile-information-label">Email</span>
                            <strong><?php echo display_value($hospital_profile["email"]); ?></strong>
                        </div>

                        <div class="profile-information-item">
                            <span class="profile-information-label">Role</span>
                            <strong><?php echo ucfirst(display_value($hospital_profile["role"])); ?></strong>
                        </div>

                        <div class="profile-information-item">
                            <span class="profile-information-label">Account Status</span>
                            <strong><?php echo htmlspecialchars($status_label); ?></strong>
                        </div>

                        <div class="profile-information-item">
                            <span class="profile-information-label">Account Created</span>
                            <strong><?php echo display_date($hospital_profile["account_created_at"]); ?></strong>
                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>
