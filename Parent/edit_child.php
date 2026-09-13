<?php

session_start();

include("../config/db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "parent") {
    header("Location: ../login.php");
    exit();
}

$parent_id = $_SESSION["user_id"];

if (!isset($_GET["id"])) {
    header("Location: my_children.php");
    exit();
}

$child_id = $_GET["id"];

$sql = "SELECT id, child_name, date_of_birth, gender, blood_group, address
        FROM children
        WHERE id = '$child_id'
        AND parent_id = '$parent_id'";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: my_children.php");
    exit();
}

$child = mysqli_fetch_assoc($result);


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $child_name = $_POST["child_name"];
    $date_of_birth = $_POST["date_of_birth"];
    $gender = $_POST["gender"];
    $blood_group = $_POST["blood_group"];
    $address = $_POST["address"];


    $update_sql = "UPDATE children
                   SET child_name = ?,
                       date_of_birth = ?,
                       gender = ?,
                       blood_group = ?,
                       address = ?
                   WHERE id = ?
                   AND parent_id = ?";


    $stmt = mysqli_prepare($conn, $update_sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sssssii",
        $child_name,
        $date_of_birth,
        $gender,
        $blood_group,
        $address,
        $child_id,
        $parent_id
    );


    if (mysqli_stmt_execute($stmt)) {

        header("Location: my_children.php");
        exit();

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Child - ImmuniCare</title>

    <link rel="stylesheet" href="../Assets/css/style.css">

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

            <div class="form-grid">

                <!-- Child Name -->
                <div class="form-group">
                    <label>Child Name</label>

                    <input
                        type="text"
                        name="child_name"
                        value="<?php echo htmlspecialchars($child['child_name']); ?>"
                        required
                    >
                </div>


                <!-- Date of Birth -->
                <div class="form-group">
                    <label>Date of Birth</label>

                    <input
                        type="date"
                        name="date_of_birth"
                        value="<?php echo htmlspecialchars($child['date_of_birth']); ?>"
                        required
                    >
                </div>


                <!-- Gender -->
                <div class="form-group">
                    <label>Gender</label>

                    <select name="gender" required>

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

                    <select name="blood_group" required>

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
                        name="address"
                        required
                    ><?php echo htmlspecialchars($child['address']); ?></textarea>

                </div>

            </div>


            <!-- Buttons -->

            <div class="form-actions">

                <a href="my_children.php" class="cancel-btn">
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