<?php

session_start();

include("../config/db.php");

// Check whether user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

// Check whether the logged-in user is a parent
if ($_SESSION["role"] !== "parent") {
    header("Location: ../login.php");
    exit();
}

$parent_id = $_SESSION["user_id"];

$message = "";
$message_type = "";

if (isset($_POST["add_child"])) {

    $child_name = trim($_POST["child_name"]);
    $date_of_birth = $_POST["date_of_birth"];
    $gender = $_POST["gender"];
    $blood_group = $_POST["blood_group"];
    $address = trim($_POST["address"]);

    if (
        empty($child_name) ||
        empty($date_of_birth) ||
        empty($gender) ||
        empty($blood_group) ||
        empty($address)
    ) {

        $message = "Please fill in all fields.";
        $message_type = "error";

    } else {

        $query = "INSERT INTO children 
                  (parent_id, child_name, date_of_birth, gender, blood_group, address)
                  VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);

        mysqli_stmt_bind_param(
            $stmt,
            "isssss",
            $parent_id,
            $child_name,
            $date_of_birth,
            $gender,
            $blood_group,
            $address
        );

        if (mysqli_stmt_execute($stmt)) {

            $message = "Child registered successfully!";
            $message_type = "success";

        } else {

            $message = "Something went wrong. Please try again.";
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Child | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <div class="dashboard-main">

        <div class="dashboard-content">

            <div class="dashboard-card">

                <div class="card-header">

                    <div>
                        <h3>Register Your Child</h3>
                        <p>Add your child's information to ImmuniCare</p>
                    </div>

                </div>

                <?php if ($message != ""): ?>

                    <div class="<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                <?php endif; ?>


                <form method="POST">

                    <div>
                        <label>Child Name</label>

                        <input
                            type="text"
                            name="child_name"
                            placeholder="Enter child's full name"
                            required
                        >
                    </div>


                    <div>
                        <label>Date of Birth</label>

                        <input
                            type="date"
                            name="date_of_birth"
                            required
                        >
                    </div>


                    <div>
                        <label>Gender</label>

                        <select name="gender" required>

                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>

                        </select>

                    </div>


                    <div>
                        <label>Blood Group</label>

                        <select name="blood_group" required>

                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>

                        </select>

                    </div>


                    <div>
                        <label>Address</label>

                        <textarea
                            name="address"
                            placeholder="Enter child's address"
                            rows="4"
                            required
                        ></textarea>

                    </div>


                    <button type="submit" name="add_child">
                        Register Child
                    </button>

                </form>

            </div>

        </div>

    </div>

</body>

</html>