<?php
require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_user"])) {
    verify_csrf();
    $user_id = post_int("user_id");
    $name = post_string("name", 100);
    $email = post_string("email", 150);
    $role = post_string("role", 20);
    $status = post_string("status", 20);

    if ($user_id <= 0 || $name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !in_array($role, ["parent", "hospital", "admin"], true) ||
        !in_array($status, ["Active", "Inactive"], true)) {
        flash_set("Enter valid user information.", "error");
    } elseif ($user_id === $admin_id && ($role !== "admin" || $status !== "Active")) {
        flash_set("You cannot deactivate or demote your own Admin account.", "error");
    } else {
        $current_stmt = $conn->prepare("SELECT role, status FROM users WHERE id = ?");
        $current_stmt->bind_param("i", $user_id);
        $current_stmt->execute();
        $current = $current_stmt->get_result()->fetch_assoc();
        $current_stmt->close();

        if (!$current) {
            flash_set("User not found.", "error");
            redirect_to("users.php");
        }
        if ($role !== $current["role"]) {
            flash_set("Role changes require a dedicated onboarding workflow.", "error");
            redirect_to("users.php");
        }
        if ($current["role"] === "admin" && $status === "Inactive") {
            $count_stmt = $conn->prepare(
                "SELECT COUNT(*) AS total FROM users WHERE role = 'admin' AND status = 'Active'"
            );
            $count_stmt->execute();
            $active_admins = (int)$count_stmt->get_result()->fetch_assoc()["total"];
            $count_stmt->close();
            if ($active_admins <= 1) {
                flash_set("The last active Admin cannot be deactivated.", "error");
                redirect_to("users.php");
            }
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $duplicate = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($duplicate) {
            flash_set("This email address is already in use.", "error");
        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?"
            );
            $stmt->bind_param("ssssi", $name, $email, $role, $status, $user_id);
            $updated = $stmt->execute();
            $stmt->close();
            flash_set($updated ? "User updated." : "Unable to update user.", $updated ? "success" : "error");
            if ($updated && $user_id === $admin_id) {
                $_SESSION["name"] = $name;
                $_SESSION["role"] = $role;
            }
            audit($conn, $admin_id, "user.updated", "user", $user_id);
        }
    }
    redirect_to("users.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_user_status"])) {
    verify_csrf();
    $user_id = post_int("status_user_id");
    $new_status = post_string("new_status", 20);

    if ($user_id === $admin_id || !in_array($new_status, ["Active", "Inactive"], true)) {
        flash_set("Invalid account status change.", "error");
    } else {
        $role_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $role_stmt->bind_param("i", $user_id);
        $role_stmt->execute();
        $target = $role_stmt->get_result()->fetch_assoc();
        $role_stmt->close();
        if (!$target) {
            flash_set("User not found.", "error");
            redirect_to("users.php");
        }
        if ($target["role"] === "admin" && $new_status === "Inactive") {
            $count_stmt = $conn->prepare(
                "SELECT COUNT(*) AS total FROM users WHERE role = 'admin' AND status = 'Active'"
            );
            $count_stmt->execute();
            $active_admins = (int)$count_stmt->get_result()->fetch_assoc()["total"];
            $count_stmt->close();
            if ($active_admins <= 1) {
                flash_set("The last active Admin cannot be deactivated.", "error");
                redirect_to("users.php");
            }
        }
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $user_id);
        $updated = $stmt->execute();
        $stmt->close();
        if ($updated) {
            notify_user(
                $conn,
                $user_id,
                "Account status updated",
                "Your ImmuniCare account is now " . $new_status . ".",
                "account",
                "../" . ($user_id === $admin_id ? "Admin/profile.php" : "login.php")
            );
        }
        flash_set($updated ? "Account status updated." : "Unable to update account status.", $updated ? "success" : "error");
        audit($conn, $admin_id, "user.status_changed", "user", $user_id, ["status" => $new_status]);
    }
    redirect_to("users.php");
}

$search = trim((string)($_GET["search"] ?? ""));
$role_filter = trim((string)($_GET["role"] ?? ""));
$sql = "SELECT id, name, email, role, status, created_at FROM users WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $value = "%$search%";
    $params = [$value, $value];
    $types = "ss";
}

if (in_array($role_filter, ["parent", "hospital", "admin"], true)) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
} else {
    $role_filter = "";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $bind = [$types];
    foreach ($params as $key => $value) {
        $bind[] = &$params[$key];
    }
    call_user_func_array([$stmt, "bind_param"], $bind);
}
$stmt->execute();
$result = $stmt->get_result();
$flash = flash_get();

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Users | ImmuniCare Admin</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>


<body class="dashboard-body">

<div class="dashboard-layout">


    <!-- =================================================
         SIDEBAR
    ================================================= -->
<?php include "sidebar.php"; ?>



    <!-- =================================================
         MAIN CONTENT
    ================================================= -->

    <main class="dashboard-main">


        <!-- HEADER -->

<?php include "../includes/portal_header.php"; ?>


        <!-- PAGE CONTENT -->

        <div class="dashboard-content">


            <!-- PAGE TITLE -->

            <div class="page-heading">

                <div>

                    <h2>
                        All Users
                    </h2>

                    <p>
                        View and manage registered Parent, Hospital and Admin accounts.
                    </p>

                </div>

            </div>



            <!-- SEARCH / FILTER -->

            <div class="users-toolbar">


                <form
                    method="GET"
                    action=""
                    class="users-search-form"
                >

                    <input
                        type="text"
                        name="search"
                        placeholder="Search by name or email..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >


                    <select name="role">

                        <option value="">
                            All Roles
                        </option>

                        <option
                            value="parent"
                            <?php echo ($role_filter === "parent") ? "selected" : ""; ?>
                        >
                            Parent
                        </option>

                        <option
                            value="hospital"
                            <?php echo ($role_filter === "hospital") ? "selected" : ""; ?>
                        >
                            Hospital
                        </option>

                        <option
                            value="admin"
                            <?php echo ($role_filter === "admin") ? "selected" : ""; ?>
                        >
                            Admin
                        </option>

                    </select>


                    <button type="submit">
                        Search
                    </button>


                    <?php if ($search !== "" || $role_filter !== "") { ?>

                        <a href="users.php">
                            Clear
                        </a>

                    <?php } ?>

                </form>


            </div>



            <!-- USERS TABLE -->

            <div class="users-card">


                <div class="users-card-header">

                    <div>

                        <h3>
                            Registered Users
                        </h3>

                        <p>
                            All accounts currently registered in ImmuniCare
                        </p>

                    </div>

                </div>


                <div class="users-table-wrapper">


                    <table class="users-table">


                        <thead>

                            <tr>

                             <th>#</th>
                             <th>Name</th>
                             <th>Email</th>
                             <th>Role</th>
                             <th>Status</th>
                             <th>Registered</th>
                             <th>Actions</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (mysqli_num_rows($result) > 0) { ?>


                            <?php while ($user = mysqli_fetch_assoc($result)) { ?>


                                <tr>


                                    <td>
                                        <?php echo $user["id"]; ?>
                                    </td>


                                    <td>

                                        <div class="user-name-cell">

                                            <div class="user-table-avatar">

                                                <?php
                                                echo strtoupper(
                                                    substr($user["name"], 0, 1)
                                                );
                                                ?>

                                            </div>


                                            <strong>

                                                <?php
                                                echo htmlspecialchars(
                                                    $user["name"]
                                                );
                                                ?>

                                            </strong>

                                        </div>

                                    </td>


                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $user["email"]
                                        );
                                        ?>

                                    </td>

<td>

    <?php

    $role_class =
        strtolower($user["role"]);

    ?>

    <span
        class="user-role <?php echo $role_class; ?>"
    >

        <?php
        echo ucfirst(
            htmlspecialchars(
                $user["role"]
            )
        );
        ?>

    </span>

</td>


<!-- STATUS -->

<td>

    <?php
    $status_class = strtolower($user["status"]);
    ?>

    <span class="user-status <?php echo $status_class; ?>">
        <?php echo htmlspecialchars($user["status"]); ?>
    </span>

</td>


<!-- REGISTERED -->

<td>

    <?php

    echo date(
        "d M Y",
        strtotime(
            $user["created_at"]
        )
    );

    ?>

</td>

<!-- ACTIONS -->

<td>

    <div class="user-actions">


        <!-- EDIT BUTTON -->

        <button
            type="button"
            class="user-action-edit"
            onclick="openEditUserModal(
                <?php echo $user['id']; ?>,
                <?php echo htmlspecialchars(json_encode($user['name'])); ?>,
                <?php echo htmlspecialchars(json_encode($user['email'])); ?>,
                <?php echo htmlspecialchars(json_encode($user['role'])); ?>,
                <?php echo htmlspecialchars(json_encode($user['status'])); ?>
            )"
        >
            Edit
        </button>


        <!-- ACTIVATE / DEACTIVATE -->

        <?php if ($user["id"] != $_SESSION["user_id"]) { ?>

            <?php if (strtolower($user["status"]) === "active") { ?>

                <button
                    type="button"
                    class="user-action-deactivate"
                    onclick="openStatusModal(
                        <?php echo $user['id']; ?>,
                        <?php echo htmlspecialchars(json_encode($user['name'])); ?>,
                        'Inactive'
                    )"
                >
                    Deactivate
                </button>

            <?php } else { ?>

                <button
                    type="button"
                    class="user-action-activate"
                    onclick="openStatusModal(
                        <?php echo $user['id']; ?>,
                        <?php echo htmlspecialchars(json_encode($user['name'])); ?>,
                        'Active'
                    )"
                >
                    Activate
                </button>

            <?php } ?>

        <?php } ?>


    </div>

</td>


                       

</tr>


<?php } ?>

<?php } else { ?>

<tr>

<td colspan="7" class="users-empty"> No users found. </td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

 </div>

</div>

</main>

</div>

<!-- EDIT USER MODAL -->

<div id="editUserModal" class="user-modal-overlay">

    <div class="user-modal">

        <!-- HEADER -->

        <div class="user-modal-header">

            <div>
                <h2>Edit User</h2>
                <p>Update the user's account information.</p>
            </div>

            <button
                type="button"
                class="user-modal-close"
                onclick="closeEditUserModal()"
            >
                &times;
            </button>

        </div>


        <!-- FORM -->

        <form method="POST">
            <?php echo csrf_field(); ?>

            <input
                type="hidden"
                name="update_user"
                value="1"
            >

            <input
                type="hidden"
                id="edit_user_id"
                name="user_id"
            >


            <div class="user-modal-body">

                <!-- NAME -->

                <div class="user-form-group">

                    <label for="edit_user_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="edit_user_name"
                        name="name"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="user-form-group">

                    <label for="edit_user_email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="edit_user_email"
                        name="email"
                        required
                    >

                </div>


                <!-- ROLE -->

                <div class="user-form-group">

                    <label for="edit_user_role">
                        Account Role
                    </label>

                    <select
                        id="edit_user_role"
                        name="role"
                        required
                    >

                        <option value="parent">
                            Parent
                        </option>

                        <option value="hospital">
                            Hospital
                        </option>

                        <option value="admin">
                            Admin
                        </option>

                    </select>

                </div>


                <!-- STATUS -->

                <div class="user-form-group">

                    <label for="edit_user_status">
                        Account Status
                    </label>

                    <select
                        id="edit_user_status"
                        name="status"
                        required
                    >

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
                            Inactive
                        </option>

                    </select>

                </div>

            </div>


            <!-- FOOTER -->

            <div class="user-modal-footer">

                <button
                    type="button"
                    class="user-modal-cancel"
                    onclick="closeEditUserModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="user-modal-save"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

<!-- =========================================================
     ACTIVATE / DEACTIVATE USER MODAL
========================================================= -->

<div
    id="userStatusModal"
    class="user-modal-overlay"
>

    <div class="user-status-modal">


        <!-- HEADER -->

        <div class="user-status-modal-header">

            <div
                class="user-status-modal-icon"
                id="userStatusModalIcon"
            >
                !
            </div>

            <button
                type="button"
                class="user-modal-close"
                onclick="closeStatusModal()"
            >
                &times;
            </button>

        </div>


        <!-- CONTENT -->

        <div class="user-status-modal-body">

            <h2 id="userStatusModalTitle">
                Deactivate User?
            </h2>

            <p id="userStatusModalMessage">
                Are you sure you want to deactivate this user?
            </p>


            <!-- FORM -->

            <form method="POST">
                <?php echo csrf_field(); ?>

                <input
                    type="hidden"
                    name="change_user_status"
                    value="1"
                >

                <input
                    type="hidden"
                    id="status_user_id"
                    name="status_user_id"
                >

                <input
                    type="hidden"
                    id="new_user_status"
                    name="new_status"
                >


                <div class="user-status-modal-actions">

                    <button
                        type="button"
                        class="user-modal-cancel"
                        onclick="closeStatusModal()"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        id="userStatusConfirmButton"
                        class="user-status-confirm deactivate"
                    >
                        Deactivate User
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

function openEditUserModal(id, name, email, role, status) {

    document.getElementById("edit_user_id").value = id;

    document.getElementById("edit_user_name").value = name;

    document.getElementById("edit_user_email").value = email;

    document.getElementById("edit_user_role").value = role;

    document.getElementById("edit_user_status").value = status;

    document
        .getElementById("editUserModal")
        .classList.add("show");
}


function closeEditUserModal() {

    document
        .getElementById("editUserModal")
        .classList.remove("show");
}


document
    .getElementById("editUserModal")
    .addEventListener("click", function(event) {

        if (event.target === this) {

            closeEditUserModal();

        }

    });

    /* =========================================================
   ACTIVATE / DEACTIVATE USER MODAL
========================================================= */

function openStatusModal(id, name, newStatus) {

    document.getElementById("status_user_id").value = id;

    document.getElementById("new_user_status").value = newStatus;


    const title =
        document.getElementById("userStatusModalTitle");

    const message =
        document.getElementById("userStatusModalMessage");

    const confirmButton =
        document.getElementById("userStatusConfirmButton");

    const icon =
        document.getElementById("userStatusModalIcon");


    if (newStatus === "Inactive") {

        title.textContent =
            "Deactivate User?";

        message.textContent =
            "Are you sure you want to deactivate " +
            name +
            "?";

        confirmButton.textContent =
            "Deactivate User";

        confirmButton.classList.remove("activate");

        confirmButton.classList.add("deactivate");

        icon.textContent = "!";

    } else {

        title.textContent =
            "Activate User?";

        message.textContent =
            "Are you sure you want to activate " +
            name +
            "?";

        confirmButton.textContent =
            "Activate User";

        confirmButton.classList.remove("deactivate");

        confirmButton.classList.add("activate");

        icon.textContent = "✓";

    }


    document
        .getElementById("userStatusModal")
        .classList.add("show");
}


function closeStatusModal() {

    document
        .getElementById("userStatusModal")
        .classList.remove("show");
}

document
    .getElementById("userStatusModal")
    .addEventListener("click", function(event) {

        if (event.target === this) {

            closeStatusModal();

        }

    });

</script>

</body>

</html>


<?php

mysqli_stmt_close($stmt);

?>