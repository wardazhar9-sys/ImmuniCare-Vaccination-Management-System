<?php

require_once "../includes/app.php";
$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];

/* =========================================================
   EDIT HOSPITAL
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_hospital"])) {
    verify_csrf();

    $hospital_id = intval($_POST["hospital_id"]);

    $hospital_name = trim($_POST["hospital_name"]);
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);
    $city = trim($_POST["city"]);
    $location = trim($_POST["location"]);

    if (
        $hospital_id <= 0 ||
        $hospital_name === "" ||
        $phone === "" ||
        $address === "" ||
        $city === "" ||
        $location === ""
    ) {

        $_SESSION["hospital_message"] =
            "Please fill in all hospital details.";

        $_SESSION["hospital_message_type"] =
            "error";

        header("Location: hospitals.php");
        exit();
    }


    $update_sql = "
        UPDATE hospitals
        SET
            hospital_name = ?,
            phone = ?,
            address = ?,
            city = ?,
            location = ?
        WHERE id = ?
    ";

    $update_stmt = mysqli_prepare($conn, $update_sql);

    mysqli_stmt_bind_param(
        $update_stmt,
        "sssssi",
        $hospital_name,
        $phone,
        $address,
        $city,
        $location,
        $hospital_id
    );


    if (mysqli_stmt_execute($update_stmt)) {

        $_SESSION["hospital_message"] =
            "Hospital information updated successfully.";

        $_SESSION["hospital_message_type"] =
            "success";

    } else {

        $_SESSION["hospital_message"] =
            "Unable to update hospital information.";

        $_SESSION["hospital_message_type"] =
            "error";
    }


    mysqli_stmt_close($update_stmt);

    header("Location: hospitals.php");
    exit();
}


/* =========================================================
   CHANGE HOSPITAL STATUS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["change_hospital_status"])
) {
    verify_csrf();

    $hospital_id = intval($_POST["status_hospital_id"]);

    $new_status = trim($_POST["new_status"]);


    if (
        $hospital_id <= 0 ||
        !in_array($new_status, ["Active", "Inactive"])
    ) {

        $_SESSION["hospital_message"] =
            "Invalid hospital status.";

        $_SESSION["hospital_message_type"] =
            "error";

        header("Location: hospitals.php");
        exit();
    }


    $status_sql = "
        UPDATE hospitals
        SET status = ?, verified_at = IF(? = 'Active', NOW(), NULL),
            verification_status = IF(? = 'Active', 'Approved', 'Rejected')
        WHERE id = ?
    ";

    $status_stmt = mysqli_prepare($conn, $status_sql);

    mysqli_stmt_bind_param(
        $status_stmt,
        "sssi",
        $new_status,
        $new_status,
        $new_status,
        $hospital_id
    );


    if (mysqli_stmt_execute($status_stmt)) {
        $user_stmt = mysqli_prepare($conn, "SELECT user_id FROM hospitals WHERE id = ?");
        mysqli_stmt_bind_param($user_stmt, "i", $hospital_id);
        mysqli_stmt_execute($user_stmt);
        $hospital_user = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
        mysqli_stmt_close($user_stmt);

        if ($hospital_user) {
            notify_user(
                $conn,
                (int)$hospital_user["user_id"],
                "Hospital status updated",
                "Your hospital account status is now " . $new_status . ".",
                "account",
                "Hospital/profile.php"
            );
        }
        audit($conn, $admin_id, "hospital.status_changed", "hospital", $hospital_id, ["status" => $new_status]);

        $_SESSION["hospital_message"] =
            "Hospital status updated successfully.";

        $_SESSION["hospital_message_type"] =
            "success";

    } else {

        $_SESSION["hospital_message"] =
            "Unable to update hospital status.";

        $_SESSION["hospital_message_type"] =
            "error";
    }


    mysqli_stmt_close($status_stmt);

    header("Location: hospitals.php");
    exit();
}


/* =========================================================
   SEARCH
========================================================= */

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";


/* =========================================================
   GET HOSPITALS
========================================================= */

$sql = "SELECT
            id,
            user_id,
            hospital_name,
            phone,
            address,
            city,
            location,
            status,
            created_at
        FROM hospitals
        WHERE 1=1";


$params = [];
$types = "";


/* =========================================================
   SEARCH HOSPITAL
========================================================= */

if ($search !== "") {

    $sql .= " AND (
                hospital_name LIKE ?
                OR phone LIKE ?
                OR address LIKE ?
                OR city LIKE ?
                OR location LIKE ?
            )";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sssss";
}


/* =========================================================
   ORDER
========================================================= */

$sql .= " ORDER BY created_at DESC";


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

    <title>Hospitals | ImmuniCare Admin</title>

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
                        All Hospitals
                    </h2>

                    <p>
                        View and manage registered hospitals in the ImmuniCare system.
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
                        placeholder="Search by hospital name or city..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >


                    <button type="submit">
                        Search
                    </button>


                    <?php if ($search !== "") { ?>

                        <a href="hospitals.php">
                            Clear
                        </a>

                    <?php } ?>


                </form>


            </div>



            <!-- HOSPITALS TABLE -->

            <div class="users-card">


                <div class="users-card-header">

                    <div>

                        <h3>
                            Registered Hospitals
                        </h3>

                        <p>
                            All hospitals currently registered in ImmuniCare
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
                                    Hospital
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Address
                                </th>

                                <th>
                                    City
                                </th>

                                <th>
                                    Location
                                </th>

                                <th>
                                    Status
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


                                <?php while ($hospital = mysqli_fetch_assoc($result)) { ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <?php
                                            echo $hospital["id"];
                                            ?>

                                        </td>



                                        <!-- HOSPITAL -->

                                        <td>


                                            <div class="user-name-cell">


                                                <div class="user-table-avatar">

                                                    <?php

                                                    echo strtoupper(
                                                        substr(
                                                            $hospital["hospital_name"],
                                                            0,
                                                            1
                                                        )
                                                    );

                                                    ?>

                                                </div>


                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $hospital["hospital_name"]
                                                    );

                                                    ?>

                                                </strong>


                                            </div>


                                        </td>



                                        <!-- PHONE -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $hospital["phone"]
                                            );

                                            ?>

                                        </td>



                                        <!-- ADDRESS -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $hospital["address"]
                                            );

                                            ?>

                                        </td>



                                        <!-- CITY -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $hospital["city"]
                                            );

                                            ?>

                                        </td>



                                        <!-- LOCATION -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $hospital["location"]
                                            );

                                            ?>

                                        </td>



                                        <!-- STATUS -->

                                        <td>

                                            <?php

                                            $status = strtolower(
                                                trim($hospital["status"])
                                            );

                                            ?>


                                            <?php if ($status === "active") { ?>

                                                <span
                                                    class="user-role hospital"
                                                >
                                                    Active
                                                </span>

                                            <?php } else { ?>

                                                <span
                                                    class="user-role admin"
                                                >
                                                    <?php
                                                    echo ucfirst(
                                                        htmlspecialchars(
                                                            $hospital["status"]
                                                        )
                                                    );
                                                    ?>
                                                </span>

                                            <?php } ?>


                                        </td>



                                        <!-- REGISTERED -->

                                        <td>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $hospital["created_at"]
                                                )
                                            );

                                            ?>

                                        </td>

<!-- ACTIONS -->

<td>

    <div class="user-actions">

        <!-- VIEW -->

        <button
            type="button"
            class="user-action-edit"
            onclick="openViewHospitalModal(
                <?php echo (int)$hospital['id']; ?>,
                <?php echo htmlspecialchars(json_encode($hospital['hospital_name']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($hospital['phone']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($hospital['address']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($hospital['city']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($hospital['location']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($hospital['status']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($hospital['created_at']), ENT_QUOTES, 'UTF-8'); ?>
            )"
        >
            View
        </button>


        <!-- EDIT -->

        <button
            type="button"
            class="user-action-edit"
            onclick="openEditHospitalModal(
                <?php echo (int)$hospital['id']; ?>,
                <?php echo htmlspecialchars(json_encode($hospital['hospital_name']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($hospital['phone']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($hospital['address']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($hospital['city']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($hospital['location']), ENT_QUOTES, 'UTF-8'); ?>
            )"
        >
            Edit
        </button>


        <!-- STATUS -->

        <?php if (strtolower(trim($hospital["status"])) === "active") { ?>

            <button
                type="button"
                class="user-action-deactivate"
                onclick="openHospitalStatusModal(
                    <?php echo (int)$hospital['id']; ?>,
                    <?php echo htmlspecialchars(json_encode($hospital['hospital_name']), ENT_QUOTES, 'UTF-8'); ?>,
                    'Inactive'
                )"
            >
                Deactivate
            </button>

        <?php } else { ?>

            <button
                type="button"
                class="user-action-activate"
                onclick="openHospitalStatusModal(
                    <?php echo (int)$hospital['id']; ?>,
                    <?php echo htmlspecialchars(json_encode($hospital['hospital_name']), ENT_QUOTES, 'UTF-8'); ?>,
                    'Active'
                )"
            >
                Activate
            </button>

        <?php } ?>

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

                                        No hospitals found.

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
     VIEW HOSPITAL MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="viewHospitalModal"
>

    <div class="user-modal child-view-modal">

        <div class="user-modal-header">

            <div>

                <h3>Hospital Details</h3>

                <p>
                    View complete hospital information
                </p>

            </div>

            <button
                type="button"
                class="user-modal-close"
                onclick="closeViewHospitalModal()"
            >
                &times;
            </button>

        </div>


        <div class="user-modal-body">

            <div class="child-details-grid">

                <div class="child-detail-item">

                    <span>
                        Hospital Name
                    </span>

                    <strong id="view_hospital_name"></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Phone
                    </span>

                    <strong id="view_hospital_phone"></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        City
                    </span>

                    <strong id="view_hospital_city"></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Status
                    </span>

                    <strong id="view_hospital_status"></strong>

                </div>


                <div class="child-detail-item child-detail-full">

                    <span>
                        Address
                    </span>

                    <strong id="view_hospital_address"></strong>

                </div>


                <div class="child-detail-item child-detail-full">

                    <span>
                        Location
                    </span>

                    <strong id="view_hospital_location"></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Registered
                    </span>

                    <strong id="view_hospital_registered"></strong>

                </div>

            </div>

        </div>


        <div class="user-modal-footer">

            <button
                type="button"
                class="user-modal-cancel"
                onclick="closeViewHospitalModal()"
            >
                Close
            </button>

        </div>

    </div>

</div>

<!-- =========================================================
     EDIT HOSPITAL MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="editHospitalModal"
>

    <div class="user-modal">

        <div class="user-modal-header">

            <div>

                <h3>Edit Hospital</h3>

                <p>
                    Update the hospital's information
                </p>

            </div>

            <button
                type="button"
                class="user-modal-close"
                onclick="closeEditHospitalModal()"
            >
                &times;
            </button>

        </div>


        <form method="POST">
            <?php echo csrf_field(); ?>

            <div class="user-modal-body">

                <input
                    type="hidden"
                    name="update_hospital"
                    value="1"
                >

                <input
                    type="hidden"
                    id="edit_hospital_id"
                    name="hospital_id"
                >


                <div class="user-form-group">

                    <label for="edit_hospital_name">
                        Hospital Name
                    </label>

                    <input
                        type="text"
                        id="edit_hospital_name"
                        name="hospital_name"
                        required
                    >

                </div>


                <div class="user-form-group">

                    <label for="edit_hospital_phone">
                        Phone
                    </label>

                    <input
                        type="text"
                        id="edit_hospital_phone"
                        name="phone"
                        required
                    >

                </div>


                <div class="user-form-group">

                    <label for="edit_hospital_address">
                        Address
                    </label>

                    <textarea
                        id="edit_hospital_address"
                        name="address"
                        rows="4"
                        placeholder="Enter hospital address..."
                        required
                    ></textarea>

                </div>


                <div class="user-form-row">

                    <div class="user-form-group">

                        <label for="edit_hospital_city">
                            City
                        </label>

                        <input
                            type="text"
                            id="edit_hospital_city"
                            name="city"
                            required
                        >

                    </div>


                    <div class="user-form-group">

                        <label for="edit_hospital_location">
                            Location
                        </label>

                        <input
                            type="text"
                            id="edit_hospital_location"
                            name="location"
                            required
                        >

                    </div>

                </div>

            </div>


            <div class="user-modal-footer">

                <button
                    type="button"
                    class="user-modal-cancel"
                    onclick="closeEditHospitalModal()"
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
     HOSPITAL STATUS MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="hospitalStatusModal"
>

    <div class="user-status-modal">

        <div class="user-status-modal-header">

            <div
                class="user-status-modal-icon"
                id="hospitalStatusModalIcon"
            >
                !
            </div>

            <button
                type="button"
                class="user-modal-close"
                onclick="closeHospitalStatusModal()"
            >
                &times;
            </button>

        </div>


        <div class="user-status-modal-body">

            <h3 id="hospitalStatusModalTitle">
                Deactivate Hospital?
            </h3>

            <p id="hospitalStatusModalMessage">
                Are you sure you want to deactivate this hospital?
            </p>

        </div>


        <form method="POST">
            <?php echo csrf_field(); ?>

            <input
                type="hidden"
                name="change_hospital_status"
                value="1"
            >

            <input
                type="hidden"
                id="status_hospital_id"
                name="status_hospital_id"
            >

            <input
                type="hidden"
                id="new_hospital_status"
                name="new_status"
            >


            <div class="user-status-modal-actions">

                <button
                    type="button"
                    class="user-status-cancel"
                    onclick="closeHospitalStatusModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="user-status-confirm deactivate"
                    id="hospitalStatusConfirmButton"
                >
                    Deactivate Hospital
                </button>

            </div>

        </form>

    </div>

</div>

<script>

/* =========================================================
   VIEW HOSPITAL
========================================================= */

function openViewHospitalModal(
    id,
    hospitalName,
    phone,
    address,
    city,
    location,
    status,
    registered
) {

    document.getElementById("view_hospital_name").textContent =
        hospitalName;

    document.getElementById("view_hospital_phone").textContent =
        phone;

    document.getElementById("view_hospital_address").textContent =
        address;

    document.getElementById("view_hospital_city").textContent =
        city;

    document.getElementById("view_hospital_location").textContent =
        location;

    document.getElementById("view_hospital_status").textContent =
        status;


    const registeredDate = new Date(registered);

    document.getElementById("view_hospital_registered").textContent =
        registeredDate.toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric"
        });


    document
        .getElementById("viewHospitalModal")
        .classList.add("show");
}


function closeViewHospitalModal() {

    document
        .getElementById("viewHospitalModal")
        .classList.remove("show");
}


/* =========================================================
   EDIT HOSPITAL
========================================================= */

function openEditHospitalModal(
    id,
    hospitalName,
    phone,
    address,
    city,
    location
) {

    document.getElementById("edit_hospital_id").value =
        id;

    document.getElementById("edit_hospital_name").value =
        hospitalName;

    document.getElementById("edit_hospital_phone").value =
        phone;

    document.getElementById("edit_hospital_address").value =
        address;

    document.getElementById("edit_hospital_city").value =
        city;

    document.getElementById("edit_hospital_location").value =
        location;


    document
        .getElementById("editHospitalModal")
        .classList.add("show");
}


function closeEditHospitalModal() {

    document
        .getElementById("editHospitalModal")
        .classList.remove("show");
}


/* =========================================================
   HOSPITAL STATUS
========================================================= */

function openHospitalStatusModal(
    id,
    hospitalName,
    newStatus
) {

    document.getElementById("status_hospital_id").value =
        id;

    document.getElementById("new_hospital_status").value =
        newStatus;


    const title =
        document.getElementById("hospitalStatusModalTitle");

    const message =
        document.getElementById("hospitalStatusModalMessage");

    const button =
        document.getElementById("hospitalStatusConfirmButton");

    const icon =
        document.getElementById("hospitalStatusModalIcon");


    if (newStatus === "Inactive") {

        title.textContent =
            "Deactivate Hospital?";

        message.textContent =
            "Are you sure you want to deactivate " +
            hospitalName +
            "?";

        button.textContent =
            "Deactivate Hospital";

        button.classList.remove("activate");

        button.classList.add("deactivate");

        icon.textContent = "!";

    } else {

        title.textContent =
            "Activate Hospital?";

        message.textContent =
            "Are you sure you want to activate " +
            hospitalName +
            "?";

        button.textContent =
            "Activate Hospital";

        button.classList.remove("deactivate");

        button.classList.add("activate");

        icon.textContent = "✓";
    }


    document
        .getElementById("hospitalStatusModal")
        .classList.add("show");
}


function closeHospitalStatusModal() {

    document
        .getElementById("hospitalStatusModal")
        .classList.remove("show");
}


/* =========================================================
   CLOSE MODALS WHEN CLICKING OUTSIDE
========================================================= */

window.addEventListener("click", function(event) {

    if (
        event.target ===
        document.getElementById("viewHospitalModal")
    ) {
        closeViewHospitalModal();
    }


    if (
        event.target ===
        document.getElementById("editHospitalModal")
    ) {
        closeEditHospitalModal();
    }


    if (
        event.target ===
        document.getElementById("hospitalStatusModal")
    ) {
        closeHospitalStatusModal();
    }

});

</script>
</body>

</html>


<?php

mysqli_stmt_close($stmt);

?>