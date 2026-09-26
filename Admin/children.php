<?php

require_once "../includes/app.php";
$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];

/* =========================================================
   EDIT CHILD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_child"])) {
    verify_csrf();

    $child_id = intval($_POST["child_id"]);
    $child_name = trim($_POST["child_name"]);
    $date_of_birth = trim($_POST["date_of_birth"]);
    $gender = trim($_POST["gender"]);
    $blood_group = trim($_POST["blood_group"]);
    $address = trim($_POST["address"]);

    if (
        $child_id <= 0 ||
        $child_name === "" ||
        $date_of_birth === "" ||
        $gender === "" ||
        $blood_group === "" ||
        $address === ""
    ) {
        $_SESSION["children_message"] = "Please fill in all child details.";
        $_SESSION["children_message_type"] = "error";

        header("Location: children.php");
        exit();
    }

    $update_sql = "
        UPDATE children
        SET
            child_name = ?,
            date_of_birth = ?,
            gender = ?,
            blood_group = ?,
            address = ?
        WHERE id = ?
    ";

    $update_stmt = mysqli_prepare($conn, $update_sql);

    mysqli_stmt_bind_param(
        $update_stmt,
        "sssssi",
        $child_name,
        $date_of_birth,
        $gender,
        $blood_group,
        $address,
        $child_id
    );

    if (mysqli_stmt_execute($update_stmt)) {

        $_SESSION["children_message"] = "Child information updated successfully.";
        $_SESSION["children_message_type"] = "success";

    } else {

        $_SESSION["children_message"] = "Unable to update child information.";
        $_SESSION["children_message_type"] = "error";
    }

    mysqli_stmt_close($update_stmt);

    header("Location: children.php");
    exit();
}


/* =========================================================
   DELETE CHILD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_child"])) {
    verify_csrf();

    $child_id = intval($_POST["delete_child_id"]);

    if ($child_id <= 0) {

        $_SESSION["children_message"] = "Invalid child selected.";
        $_SESSION["children_message_type"] = "error";

        header("Location: children.php");
        exit();
    }


    /* Check bookings */

    $booking_sql = "
        SELECT id
        FROM bookings
        WHERE child_id = ?
        LIMIT 1
    ";

    $booking_stmt = mysqli_prepare($conn, $booking_sql);

    mysqli_stmt_bind_param(
        $booking_stmt,
        "i",
        $child_id
    );

    mysqli_stmt_execute($booking_stmt);

    mysqli_stmt_store_result($booking_stmt);

    $has_booking = mysqli_stmt_num_rows($booking_stmt) > 0;

    mysqli_stmt_close($booking_stmt);


    /* Check vaccination records */

    $record_sql = "
        SELECT id
        FROM vaccination_records
        WHERE child_id = ?
        LIMIT 1
    ";

    $record_stmt = mysqli_prepare($conn, $record_sql);

    mysqli_stmt_bind_param(
        $record_stmt,
        "i",
        $child_id
    );

    mysqli_stmt_execute($record_stmt);

    mysqli_stmt_store_result($record_stmt);

    $has_record = mysqli_stmt_num_rows($record_stmt) > 0;

    mysqli_stmt_close($record_stmt);


    if ($has_booking || $has_record) {

        $_SESSION["children_message"] =
            "This child cannot be deleted because related booking or vaccination records exist.";

        $_SESSION["children_message_type"] = "error";

        header("Location: children.php");
        exit();
    }


    /* Archive child */

    $delete_sql = "
        UPDATE children SET archived_at = NOW()
        WHERE id = ?
    ";

    $delete_stmt = mysqli_prepare($conn, $delete_sql);

    mysqli_stmt_bind_param(
        $delete_stmt,
        "i",
        $child_id
    );

    if (mysqli_stmt_execute($delete_stmt)) {

        $_SESSION["children_message"] =
            "Child archived successfully.";

        $_SESSION["children_message_type"] =
            "success";

    } else {

        $_SESSION["children_message"] =
            "Unable to delete child.";

        $_SESSION["children_message_type"] =
            "error";
    }

    mysqli_stmt_close($delete_stmt);

    header("Location: children.php");
    exit();
}


/* =========================================================
   SEARCH
========================================================= */

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";


/* =========================================================
   GET CHILDREN
========================================================= */

        $sql = "SELECT
            children.id,
            children.child_name,
            children.date_of_birth,
            children.gender,
            children.blood_group,
            children.address,
            children.created_at,
            users.name AS parent_name,
            users.email AS parent_email
        FROM children
        INNER JOIN users
            ON children.parent_id = users.id
        WHERE children.archived_at IS NULL";


$params = [];
$types = "";


/* =========================================================
   SEARCH CHILD / PARENT
========================================================= */

if ($search !== "") {

    $sql .= " AND (
                children.child_name LIKE ?
                OR users.name LIKE ?
                OR users.email LIKE ?
            )";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sss";
}


/* =========================================================
   ORDER
========================================================= */

$sql .= " ORDER BY children.created_at DESC";


/* =========================================================
   PREPARE
========================================================= */

$stmt = mysqli_prepare($conn, $sql);


/* =========================================================
   BIND PARAMETERS
========================================================= */

if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );

}


/* =========================================================
   EXECUTE
========================================================= */

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Children | ImmuniCare Admin</title>

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
                        All Children
                    </h2>

                    <p>
                        View and manage registered children in the ImmuniCare system.
                    </p>

                </div>

            </div>



            <!-- SEARCH -->

            <div class="users-toolbar">


                <form
                    method="GET"
                    action=""
                    class="users-search-form"
                >


                    <input
                        type="text"
                        name="search"
                        placeholder="Search by child name or parent..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >


                    <button type="submit">
                        Search
                    </button>


                    <?php if ($search !== "") { ?>

                        <a href="children.php">
                            Clear
                        </a>

                    <?php } ?>


                </form>


            </div>



            <!-- CHILDREN TABLE -->

            <div class="users-card">


                <div class="users-card-header">

                    <div>

                        <h3>
                            Registered Children
                        </h3>

                        <p>
                            All children currently registered in ImmuniCare
                        </p>

                    </div>

                </div>



                <div class="users-table-wrapper">


                    <table class="users-table">


                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Child
                                </th>

                                <th>
                                    Parent
                                </th>

                                <th>
                                    Date of Birth
                                </th>

                                <th>
                                    Gender
                                </th>

                                <th>
                                    Blood Group
                                </th>

                                <th>
                                    Address
                                </th>

                                <th>
                                    Registered
                                </th>

                                <th>
                                 Actions
                                </th>

                            </tr>

                        </thead>



                        <tbody>


                            <?php if (mysqli_num_rows($result) > 0) { ?>


                                <?php while ($child = mysqli_fetch_assoc($result)) { ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <?php
                                            echo $child["id"];
                                            ?>

                                        </td>



                                        <!-- CHILD -->

                                        <td>


                                            <div class="user-name-cell">


                                                <div class="user-table-avatar">

                                                    <?php

                                                    echo strtoupper(
                                                        substr(
                                                            $child["child_name"],
                                                            0,
                                                            1
                                                        )
                                                    );

                                                    ?>

                                                </div>


                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $child["child_name"]
                                                    );

                                                    ?>

                                                </strong>


                                            </div>


                                        </td>



                                        <!-- PARENT -->

                                        <td>

                                            <div>

                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $child["parent_name"]
                                                    );

                                                    ?>

                                                </strong>

                                                <br>

                                                <span
                                                    style="
                                                        font-size: 12px;
                                                        color: #94A3B8;
                                                    "
                                                >

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $child["parent_email"]
                                                    );

                                                    ?>

                                                </span>

                                            </div>

                                        </td>



                                        <!-- DATE OF BIRTH -->

                                        <td>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $child["date_of_birth"]
                                                )
                                            );

                                            ?>

                                        </td>



                                        <!-- GENDER -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $child["gender"]
                                            );

                                            ?>

                                        </td>



                                        <!-- BLOOD GROUP -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $child["blood_group"]
                                            );

                                            ?>

                                        </td>



                                        <!-- ADDRESS -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $child["address"]
                                            );

                                            ?>

                                        </td>



                                        <!-- REGISTERED -->

                                        <td>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $child["created_at"]
                                                )
                                            );

                                            ?>

                                        </td>

        <!-- ACTIONS -->

<td>

    <div class="user-actions">

        <button
            type="button"
            class="user-action-edit"
            onclick="openViewChildModal(
                <?php echo (int)$child['id']; ?>,
                <?php echo htmlspecialchars(json_encode($child['child_name']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['parent_name']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['parent_email']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['date_of_birth']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['gender']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['blood_group']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['address']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['created_at']), ENT_QUOTES, 'UTF-8'); ?>
            )"
        >
            View
        </button>


        <button
            type="button"
            class="user-action-edit"
            onclick="openEditChildModal(
                <?php echo (int)$child['id']; ?>,
                <?php echo htmlspecialchars(json_encode($child['child_name']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['date_of_birth']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['gender']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['blood_group']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($child['address']), ENT_QUOTES, 'UTF-8'); ?>
            )"
        >
            Edit
        </button>


        <button
            type="button"
            class="user-action-deactivate"
            onclick="openDeleteChildModal(
                <?php echo (int)$child['id']; ?>,
                <?php echo htmlspecialchars(json_encode($child['child_name']), ENT_QUOTES, 'UTF-8'); ?>
            )"
        >
            Delete
        </button>

    </div>

</td>


                                    </tr>


                                <?php } ?>


                            <?php } else { ?>


                                <tr>

                                    <td
                                        colspan="9"
                                        class="users-empty"
                                    >

                                        No children found.

                                    </td>

                                </tr>


                            <?php } ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>


    </main>


</div>

<!-- =========================================================
     VIEW CHILD MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="viewChildModal"
>

    <div class="user-modal child-view-modal">

        <div class="user-modal-header">

            <div>
                <h3>Child Details</h3>

                <p>
                    View complete child information
                </p>
            </div>

            <button
                type="button"
                class="user-modal-close"
                onclick="closeViewChildModal()"
            >
                &times;
            </button>

        </div>


        <div class="user-modal-body">

            <div class="child-details-grid">

                <div class="child-detail-item">
                    <span>Child Name</span>
                    <strong id="view_child_name"></strong>
                </div>

                <div class="child-detail-item">
                    <span>Parent Name</span>
                    <strong id="view_parent_name"></strong>
                </div>

                <div class="child-detail-item">
                    <span>Parent Email</span>
                    <strong id="view_parent_email"></strong>
                </div>

                <div class="child-detail-item">
                    <span>Date of Birth</span>
                    <strong id="view_child_dob"></strong>
                </div>

                <div class="child-detail-item">
                    <span>Gender</span>
                    <strong id="view_child_gender"></strong>
                </div>

                <div class="child-detail-item">
                    <span>Blood Group</span>
                    <strong id="view_child_blood"></strong>
                </div>

                <div class="child-detail-item child-detail-full">
                    <span>Address</span>
                    <strong id="view_child_address"></strong>
                </div>

                <div class="child-detail-item">
                    <span>Registered</span>
                    <strong id="view_child_registered"></strong>
                </div>

            </div>

        </div>


        <div class="user-modal-footer">

            <button
                type="button"
                class="user-modal-cancel"
                onclick="closeViewChildModal()"
            >
                Close
            </button>

        </div>

    </div>

</div>

<!-- =========================================================
     EDIT CHILD MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="editChildModal"
>

    <div class="user-modal">

        <div class="user-modal-header">

            <div>
                <h3>Edit Child</h3>

                <p>
                    Update the child's information
                </p>
            </div>

            <button
                type="button"
                class="user-modal-close"
                onclick="closeEditChildModal()"
            >
                &times;
            </button>

        </div>


        <form method="POST">
            <?php echo csrf_field(); ?>

            <div class="user-modal-body">

                <input
                    type="hidden"
                    name="update_child"
                    value="1"
                >

                <input
                    type="hidden"
                    id="edit_child_id"
                    name="child_id"
                >


                <div class="user-form-group">

                    <label for="edit_child_name">
                        Child Name
                    </label>

                    <input
                        type="text"
                        id="edit_child_name"
                        name="child_name"
                        required
                    >

                </div>


                <div class="user-form-row">

                    <div class="user-form-group">

                        <label for="edit_child_dob">
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            id="edit_child_dob"
                            name="date_of_birth"
                            required
                        >

                    </div>


                    <div class="user-form-group">

                        <label for="edit_child_gender">
                            Gender
                        </label>

                        <select
                            id="edit_child_gender"
                            name="gender"
                            required
                        >

                            <option value="">
                                Select Gender
                            </option>

                            <option value="Male">
                                Male
                            </option>

                            <option value="Female">
                                Female
                            </option>

                        </select>

                    </div>

                </div>


                <div class="user-form-group">

                    <label for="edit_child_blood">
                        Blood Group
                    </label>

                    <select
                        id="edit_child_blood"
                        name="blood_group"
                        required
                    >

                        <option value="">
                            Select Blood Group
                        </option>

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


                <div class="user-form-group">

                    <label for="edit_child_address">
                        Address
                    </label>

                    <textarea
                    id="edit_child_address"
                    name="address"
                    rows="4"
                    placeholder="Enter child's address..."
                    required>
                </textarea>
                </div>

            </div>


            <div class="user-modal-footer">

                <button
                    type="button"
                    class="user-modal-cancel"
                    onclick="closeEditChildModal()"
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
     DELETE CHILD MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="deleteChildModal"
>

    <div class="user-status-modal">

        <div class="user-status-modal-header">

            <div
                class="user-status-modal-icon"
                id="deleteChildModalIcon"
            >
                !
            </div>

            <button
                type="button"
                class="user-modal-close"
                onclick="closeDeleteChildModal()"
            >
                &times;
            </button>

        </div>


        <div class="user-status-modal-body">

            <h3>
                Delete Child?
            </h3>

            <p id="deleteChildModalMessage">
                Are you sure you want to delete this child?
            </p>

            <p class="delete-warning-text">
                This action cannot be undone. If this child has
                booking or vaccination records, deletion will be
                prevented.
            </p>

        </div>


        <form method="POST">
            <?php echo csrf_field(); ?>

            <input
                type="hidden"
                name="delete_child"
                value="1"
            >

            <input
                type="hidden"
                id="delete_child_id"
                name="delete_child_id"
            >


            <div class="user-status-modal-actions">

                <button
                    type="button"
                    class="user-status-cancel"
                    onclick="closeDeleteChildModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="user-status-confirm deactivate"
                >
                    Delete Child
                </button>

            </div>

        </form>

    </div>

</div>

<script>

function openViewChildModal(
    id,
    childName,
    parentName,
    parentEmail,
    dob,
    gender,
    bloodGroup,
    address,
    registered
) {

    document.getElementById("view_child_name").textContent = childName;

    document.getElementById("view_parent_name").textContent = parentName;

    document.getElementById("view_parent_email").textContent = parentEmail;


    const dobDate = new Date(dob);

    document.getElementById("view_child_dob").textContent =
        dobDate.toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric"
        });


    document.getElementById("view_child_gender").textContent = gender;

    document.getElementById("view_child_blood").textContent = bloodGroup;

    document.getElementById("view_child_address").textContent = address;


    const registeredDate = new Date(registered);

    document.getElementById("view_child_registered").textContent =
        registeredDate.toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric"
        });


    document
        .getElementById("viewChildModal")
        .classList.add("show");
}


function closeViewChildModal() {

    document
        .getElementById("viewChildModal")
        .classList.remove("show");
}



function openEditChildModal(
    id,
    childName,
    dob,
    gender,
    bloodGroup,
    address
) {

    document.getElementById("edit_child_id").value = id;

    document.getElementById("edit_child_name").value = childName;

    document.getElementById("edit_child_dob").value = dob;

    document.getElementById("edit_child_gender").value = gender;

    document.getElementById("edit_child_blood").value = bloodGroup;

    document.getElementById("edit_child_address").value = address;


    document
        .getElementById("editChildModal")
        .classList.add("show");
}


function closeEditChildModal() {

    document
        .getElementById("editChildModal")
        .classList.remove("show");
}



function openDeleteChildModal(
    id,
    childName
) {

    document.getElementById("delete_child_id").value = id;

    document.getElementById("deleteChildModalMessage").textContent =
        "Are you sure you want to delete " +
        childName +
        "?";


    document
        .getElementById("deleteChildModal")
        .classList.add("show");
}


function closeDeleteChildModal() {

    document
        .getElementById("deleteChildModal")
        .classList.remove("show");
}


/* Close modal when clicking outside */

window.addEventListener("click", function(event) {

    if (event.target === document.getElementById("viewChildModal")) {
        closeViewChildModal();
    }

    if (event.target === document.getElementById("editChildModal")) {
        closeEditChildModal();
    }

    if (event.target === document.getElementById("deleteChildModal")) {
        closeDeleteChildModal();
    }

});

</script>

</body>

</html>


<?php

mysqli_stmt_close($stmt);

?>