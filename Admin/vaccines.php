<?php

require_once "../includes/app.php";
$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];


/* =========================================================
   ADD VACCINE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["add_vaccine"])
) {
    verify_csrf();

    $vaccine_name = trim($_POST["vaccine_name"]);
    $description = trim($_POST["description"]);
    $age_group = trim($_POST["age_group"]);
    $dose_number = intval($_POST["dose_number"]);
    $availability = trim($_POST["availability"]);


    if (
        $vaccine_name === ""
        || $description === ""
        || $age_group === ""
        || $dose_number <= 0
        || !in_array($availability, ["Available", "Unavailable"])
    ) {

        $_SESSION["vaccine_message"] =
            "Please fill in all vaccine details correctly.";

        $_SESSION["vaccine_message_type"] =
            "error";

        header("Location: vaccines.php");
        exit();
    }


    /* Check duplicate vaccine name */

    $duplicate_sql = "
        SELECT id
        FROM vaccines
        WHERE vaccine_name = ?
        LIMIT 1
    ";

    $duplicate_stmt = mysqli_prepare(
        $conn,
        $duplicate_sql
    );

    mysqli_stmt_bind_param(
        $duplicate_stmt,
        "s",
        $vaccine_name
    );

    mysqli_stmt_execute($duplicate_stmt);

    mysqli_stmt_store_result($duplicate_stmt);

    if (mysqli_stmt_num_rows($duplicate_stmt) > 0) {

        mysqli_stmt_close($duplicate_stmt);

        $_SESSION["vaccine_message"] =
            "A vaccine with this name already exists.";

        $_SESSION["vaccine_message_type"] =
            "error";

        header("Location: vaccines.php");
        exit();
    }

    mysqli_stmt_close($duplicate_stmt);


    /* Insert vaccine */

    $insert_sql = "
        INSERT INTO vaccines
        (
            vaccine_name,
            description,
            age_group,
            dose_number,
            availability
        )
        VALUES (?, ?, ?, ?, ?)
    ";

    $insert_stmt = mysqli_prepare(
        $conn,
        $insert_sql
    );

    mysqli_stmt_bind_param(
        $insert_stmt,
        "sssis",
        $vaccine_name,
        $description,
        $age_group,
        $dose_number,
        $availability
    );


    if (mysqli_stmt_execute($insert_stmt)) {

        $_SESSION["vaccine_message"] =
            "Vaccine added successfully.";

        $_SESSION["vaccine_message_type"] =
            "success";

    } else {

        $_SESSION["vaccine_message"] =
            "Unable to add vaccine.";

        $_SESSION["vaccine_message_type"] =
            "error";
    }


    mysqli_stmt_close($insert_stmt);

    header("Location: vaccines.php");
    exit();
}


/* =========================================================
   EDIT VACCINE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["update_vaccine"])
) {
    verify_csrf();

    $vaccine_id = intval($_POST["vaccine_id"]);

    $vaccine_name = trim($_POST["vaccine_name"]);
    $description = trim($_POST["description"]);
    $age_group = trim($_POST["age_group"]);
    $dose_number = intval($_POST["dose_number"]);
    $availability = trim($_POST["availability"]);


    if (
        $vaccine_id <= 0
        || $vaccine_name === ""
        || $description === ""
        || $age_group === ""
        || $dose_number <= 0
        || !in_array($availability, ["Available", "Unavailable"])
    ) {

        $_SESSION["vaccine_message"] =
            "Please fill in all vaccine details correctly.";

        $_SESSION["vaccine_message_type"] =
            "error";

        header("Location: vaccines.php");
        exit();
    }


    /* Check duplicate name excluding current vaccine */

    $duplicate_sql = "
        SELECT id
        FROM vaccines
        WHERE vaccine_name = ?
        AND id != ?
        LIMIT 1
    ";

    $duplicate_stmt = mysqli_prepare(
        $conn,
        $duplicate_sql
    );

    mysqli_stmt_bind_param(
        $duplicate_stmt,
        "si",
        $vaccine_name,
        $vaccine_id
    );

    mysqli_stmt_execute($duplicate_stmt);

    mysqli_stmt_store_result($duplicate_stmt);


    if (mysqli_stmt_num_rows($duplicate_stmt) > 0) {

        mysqli_stmt_close($duplicate_stmt);

        $_SESSION["vaccine_message"] =
            "Another vaccine with this name already exists.";

        $_SESSION["vaccine_message_type"] =
            "error";

        header("Location: vaccines.php");
        exit();
    }

    mysqli_stmt_close($duplicate_stmt);


    /* Update vaccine */

    $update_sql = "
        UPDATE vaccines
        SET
            vaccine_name = ?,
            description = ?,
            age_group = ?,
            dose_number = ?,
            availability = ?
        WHERE id = ?
    ";

    $update_stmt = mysqli_prepare(
        $conn,
        $update_sql
    );

    mysqli_stmt_bind_param(
        $update_stmt,
        "sssisi",
        $vaccine_name,
        $description,
        $age_group,
        $dose_number,
        $availability,
        $vaccine_id
    );


    if (mysqli_stmt_execute($update_stmt)) {

        $_SESSION["vaccine_message"] =
            "Vaccine information updated successfully.";

        $_SESSION["vaccine_message_type"] =
            "success";

    } else {

        $_SESSION["vaccine_message"] =
            "Unable to update vaccine information.";

        $_SESSION["vaccine_message_type"] =
            "error";
    }


    mysqli_stmt_close($update_stmt);

    header("Location: vaccines.php");
    exit();
}


/* =========================================================
   CHANGE VACCINE AVAILABILITY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["change_vaccine_availability"])
) {
    verify_csrf();

    $vaccine_id = intval($_POST["availability_vaccine_id"]);

    $new_availability = trim(
        $_POST["new_availability"]
    );


    if (
        $vaccine_id <= 0
        || !in_array(
            $new_availability,
            ["Available", "Unavailable"]
        )
    ) {

        $_SESSION["vaccine_message"] =
            "Invalid vaccine availability.";

        $_SESSION["vaccine_message_type"] =
            "error";

        header("Location: vaccines.php");
        exit();
    }


    $availability_sql = "
        UPDATE vaccines
        SET availability = ?
        WHERE id = ?
    ";

    $availability_stmt = mysqli_prepare(
        $conn,
        $availability_sql
    );

    mysqli_stmt_bind_param(
        $availability_stmt,
        "si",
        $new_availability,
        $vaccine_id
    );


    if (mysqli_stmt_execute($availability_stmt)) {

        $_SESSION["vaccine_message"] =
            "Vaccine availability updated successfully.";

        $_SESSION["vaccine_message_type"] =
            "success";

    } else {

        $_SESSION["vaccine_message"] =
            "Unable to update vaccine availability.";

        $_SESSION["vaccine_message_type"] =
            "error";
    }


    mysqli_stmt_close($availability_stmt);

    header("Location: vaccines.php");
    exit();
}


/* =========================================================
   FLASH MESSAGE
========================================================= */

$message = $_SESSION["vaccine_message"] ?? "";
$message_type = $_SESSION["vaccine_message_type"] ?? "";

unset($_SESSION["vaccine_message"]);
unset($_SESSION["vaccine_message_type"]);


/* =========================================================
   SEARCH
========================================================= */

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";


/* =========================================================
   GET VACCINES
========================================================= */

$sql = "
    SELECT
        id,
        vaccine_name,
        description,
        age_group,
        dose_number,
        availability,
        created_at
    FROM vaccines
    WHERE 1=1
";


$params = [];
$types = "";


/* =========================================================
   SEARCH VACCINES
========================================================= */

if ($search !== "") {

    $sql .= "
        AND (
            vaccine_name LIKE ?
            OR description LIKE ?
            OR age_group LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sss";
}


/* =========================================================
   ORDER
========================================================= */

$sql .= "
    ORDER BY id ASC
";


/* =========================================================
   PREPARE
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    $sql
);


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

    <title>
        Vaccines | ImmuniCare Admin
    </title>

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

    <aside class="dashboard-sidebar">


        <div class="sidebar-brand">

            <img
                src="../assets/images/immunicare-logo-sidebar.svg"
                alt="ImmuniCare Admin Portal"
                class="sidebar-brand-image"
            >

        </div>


        <nav class="sidebar-nav">


            <!-- MAIN MENU -->

            <div class="nav-section-title">
                MAIN MENU
            </div>


            <a
                href="dashboard.php"
                class="sidebar-link"
            >

                <span class="sidebar-icon">
                    ⌂
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="users.php"
                class="sidebar-link"
            >

                <span class="sidebar-icon">
                    ♧
                </span>

                <span>
                    Users
                </span>

            </a>


            <a
                href="children.php"
                class="sidebar-link"
            >

                <span class="sidebar-icon">
                    ♙
                </span>

                <span>
                    Children
                </span>

            </a>


            <a
                href="hospitals.php"
                class="sidebar-link"
            >

                <span class="sidebar-icon">
                    ♜
                </span>

                <span>
                    Hospitals
                </span>

            </a>


            <a
                href="vaccines.php"
                class="sidebar-link active"
            >

                <span class="sidebar-icon">
                    ✚
                </span>

                <span>
                    Vaccines
                </span>

            </a>


            <!-- APPOINTMENTS -->

            <div class="nav-section-title dashboard-nav-spacing">
                APPOINTMENTS
            </div>


            <a
                href="bookings.php"
                class="sidebar-link"
            >

                <span class="sidebar-icon">
                    ▤
                </span>

                <span>
                    Bookings
                </span>

            </a>


            <a
                href="schedules.php"
                class="sidebar-link"
            >

                <span class="sidebar-icon">
                    ▣
                </span>

                <span>
                    Vaccination Schedule
                </span>

            </a>


            <!-- HEALTH RECORDS -->

            <div class="nav-section-title dashboard-nav-spacing">
                HEALTH RECORDS
            </div>


            <a
                href="vaccination_records.php"
                class="sidebar-link"
            >

                <span class="sidebar-icon">
                    ✓
                </span>

                <span>
                    Vaccination Records
                </span>

            </a>


            <!-- ACCOUNT -->

            <div class="nav-section-title dashboard-nav-spacing">
                ACCOUNT
            </div>


            <a
                href="profile.php"
                class="sidebar-link"
            >

                <span class="sidebar-icon">
                    ◯
                </span>

                <span>
                    My Profile
                </span>

            </a>


        </nav>


        <!-- LOGOUT -->

        <div class="sidebar-bottom">

            <a
                href="logout.php"
                class="logout-link"
            >

                <span class="sidebar-icon">
                    ↪
                </span>

                <span>
                    Logout
                </span>

            </a>

        </div>


    </aside>


    <!-- =================================================
         MAIN CONTENT
    ================================================= -->

    <main class="dashboard-main">


        <!-- HEADER -->

        <header class="dashboard-header">


            <div class="header-page-title">

                <h1>
                    Vaccines
                </h1>

                <p>
                    Manage vaccines available in the ImmuniCare system
                </p>

            </div>


            <div class="header-actions">


                <!-- NOTIFICATION -->

                <div class="notification-wrapper">

                    <button
                        type="button"
                        class="notification-button"
                        aria-label="Notifications"
                    >

                        <svg
                            class="notification-bell"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <path
                                d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"
                            ></path>

                            <path d="M10 21h4"></path>

                        </svg>

                    </button>

                </div>


                <div class="header-divider"></div>


                <!-- PROFILE -->

                <div class="profile-mini">

                    <div class="profile-avatar">

                        <?php

                        echo strtoupper(
                            substr(
                                $_SESSION["name"] ?? "I",
                                0,
                                1
                            )
                        );

                        ?>

                    </div>


                    <div class="profile-info">

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $_SESSION["name"]
                                ?? "ImmuniCare Admin"
                            );

                            ?>

                        </strong>

                        <span>
                            Administrator Account
                        </span>

                    </div>

                </div>


            </div>

        </header>


        <!-- PAGE CONTENT -->

        <div class="dashboard-content">


            <!-- PAGE TITLE -->

            <div class="page-heading">

                <div>

                    <h2>
                        All Vaccines
                    </h2>

                    <p>
                        View and manage vaccines available in ImmuniCare.
                    </p>

                </div>


                <button
                    type="button"
                    class="vaccine-add-button"
                    onclick="openAddVaccineModal()"
                >
                    + Add New Vaccine
                </button>

            </div>


            <!-- FLASH MESSAGE -->

            <?php if ($message !== "") { ?>

                <div
                    class="vaccine-alert
                    <?php
                    echo $message_type === "success"
                        ? "vaccine-alert-success"
                        : "vaccine-alert-error";
                    ?>"
                >

                    <span>

                        <?php
                        echo htmlspecialchars($message);
                        ?>

                    </span>

                    <button
                        type="button"
                        onclick="this.parentElement.remove()"
                    >
                        &times;
                    </button>

                </div>

            <?php } ?>


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
                        placeholder="Search by vaccine name, description or age group..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                    <button type="submit">
                        Search
                    </button>


                    <?php if ($search !== "") { ?>

                        <a href="vaccines.php">
                            Clear
                        </a>

                    <?php } ?>

                </form>

            </div>


            <!-- VACCINES TABLE -->

            <div class="users-card">


                <div class="users-card-header">

                    <div>

                        <h3>
                            Registered Vaccines
                        </h3>

                        <p>
                            All vaccines currently available in ImmuniCare
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
                                    Vaccine
                                </th>

                                <th>
                                    Description
                                </th>

                                <th>
                                    Age Group
                                </th>

                                <th>
                                    Dose
                                </th>

                                <th>
                                    Availability
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


                            
    <?php
    $serial_number = 1;

    while ($vaccine = mysqli_fetch_assoc($result)) {
    ?>


<tr>


<!-- SERIAL NUMBER -->

<td>

    <?php
    echo $serial_number;
    ?>

</td>

                                        
                

<!-- VACCINE -->

                                        <td>

                                            <div class="user-name-cell">

                                                <div class="user-table-avatar">

                                                    <?php

                                                    echo strtoupper(
                                                        substr(
                                                            $vaccine["vaccine_name"],
                                                            0,
                                                            1
                                                        )
                                                    );

                                                    ?>

                                                </div>


                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $vaccine["vaccine_name"]
                                                    );

                                                    ?>

                                                </strong>

                                            </div>

                                        </td>


                                        <!-- DESCRIPTION -->

                                        <td>

                                            <div
                                                class="vaccine-description-cell"
                                            >

                                                <?php

                                                echo htmlspecialchars(
                                                    $vaccine["description"]
                                                );

                                                ?>

                                            </div>

                                        </td>


                                        <!-- AGE GROUP -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $vaccine["age_group"]
                                            );

                                            ?>

                                        </td>


                                        <!-- DOSE -->

                                        <td>

                                            <span
                                                class="vaccine-dose-badge"
                                            >

                                                Dose
                                                <?php
                                                echo (int)$vaccine["dose_number"];
                                                ?>

                                            </span>

                                        </td>


                                        <!-- AVAILABILITY -->

                                        <td>

                                            <?php

                                            $availability =
                                                strtolower(
                                                    trim(
                                                        $vaccine["availability"]
                                                    )
                                                );

                                            ?>


                                            <?php if ($availability === "available") { ?>

                                                <span
                                                    class="vaccine-availability available"
                                                >
                                                    Available
                                                </span>

                                            <?php } else { ?>

                                                <span
                                                    class="vaccine-availability unavailable"
                                                >
                                                    Unavailable
                                                </span>

                                            <?php } ?>

                                        </td>


                                        <!-- REGISTERED -->

                                        <td>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $vaccine["created_at"]
                                                )
                                            );

                                            ?>

                                        </td>


                                        <!-- ACTIONS -->

                                        <td>

                                            <div class="user-actions vaccine-actions">


                                                <!-- VIEW -->

                                                <button
                                                    type="button"
                                                    class="user-action-edit"
                                                    onclick="openViewVaccineModal(
                                                        <?php echo (int)$vaccine['id']; ?>,
                                                        <?php echo htmlspecialchars(json_encode($vaccine['vaccine_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($vaccine['description']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($vaccine['age_group']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo (int)$vaccine['dose_number']; ?>,
                                                        <?php echo htmlspecialchars(json_encode($vaccine['availability']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($vaccine['created_at']), ENT_QUOTES, 'UTF-8'); ?>
                                                    )"
                                                >
                                                    View
                                                </button>


                                                <!-- EDIT -->

                                                <button
                                                    type="button"
                                                    class="user-action-edit"
                                                    onclick="openEditVaccineModal(
                                                        <?php echo (int)$vaccine['id']; ?>,
                                                        <?php echo htmlspecialchars(json_encode($vaccine['vaccine_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($vaccine['description']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($vaccine['age_group']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo (int)$vaccine['dose_number']; ?>,
                                                        <?php echo htmlspecialchars(json_encode($vaccine['availability']), ENT_QUOTES, 'UTF-8'); ?>
                                                    )"
                                                >
                                                    Edit
                                                </button>


                                                <!-- AVAILABILITY -->

                                                <?php if ($availability === "available") { ?>

                                                    <button
                                                        type="button"
                                                        class="user-action-deactivate"
                                                        onclick="openVaccineAvailabilityModal(
                                                            <?php echo (int)$vaccine['id']; ?>,
                                                            <?php echo htmlspecialchars(json_encode($vaccine['vaccine_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                            'Unavailable'
                                                        )"
                                                    >
                                                        Unavailable
                                                    </button>

                                                <?php } else { ?>

                                                    <button
                                                        type="button"
                                                        class="user-action-activate"
                                                        onclick="openVaccineAvailabilityModal(
                                                            <?php echo (int)$vaccine['id']; ?>,
                                                            <?php echo htmlspecialchars(json_encode($vaccine['vaccine_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                            'Available'
                                                        )"
                                                    >
                                                        Available
                                                    </button>

                                                <?php } ?>


                                            </div>

                                        </td>


                                    </tr>

                                    <?php $serial_number++; ?>


                                <?php } ?>


                            <?php } else { ?>


                                <tr>

                                    <td
                                        colspan="8"
                                        class="users-empty"
                                    >
                                        No vaccines found.
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
     ADD VACCINE MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="addVaccineModal"
>

    <div class="user-modal vaccine-modal">

        <div class="user-modal-header">

            <div>

                <h3>
                    Add New Vaccine
                </h3>

                <p>
                    Add a new vaccine to the ImmuniCare system
                </p>

            </div>


            <button
                type="button"
                class="user-modal-close"
                onclick="closeAddVaccineModal()"
            >
                &times;
            </button>

        </div>


        <form method="POST">
            <?php echo csrf_field(); ?>

            <div class="user-modal-body">


                <input
                    type="hidden"
                    name="add_vaccine"
                    value="1"
                >


                <div class="user-form-group">

                    <label for="add_vaccine_name">
                        Vaccine Name
                    </label>

                    <input
                        type="text"
                        id="add_vaccine_name"
                        name="vaccine_name"
                        placeholder="e.g. BCG"
                        required
                    >

                </div>


                <div class="user-form-group">

                    <label for="add_vaccine_description">
                        Description
                    </label>

                    <textarea
                        id="add_vaccine_description"
                        name="description"
                        rows="4"
                        placeholder="Enter vaccine description..."
                        required
                    ></textarea>

                </div>


                <div class="user-form-group">

                    <label for="add_vaccine_age_group">
                        Age Group
                    </label>

                    <input
                        type="text"
                        id="add_vaccine_age_group"
                        name="age_group"
                        placeholder="e.g. At Birth"
                        required
                    >

                </div>


                <div class="user-form-row">


                    <div class="user-form-group">

                        <label for="add_vaccine_dose">
                            Dose Number
                        </label>

                        <input
                            type="number"
                            id="add_vaccine_dose"
                            name="dose_number"
                            min="1"
                            value="1"
                            required
                        >

                    </div>


                    <div class="user-form-group">

                        <label for="add_vaccine_availability">
                            Availability
                        </label>

                        <select
                            id="add_vaccine_availability"
                            name="availability"
                            required
                        >

                            <option value="Available">
                                Available
                            </option>

                            <option value="Unavailable">
                                Unavailable
                            </option>

                        </select>

                    </div>


                </div>


            </div>


            <div class="user-modal-footer">

                <button
                    type="button"
                    class="user-modal-cancel"
                    onclick="closeAddVaccineModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="user-modal-save"
                >
                    Add Vaccine
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     VIEW VACCINE MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="viewVaccineModal"
>

    <div class="user-modal vaccine-view-modal">

        <div class="user-modal-header">

            <div>

                <h3>
                    Vaccine Details
                </h3>

                <p>
                    View complete vaccine information
                </p>

            </div>


            <button
                type="button"
                class="user-modal-close"
                onclick="closeViewVaccineModal()"
            >
                &times;
            </button>

        </div>


        <div class="user-modal-body">


            <div class="child-details-grid">


                <div class="child-detail-item">

                    <span>
                        Vaccine Name
                    </span>

                    <strong
                        id="view_vaccine_name"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Dose Number
                    </span>

                    <strong
                        id="view_vaccine_dose"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Age Group
                    </span>

                    <strong
                        id="view_vaccine_age"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Availability
                    </span>

                    <strong
                        id="view_vaccine_availability"
                    ></strong>

                </div>


                <div
                    class="child-detail-item child-detail-full"
                >

                    <span>
                        Description
                    </span>

                    <strong
                        id="view_vaccine_description"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Registered
                    </span>

                    <strong
                        id="view_vaccine_registered"
                    ></strong>

                </div>


            </div>


        </div>


        <div class="user-modal-footer">

            <button
                type="button"
                class="user-modal-cancel"
                onclick="closeViewVaccineModal()"
            >
                Close
            </button>

        </div>

    </div>

</div>


<!-- =========================================================
     EDIT VACCINE MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="editVaccineModal"
>

    <div class="user-modal vaccine-modal">

        <div class="user-modal-header">

            <div>

                <h3>
                    Edit Vaccine
                </h3>

                <p>
                    Update vaccine information
                </p>

            </div>


            <button
                type="button"
                class="user-modal-close"
                onclick="closeEditVaccineModal()"
            >
                &times;
            </button>

        </div>


        <form method="POST">
            <?php echo csrf_field(); ?>


            <div class="user-modal-body">


                <input
                    type="hidden"
                    name="update_vaccine"
                    value="1"
                >


                <input
                    type="hidden"
                    id="edit_vaccine_id"
                    name="vaccine_id"
                >


                <div class="user-form-group">

                    <label for="edit_vaccine_name">
                        Vaccine Name
                    </label>

                    <input
                        type="text"
                        id="edit_vaccine_name"
                        name="vaccine_name"
                        required
                    >

                </div>


                <div class="user-form-group">

                    <label for="edit_vaccine_description">
                        Description
                    </label>

                    <textarea
                        id="edit_vaccine_description"
                        name="description"
                        rows="4"
                        placeholder="Enter vaccine description..."
                        required
                    ></textarea>

                </div>


                <div class="user-form-group">

                    <label for="edit_vaccine_age_group">
                        Age Group
                    </label>

                    <input
                        type="text"
                        id="edit_vaccine_age_group"
                        name="age_group"
                        required
                    >

                </div>


                <div class="user-form-row">


                    <div class="user-form-group">

                        <label for="edit_vaccine_dose">
                            Dose Number
                        </label>

                        <input
                            type="number"
                            id="edit_vaccine_dose"
                            name="dose_number"
                            min="1"
                            required
                        >

                    </div>


                    <div class="user-form-group">

                        <label for="edit_vaccine_availability">
                            Availability
                        </label>

                        <select
                            id="edit_vaccine_availability"
                            name="availability"
                            required
                        >

                            <option value="Available">
                                Available
                            </option>

                            <option value="Unavailable">
                                Unavailable
                            </option>

                        </select>

                    </div>


                </div>


            </div>


            <div class="user-modal-footer">

                <button
                    type="button"
                    class="user-modal-cancel"
                    onclick="closeEditVaccineModal()"
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
     AVAILABILITY CONFIRMATION MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="vaccineAvailabilityModal"
>

    <div class="user-status-modal">


        <div class="user-status-modal-header">

            <div
                class="user-status-modal-icon"
                id="vaccineAvailabilityModalIcon"
            >
                !
            </div>


            <button
                type="button"
                class="user-modal-close"
                onclick="closeVaccineAvailabilityModal()"
            >
                &times;
            </button>

        </div>


        <div class="user-status-modal-body">

            <h3 id="vaccineAvailabilityModalTitle">
                Make Vaccine Unavailable?
            </h3>


            <p id="vaccineAvailabilityModalMessage">
                Are you sure you want to make this vaccine unavailable?
            </p>

        </div>


        <form method="POST">
            <?php echo csrf_field(); ?>


            <input
                type="hidden"
                name="change_vaccine_availability"
                value="1"
            >


            <input
                type="hidden"
                id="availability_vaccine_id"
                name="availability_vaccine_id"
            >


            <input
                type="hidden"
                id="new_vaccine_availability"
                name="new_availability"
            >


            <div class="user-status-modal-actions">


                <button
                    type="button"
                    class="user-status-cancel"
                    onclick="closeVaccineAvailabilityModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="user-status-confirm deactivate"
                    id="vaccineAvailabilityConfirmButton"
                >
                    Make Unavailable
                </button>


            </div>


        </form>

    </div>

</div>


<script>


/* =========================================================
   ADD VACCINE
========================================================= */

function openAddVaccineModal() {

    document
        .getElementById("addVaccineModal")
        .classList.add("show");

}


function closeAddVaccineModal() {

    document
        .getElementById("addVaccineModal")
        .classList.remove("show");

}


/* =========================================================
   VIEW VACCINE
========================================================= */

function openViewVaccineModal(
    id,
    vaccineName,
    description,
    ageGroup,
    doseNumber,
    availability,
    createdAt
) {

    document.getElementById(
        "view_vaccine_name"
    ).textContent = vaccineName;


    document.getElementById(
        "view_vaccine_description"
    ).textContent = description;


    document.getElementById(
        "view_vaccine_age"
    ).textContent = ageGroup;


    document.getElementById(
        "view_vaccine_dose"
    ).textContent =
        "Dose " + doseNumber;


    document.getElementById(
        "view_vaccine_availability"
    ).textContent = availability;


    const registeredDate =
        new Date(createdAt);


    document.getElementById(
        "view_vaccine_registered"
    ).textContent =
        registeredDate.toLocaleDateString(
            "en-GB",
            {
                day: "2-digit",
                month: "short",
                year: "numeric"
            }
        );


    document
        .getElementById("viewVaccineModal")
        .classList.add("show");

}


function closeViewVaccineModal() {

    document
        .getElementById("viewVaccineModal")
        .classList.remove("show");

}


/* =========================================================
   EDIT VACCINE
========================================================= */

function openEditVaccineModal(
    id,
    vaccineName,
    description,
    ageGroup,
    doseNumber,
    availability
) {

    document.getElementById(
        "edit_vaccine_id"
    ).value = id;


    document.getElementById(
        "edit_vaccine_name"
    ).value = vaccineName;


    document.getElementById(
        "edit_vaccine_description"
    ).value = description;


    document.getElementById(
        "edit_vaccine_age_group"
    ).value = ageGroup;


    document.getElementById(
        "edit_vaccine_dose"
    ).value = doseNumber;


    document.getElementById(
        "edit_vaccine_availability"
    ).value = availability;


    document
        .getElementById("editVaccineModal")
        .classList.add("show");

}


function closeEditVaccineModal() {

    document
        .getElementById("editVaccineModal")
        .classList.remove("show");

}


/* =========================================================
   AVAILABILITY
========================================================= */

function openVaccineAvailabilityModal(
    id,
    vaccineName,
    newAvailability
) {

    document.getElementById(
        "availability_vaccine_id"
    ).value = id;


    document.getElementById(
        "new_vaccine_availability"
    ).value = newAvailability;


    const title =
        document.getElementById(
            "vaccineAvailabilityModalTitle"
        );


    const message =
        document.getElementById(
            "vaccineAvailabilityModalMessage"
        );


    const button =
        document.getElementById(
            "vaccineAvailabilityConfirmButton"
        );


    const icon =
        document.getElementById(
            "vaccineAvailabilityModalIcon"
        );


    if (newAvailability === "Unavailable") {

        title.textContent =
            "Make Vaccine Unavailable?";


        message.textContent =
            "Are you sure you want to make " +
            vaccineName +
            " unavailable?";


        button.textContent =
            "Make Unavailable";


        button.classList.remove(
            "activate"
        );


        button.classList.add(
            "deactivate"
        );


        icon.textContent = "!";

    } else {

        title.textContent =
            "Make Vaccine Available?";


        message.textContent =
            "Are you sure you want to make " +
            vaccineName +
            " available?";


        button.textContent =
            "Make Available";


        button.classList.remove(
            "deactivate"
        );


        button.classList.add(
            "activate"
        );


        icon.textContent = "✓";

    }


    document
        .getElementById(
            "vaccineAvailabilityModal"
        )
        .classList.add("show");

}


function closeVaccineAvailabilityModal() {

    document
        .getElementById(
            "vaccineAvailabilityModal"
        )
        .classList.remove("show");

}


/* =========================================================
   CLOSE MODALS BY CLICKING OUTSIDE
========================================================= */

window.addEventListener(
    "click",
    function(event) {


        if (
            event.target ===
            document.getElementById(
                "addVaccineModal"
            )
        ) {

            closeAddVaccineModal();

        }


        if (
            event.target ===
            document.getElementById(
                "viewVaccineModal"
            )
        ) {

            closeViewVaccineModal();

        }


        if (
            event.target ===
            document.getElementById(
                "editVaccineModal"
            )
        ) {

            closeEditVaccineModal();

        }


        if (
            event.target ===
            document.getElementById(
                "vaccineAvailabilityModal"
            )
        ) {

            closeVaccineAvailabilityModal();

        }

    }
);

</script>


</body>

</html>


<?php

mysqli_stmt_close($stmt);

?>