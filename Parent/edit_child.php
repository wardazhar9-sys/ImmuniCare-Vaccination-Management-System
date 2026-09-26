<?php
require_once "../includes/app.php";

$user = require_role($conn, "parent");
$parent_id = (int)$user["id"];
$child_id = (int)($_GET["id"] ?? $_POST["child_id"] ?? 0);

if ($child_id <= 0) {
    redirect_to("children.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $child_name = post_string("child_name", 100);
    $date_of_birth = post_string("date_of_birth", 10);
    $gender = post_string("gender", 20);
    $blood_group = post_string("blood_group", 10);
    $address = post_string("address", 500);

    if (
        $child_name === "" || !valid_date($date_of_birth) ||
        $date_of_birth > date("Y-m-d") ||
        !in_array($gender, ["Male", "Female"], true) ||
        !in_array($blood_group, ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"], true) ||
        $address === ""
    ) {
        $error_message = "Enter valid child information.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE children SET child_name = ?, date_of_birth = ?, gender = ?,
             blood_group = ?, address = ?
             WHERE id = ? AND parent_id = ? AND archived_at IS NULL"
        );
        $stmt->bind_param(
            "sssssii", $child_name, $date_of_birth, $gender,
            $blood_group, $address, $child_id, $parent_id
        );
        $updated = $stmt->execute() && $stmt->affected_rows >= 0;
        $stmt->close();

        if ($updated) {
            audit($conn, $parent_id, "child.updated", "child", $child_id);
            redirect_to("children.php");
        }

        $error_message = "Unable to update child information.";
    }
}

$stmt = $conn->prepare(
    "SELECT id, child_name, date_of_birth, gender, blood_group, address
     FROM children WHERE id = ? AND parent_id = ? AND archived_at IS NULL"
);
$stmt->bind_param("ii", $child_id, $parent_id);
$stmt->execute();
$child = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$child) {
    redirect_to("children.php");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Child - ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">
<script src="../assets/js/form-validation.js" defer></script>

    <style>

.edit-child-container {
    max-width: 900px;
    margin: 50px auto;
    padding: 0 25px 50px;
}

/* Page Header */

.edit-page-header {
    margin-bottom: 30px;
}

.edit-page-title {
    font-size: 32px;
    color: #0B1F3A;
    margin: 0 0 8px;
    font-weight: 700;
}

.edit-page-subtitle {
    color: #64748B;
    font-size: 15px;
    margin: 0;
}

/* Form Card */

.edit-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 35px;
    box-shadow: 0 4px 20px rgba(11, 31, 58, 0.08);
}

/* Form Grid */

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-size: 14px;
    font-weight: 600;
    color: #0F172A;
    margin-bottom: 8px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 12px 14px;
    border: 1px solid #E2E8F0;
    border-radius: 9px;
    font-size: 14px;
    font-family: inherit;
    color: #0F172A;
    background: #FFFFFF;
    outline: none;
    transition: 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.10);
}

.form-group textarea {
    min-height: 110px;
    resize: vertical;
}

/* Buttons */

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #E2E8F0;
}

.cancel-btn,
.save-btn {
    padding: 12px 22px;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: 0.2s ease;
}

.cancel-btn {
    color: #475569;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
}

.cancel-btn:hover {
    background: #F1F5F9;
}

.save-btn {
    color: #FFFFFF;
    background: #1E40AF;
    border: 1px solid #1E40AF;
}

.save-btn:hover {
    background: #0B1F3A;
}

/* Responsive */

@media (max-width: 700px) {

    .edit-child-container {
        margin-top: 30px;
        padding: 0 15px 40px;
    }

    .edit-card {
        padding: 22px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full-width {
        grid-column: auto;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .cancel-btn,
    .save-btn {
        width: 100%;
        text-align: center;
        box-sizing: border-box;
    }
}

</style>

</head>



  <body>

<div class="edit-child-container">

    <div class="edit-page-header">
        <h1 class="edit-page-title">Edit Child</h1>
        <p class="edit-page-subtitle">
            Update your child's information.
        </p>
    </div>

    <div class="edit-card">

        <form method="POST">
            <?php echo csrf_field(); ?>

            <div class="form-grid">

                <!-- Child Name -->
                <div class="form-group">
                    <label>Child Name</label>

                    <input
                        type="text"
                        id="child_name"
                        name="child_name"
                        value="<?php echo htmlspecialchars($child['child_name']); ?>"
                        maxlength="100"
                        required
                    >
                </div>


                <!-- Date of Birth -->
                <div class="form-group">
                    <label>Date of Birth</label>

                    <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        value="<?php echo htmlspecialchars($child['date_of_birth']); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        required
                    >
                </div>


                <!-- Gender -->
                <div class="form-group">
                    <label>Gender</label>

                    <select id="gender" name="gender" required>

                        <option value="Male"
                            <?php if ($child['gender'] == 'Male') echo 'selected'; ?>>
                            Male
                        </option>

                        <option value="Female"
                            <?php if ($child['gender'] == 'Female') echo 'selected'; ?>>
                            Female
                        </option>

                    </select>
                </div>


                <!-- Blood Group -->
                <div class="form-group">
                    <label>Blood Group</label>

                    <select id="blood_group" name="blood_group" required>

                        <option value="A+" <?php if ($child['blood_group'] == 'A+') echo 'selected'; ?>>A+</option>
                        <option value="A-" <?php if ($child['blood_group'] == 'A-') echo 'selected'; ?>>A-</option>
                        <option value="B+" <?php if ($child['blood_group'] == 'B+') echo 'selected'; ?>>B+</option>
                        <option value="B-" <?php if ($child['blood_group'] == 'B-') echo 'selected'; ?>>B-</option>
                        <option value="AB+" <?php if ($child['blood_group'] == 'AB+') echo 'selected'; ?>>AB+</option>
                        <option value="AB-" <?php if ($child['blood_group'] == 'AB-') echo 'selected'; ?>>AB-</option>
                        <option value="O+" <?php if ($child['blood_group'] == 'O+') echo 'selected'; ?>>O+</option>
                        <option value="O-" <?php if ($child['blood_group'] == 'O-') echo 'selected'; ?>>O-</option>

                    </select>
                </div>


                <!-- Address -->
                <div class="form-group full-width">

                    <label>Address</label>

                    <textarea
                        id="address"
                        name="address"
                        maxlength="500"
                        required
                    ><?php echo htmlspecialchars($child['address']); ?></textarea>

                </div>

            </div>


            <!-- Buttons -->

            <div class="form-actions">

                <a href="children.php" class="cancel-btn">
                    Cancel
                </a>

                <button type="submit" class="save-btn">
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>



</body>

</html>