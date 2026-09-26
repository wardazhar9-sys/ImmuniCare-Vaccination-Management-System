<?php
require_once "../includes/app.php";

$user = require_role($conn, "parent");
$parent_id = (int)$user["id"];
$error_message = "";

if (isset($_POST["update_profile"])) {
    verify_csrf();
    $name = post_string("name", 100);
    $email = post_string("email", 150);

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Enter a valid name and email address.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $stmt->bind_param("si", $email, $parent_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $error_message = "This email address is already in use.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET name = ?, email = ?
                 WHERE id = ? AND role = 'parent'"
            );
            $stmt->bind_param("ssi", $name, $email, $parent_id);
            $updated = $stmt->execute();
            $stmt->close();

            if ($updated) {
                $_SESSION["name"] = $name;
                audit($conn, $parent_id, "profile.updated", "user", $parent_id);
                redirect_to("profile.php?updated=1");
            }

            $error_message = "Unable to update your profile.";
        }
    }
}

$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? AND role = 'parent'");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <div class="parent-dashboard">
        <?php include "sidebar.php"; ?>
        <main class="dashboard-main">

            <?php include "../includes/portal_header.php"; ?>

            <section class="dashboard-content">

            <?php if (isset($error_message)): ?>

    <div class="profile-error-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>

<?php endif; ?>

                <div class="profile-section-card">

                    <div class="profile-section-header">
                        <div>
                            <h2>Personal Information</h2>
                            <p>Update your account details</p>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>

                        <div class="profile-information-grid">

                            <div class="profile-information-item">

                                <label class="profile-information-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    value="<?php echo htmlspecialchars($parent["name"]); ?>"
                                    required
                                >

                            </div>

                            <div class="profile-information-item">

                                <label class="profile-information-label">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($parent["email"]); ?>"
                                    required
>

                            </div>

                        </div>

                        <div class="profile-section-action">

                            <button
                                type="submit"
                                name="update_profile"
                                class="dashboard-primary-btn"
                            >
                                Save Changes
                            </button>

                            <a
                                href="profile.php"
                                class="secondary-profile-btn"
                            >
                                Cancel
                            </a>

                        </div>

                    </form>

                </div>

            </section>

        </main>

    </div>

</body>
</html>