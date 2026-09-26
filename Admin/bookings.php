<?php

require_once "../includes/app.php";
$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];


/* =========================================================
   UPDATE BOOKING STATUS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["change_booking_status"])
) {
    verify_csrf();

    $booking_id = intval($_POST["booking_id"]);
    $new_status = trim($_POST["new_status"]);


    $allowed_statuses = [
        "Approved",
        "Rejected"
    ];


    if (
        $booking_id <= 0
        || !in_array($new_status, $allowed_statuses)
    ) {

        $_SESSION["booking_message"] =
            "Invalid booking status.";

        $_SESSION["booking_message_type"] =
            "error";

        header("Location: bookings.php");
        exit();

    }


    /* Get current booking status */

    $current_sql = "
        SELECT status
        FROM bookings
        WHERE id = ?
        LIMIT 1
    ";

    $current_stmt = mysqli_prepare(
        $conn,
        $current_sql
    );

    mysqli_stmt_bind_param(
        $current_stmt,
        "i",
        $booking_id
    );

    mysqli_stmt_execute($current_stmt);

    $current_result =
        mysqli_stmt_get_result($current_stmt);

    $current_booking =
        mysqli_fetch_assoc($current_result);

    mysqli_stmt_close($current_stmt);


    if (!$current_booking) {

        $_SESSION["booking_message"] =
            "Booking not found.";

        $_SESSION["booking_message_type"] =
            "error";

        header("Location: bookings.php");
        exit();

    }


    $current_status =
        $current_booking["status"];


    /* =====================================================
       ALLOWED STATUS TRANSITIONS

       Pending   → Approved
       Pending   → Rejected
       Approved  → Completed
    ===================================================== */

    $valid_transition = false;


    if (
        $current_status === "Pending"
        && in_array(
            $new_status,
            ["Approved", "Rejected"]
        )
    ) {

        $valid_transition = true;

    }


    if (!$valid_transition) {

        $_SESSION["booking_message"] =
            "This booking cannot be changed from " .
            $current_status .
            " to " .
            $new_status .
            ".";

        $_SESSION["booking_message_type"] =
            "error";

        header("Location: bookings.php");
        exit();

    }


    /* =====================================================
       UPDATE STATUS
    ===================================================== */

    $update_sql = "
        UPDATE bookings
        SET status = ?
        WHERE id = ?
    ";

    $update_stmt = mysqli_prepare(
        $conn,
        $update_sql
    );

    mysqli_stmt_bind_param(
        $update_stmt,
        "si",
        $new_status,
        $booking_id
    );


    if (mysqli_stmt_execute($update_stmt)) {
        $info_stmt = mysqli_prepare(
            $conn,
            "SELECT parent_id FROM bookings WHERE id = ?"
        );
        mysqli_stmt_bind_param($info_stmt, "i", $booking_id);
        mysqli_stmt_execute($info_stmt);
        $info = mysqli_fetch_assoc(mysqli_stmt_get_result($info_stmt));
        mysqli_stmt_close($info_stmt);

        if ($info) {
            notify_user(
                $conn,
                (int)$info["parent_id"],
                "Appointment " . strtolower($new_status),
                "Your appointment status was updated to " . $new_status . ".",
                "appointment",
                "Parent/bookings.php"
            );
        }
        audit($conn, $admin_id, "booking.status_changed", "booking", $booking_id, ["status" => $new_status]);
        $_SESSION["booking_message"] = "Booking status updated successfully.";
        $_SESSION["booking_message_type"] = "success";

    } else {

        $_SESSION["booking_message"] =
            "Unable to update booking status.";

        $_SESSION["booking_message_type"] =
            "error";

    }


    mysqli_stmt_close($update_stmt);

    header("Location: bookings.php");
    exit();

}


/* =========================================================
   FLASH MESSAGE
========================================================= */

$message =
    $_SESSION["booking_message"] ?? "";

$message_type =
    $_SESSION["booking_message_type"] ?? "";


unset($_SESSION["booking_message"]);
unset($_SESSION["booking_message_type"]);


/* =========================================================
   FILTERS
========================================================= */

$search =
    isset($_GET["search"])
        ? trim($_GET["search"])
        : "";

$status_filter =
    isset($_GET["status"])
        ? trim($_GET["status"])
        : "";

$selected_hospital_id =
    isset($_GET["hospital_id"])
        ? intval($_GET["hospital_id"])
        : 0;


/* =========================================================
   VALIDATE STATUS FILTER
========================================================= */

$valid_filter_statuses = [
    "Pending",
    "Approved",
    "Rejected",
    "Completed"
];


if (
    $status_filter !== ""
    && !in_array(
        $status_filter,
        $valid_filter_statuses
    )
) {

    $status_filter = "";

}


/* =========================================================
   GET HOSPITALS FOR FILTER
========================================================= */

$hospital_filter_sql = "
    SELECT id, hospital_name
    FROM hospitals
    ORDER BY hospital_name ASC
";

$hospital_filter_result =
    mysqli_query(
        $conn,
        $hospital_filter_sql
    );


/* =========================================================
   GET BOOKINGS
========================================================= */

$sql = "
    SELECT
        b.id,
        b.parent_id,
        b.child_id,
        b.hospital_id,
        b.vaccine_id,
        b.booking_date,
        b.booking_time,
        b.status,
        b.created_at,

        u.name AS parent_name,
        u.email AS parent_email,

        c.child_name,

        h.hospital_name,
        h.city,

        v.vaccine_name,
        v.dose_number

    FROM bookings b

    INNER JOIN users u
        ON b.parent_id = u.id

    INNER JOIN children c
        ON b.child_id = c.id

    INNER JOIN hospitals h
        ON b.hospital_id = h.id

    INNER JOIN vaccines v
        ON b.vaccine_id = v.id

    WHERE 1=1
";


$params = [];
$types = "";


/* =========================================================
   SEARCH
========================================================= */

if ($search !== "") {

    $sql .= "
        AND (
            c.child_name LIKE ?
            OR u.name LIKE ?
            OR u.email LIKE ?
            OR h.hospital_name LIKE ?
            OR v.vaccine_name LIKE ?
        )
    ";

    $search_value =
        "%" . $search . "%";


    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sssss";

}


/* =========================================================
   STATUS FILTER
========================================================= */

if ($status_filter !== "") {

    $sql .= "
        AND b.status = ?
    ";

    $params[] =
        $status_filter;

    $types .= "s";

}


/* =========================================================
   HOSPITAL FILTER
========================================================= */

if ($selected_hospital_id > 0) {

    $sql .= "
        AND b.hospital_id = ?
    ";

    $params[] =
        $selected_hospital_id;

    $types .= "i";

}


/* =========================================================
   ORDER
========================================================= */

$sql .= "
    ORDER BY b.booking_date ASC,
             b.booking_time ASC,
             b.id ASC
";


/* =========================================================
   PREPARE
========================================================= */

$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


/* =========================================================
   BIND DYNAMIC PARAMETERS
========================================================= */

if (!empty($params)) {

    $bind_params = [];

    $bind_params[] = $types;


    foreach ($params as $key => $value) {

        $bind_params[] =
            &$params[$key];

    }


    call_user_func_array(
        [$stmt, "bind_param"],
        $bind_params
    );

}


/* =========================================================
   EXECUTE
========================================================= */

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

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
        Bookings | ImmuniCare Admin
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
                class="sidebar-link"
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
                class="sidebar-link active"
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
                    Bookings
                </h1>

                <p>
                    Manage vaccination appointments across ImmuniCare
                </p>

            </div>


            <div class="header-actions">


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


        <!-- =================================================
             PAGE CONTENT
        ================================================= -->

        <div class="dashboard-content">


            <!-- PAGE HEADING -->

            <div class="page-heading">

                <div>

                    <h2>
                        Appointment Bookings
                    </h2>

                    <p>
                        View and manage vaccination appointments.
                    </p>

                </div>

            </div>


            <!-- FLASH MESSAGE -->

            <?php if ($message !== "") { ?>

                <div
                    class="booking-alert
                    <?php
                    echo $message_type === "success"
                        ? "booking-alert-success"
                        : "booking-alert-error";
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


            <!-- =================================================
                 FILTERS
            ================================================= -->

            <div class="bookings-filter-card">


                <form
                    method="GET"
                    action="bookings.php"
                    class="bookings-filter-form"
                >


                    <div class="booking-filter-group search-filter">

                        <label for="booking_search">
                            Search
                        </label>

                        <input
                            type="text"
                            id="booking_search"
                            name="search"
                            placeholder="Child, parent, vaccine or hospital..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    </div>


                    <div class="booking-filter-group">

                        <label for="booking_status">
                            Status
                        </label>

                        <select
                            id="booking_status"
                            name="status"
                        >

                            <option value="">
                                All Statuses
                            </option>

                            <option
                                value="Pending"
                                <?php
                                echo $status_filter === "Pending"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Pending
                            </option>

                            <option
                                value="Approved"
                                <?php
                                echo $status_filter === "Approved"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Approved
                            </option>

                            <option
                                value="Rejected"
                                <?php
                                echo $status_filter === "Rejected"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Rejected
                            </option>

                            <option
                                value="Completed"
                                <?php
                                echo $status_filter === "Completed"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Completed
                            </option>

                        </select>

                    </div>


                    <div class="booking-filter-group">

                        <label for="booking_hospital">
                            Hospital
                        </label>

                        <select
                            id="booking_hospital"
                            name="hospital_id"
                        >

                            <option value="">
                                All Hospitals
                            </option>


                            <?php while (
                                $hospital_option =
                                mysqli_fetch_assoc(
                                    $hospital_filter_result
                                )
                            ) { ?>

                           <option
    value="<?php echo (int)$hospital_option["id"]; ?>"
    <?php
    echo $hospital_option["id"] == $selected_hospital_id
        ? "selected"
        : "";
    ?>
>

                                    <?php

                                    echo htmlspecialchars(
                                        $hospital_option["hospital_name"]
                                    );

                                    ?>

                                </option>

                            <?php } ?>

                        </select>

                    </div>


                    <div class="booking-filter-actions">

                        <button
                            type="submit"
                            class="booking-filter-button"
                        >
                            Apply Filters
                        </button>


                        <a
                            href="bookings.php"
                            class="booking-clear-button"
                        >
                            Clear
                        </a>

                    </div>


                </form>

            </div>


            <!-- =================================================
                 BOOKINGS TABLE
            ================================================= -->

            <div class="users-card">


                <div class="users-card-header">

                    <div>

                        <h3>
                            All Bookings
                        </h3>

                        <p>
                            Complete appointment history
                        </p>

                    </div>

                </div>


                <div class="users-table-wrapper">


                    <table class="users-table bookings-table">


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
                                    Vaccine
                                </th>

                                <th>
                                    Hospital
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Time
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (
                                mysqli_num_rows($result) > 0
                            ) { ?>


                                <?php

                                $serial_number = 1;

                                while (
                                    $booking =
                                    mysqli_fetch_assoc($result)
                                ) {

                                ?>


                                    <?php

                                    $status =
                                        $booking["status"];

                                    ?>


                                    <tr>


                                        <!-- SERIAL -->

                                        <td>

                                            <?php
                                            echo $serial_number;
                                            ?>

                                        </td>


                                        <!-- CHILD -->

                                        <td>

                                            <strong>

                                                <?php

                                                echo htmlspecialchars(
                                                    $booking["child_name"]
                                                );

                                                ?>

                                            </strong>

                                        </td>


                                        <!-- PARENT -->

                                        <td>

                                            <div class="booking-person-cell">

                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $booking["parent_name"]
                                                    );

                                                    ?>

                                                </strong>

                                                <span>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $booking["parent_email"]
                                                    );

                                                    ?>

                                                </span>

                                            </div>

                                        </td>


                                        <!-- VACCINE -->

                                        <td>

                                            <div class="booking-vaccine-cell">

                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $booking["vaccine_name"]
                                                    );

                                                    ?>

                                                </strong>

                                                <span>

                                                    Dose
                                                    <?php

                                                    echo (int)
                                                        $booking["dose_number"];

                                                    ?>

                                                </span>

                                            </div>

                                        </td>


                                        <!-- HOSPITAL -->

                                        <td>

                                            <div class="booking-hospital-cell">

                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $booking["hospital_name"]
                                                    );

                                                    ?>

                                                </strong>

                                                <span>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $booking["city"]
                                                    );

                                                    ?>

                                                </span>

                                            </div>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $booking["booking_date"]
                                                )
                                            );

                                            ?>

                                        </td>


                                        <!-- TIME -->

                                        <td>

                                            <?php

                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $booking["booking_time"]
                                                )
                                            );

                                            ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="admin-booking-status
                                                <?php
                                                echo strtolower(
                                                    $status
                                                );
                                                ?>"
                                            >

                                                <?php

                                                echo htmlspecialchars(
                                                    $status
                                                );

                                                ?>

                                            </span>

                                        </td>


                                        <!-- ACTIONS -->

                                        <td>

                                            <div
                                                class="booking-actions"
                                            >


                                                <!-- VIEW -->

                                                <button
                                                    type="button"
                                                    class="user-action-edit"
                                                    onclick="openViewBookingModal(
                                                        <?php echo (int)$booking['id']; ?>,
                                                        <?php echo htmlspecialchars(json_encode($booking['parent_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($booking['parent_email']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($booking['child_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($booking['vaccine_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo (int)$booking['dose_number']; ?>,
                                                        <?php echo htmlspecialchars(json_encode($booking['hospital_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($booking['city']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($booking['booking_date']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($booking['booking_time']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($booking['status']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($booking['created_at']), ENT_QUOTES, 'UTF-8'); ?>
                                                    )"
                                                >
                                                    View
                                                </button>


                                                <!-- STATUS ACTIONS -->

                                                <?php if (
                                                    $status === "Pending"
                                                ) { ?>


                                                    <button
                                                        type="button"
                                                        class="user-action-activate"
                                                        onclick="openBookingStatusModal(
                                                            <?php echo (int)$booking['id']; ?>,
                                                            <?php echo htmlspecialchars(json_encode($booking['child_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                            'Approved'
                                                        )"
                                                    >
                                                        Approve
                                                    </button>


                                                    <button
                                                        type="button"
                                                        class="user-action-deactivate"
                                                        onclick="openBookingStatusModal(
                                                            <?php echo (int)$booking['id']; ?>,
                                                            <?php echo htmlspecialchars(json_encode($booking['child_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                            'Rejected'
                                                        )"
                                                    >
                                                        Reject
                                                    </button>


                                                <?php } else { ?>


                                                    <span
                                                        class="booking-no-action"
                                                    >
                                                        —
                                                    </span>


                                                <?php } ?>


                                            </div>

                                        </td>


                                    </tr>


                                    <?php

                                    $serial_number++;

                                ?>


                                <?php } ?>


                            <?php } else { ?>


                                <tr>

                                    <td
                                        colspan="9"
                                        class="users-empty"
                                    >

                                        No bookings found.

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
     VIEW BOOKING MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="viewBookingModal"
>


    <div class="user-modal booking-view-modal">


        <div class="user-modal-header">


            <div>

                <h3>
                    Booking Details
                </h3>

                <p>
                    View complete appointment information
                </p>

            </div>


            <button
                type="button"
                class="user-modal-close"
                onclick="closeViewBookingModal()"
            >
                &times;
            </button>


        </div>


        <div class="user-modal-body">


            <div class="child-details-grid">


                <div class="child-detail-item">

                    <span>
                        Booking ID
                    </span>

                    <strong
                        id="view_booking_id"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Status
                    </span>

                    <strong
                        id="view_booking_status"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Parent
                    </span>

                    <strong
                        id="view_booking_parent"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Parent Email
                    </span>

                    <strong
                        id="view_booking_email"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Child
                    </span>

                    <strong
                        id="view_booking_child"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Vaccine
                    </span>

                    <strong
                        id="view_booking_vaccine"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Dose
                    </span>

                    <strong
                        id="view_booking_dose"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Hospital
                    </span>

                    <strong
                        id="view_booking_hospital"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        City
                    </span>

                    <strong
                        id="view_booking_city"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Appointment Date
                    </span>

                    <strong
                        id="view_booking_date"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Appointment Time
                    </span>

                    <strong
                        id="view_booking_time"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Booked On
                    </span>

                    <strong
                        id="view_booking_created"
                    ></strong>

                </div>


            </div>


        </div>


        <div class="user-modal-footer">

            <button
                type="button"
                class="user-modal-cancel"
                onclick="closeViewBookingModal()"
            >
                Close
            </button>

        </div>


    </div>

</div>


<!-- =========================================================
     BOOKING STATUS MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="bookingStatusModal"
>


    <div class="user-status-modal">


        <div class="user-status-modal-header">


            <div
                class="user-status-modal-icon"
                id="bookingStatusModalIcon"
            >
                !
            </div>


            <button
                type="button"
                class="user-modal-close"
                onclick="closeBookingStatusModal()"
            >
                &times;
            </button>


        </div>


        <div class="user-status-modal-body">


            <h3
                id="bookingStatusModalTitle"
            >
                Update Booking?
            </h3>


            <p
                id="bookingStatusModalMessage"
            >
                Are you sure you want to update this booking?
            </p>


        </div>


        <form method="POST">
            <?php echo csrf_field(); ?>


            <input
                type="hidden"
                name="change_booking_status"
                value="1"
            >


            <input
                type="hidden"
                id="booking_status_id"
                name="booking_id"
            >


            <input
                type="hidden"
                id="new_booking_status"
                name="new_status"
            >


            <div class="user-status-modal-actions">


                <button
                    type="button"
                    class="user-status-cancel"
                    onclick="closeBookingStatusModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="user-status-confirm activate"
                    id="bookingStatusConfirmButton"
                >
                    Confirm
                </button>


            </div>


        </form>


    </div>

</div>


<script>


/* =========================================================
   VIEW BOOKING
========================================================= */

function openViewBookingModal(
    id,
    parentName,
    parentEmail,
    childName,
    vaccineName,
    doseNumber,
    hospitalName,
    city,
    bookingDate,
    bookingTime,
    status,
    createdAt
) {


    document.getElementById(
        "view_booking_id"
    ).textContent =
        "#" + id;


    document.getElementById(
        "view_booking_status"
    ).textContent =
        status;


    document.getElementById(
        "view_booking_parent"
    ).textContent =
        parentName;


    document.getElementById(
        "view_booking_email"
    ).textContent =
        parentEmail;


    document.getElementById(
        "view_booking_child"
    ).textContent =
        childName;


    document.getElementById(
        "view_booking_vaccine"
    ).textContent =
        vaccineName;


    document.getElementById(
        "view_booking_dose"
    ).textContent =
        "Dose " + doseNumber;


    document.getElementById(
        "view_booking_hospital"
    ).textContent =
        hospitalName;


    document.getElementById(
        "view_booking_city"
    ).textContent =
        city;


    const appointmentDate =
        new Date(bookingDate);


    document.getElementById(
        "view_booking_date"
    ).textContent =
        appointmentDate.toLocaleDateString(
            "en-GB",
            {
                day: "2-digit",
                month: "short",
                year: "numeric"
            }
        );


    const timeParts =
        bookingTime.split(":");


    let hours =
        parseInt(
            timeParts[0],
            10
        );


    const minutes =
        timeParts[1];


    const period =
        hours >= 12
            ? "PM"
            : "AM";


    hours =
        hours % 12 || 12;


    document.getElementById(
        "view_booking_time"
    ).textContent =
        hours +
        ":" +
        minutes +
        " " +
        period;


    const createdDate =
        new Date(createdAt);


    document.getElementById(
        "view_booking_created"
    ).textContent =
        createdDate.toLocaleDateString(
            "en-GB",
            {
                day: "2-digit",
                month: "short",
                year: "numeric"
            }
        );


    document
        .getElementById(
            "viewBookingModal"
        )
        .classList.add("show");

}


function closeViewBookingModal() {

    document
        .getElementById(
            "viewBookingModal"
        )
        .classList.remove("show");

}


/* =========================================================
   BOOKING STATUS MODAL
========================================================= */

function openBookingStatusModal(
    id,
    childName,
    newStatus
) {


    document.getElementById(
        "booking_status_id"
    ).value =
        id;


    document.getElementById(
        "new_booking_status"
    ).value =
        newStatus;


    const title =
        document.getElementById(
            "bookingStatusModalTitle"
        );


    const message =
        document.getElementById(
            "bookingStatusModalMessage"
        );


    const button =
        document.getElementById(
            "bookingStatusConfirmButton"
        );


    const icon =
        document.getElementById(
            "bookingStatusModalIcon"
        );


    if (newStatus === "Approved") {


        title.textContent =
            "Approve Booking?";


        message.textContent =
            "Are you sure you want to approve the booking for " +
            childName +
            "?";


        button.textContent =
            "Approve Booking";


        button.classList.remove(
            "deactivate"
        );


        button.classList.add(
            "activate"
        );


        icon.textContent =
            "✓";


    } else if (
        newStatus === "Rejected"
    ) {


        title.textContent =
            "Reject Booking?";


        message.textContent =
            "Are you sure you want to reject the booking for " +
            childName +
            "?";


        button.textContent =
            "Reject Booking";


        button.classList.remove(
            "activate"
        );


        button.classList.add(
            "deactivate"
        );


        icon.textContent =
            "!";


    } else {


        title.textContent =
            "Complete Booking?";


        message.textContent =
            "Are you sure you want to mark the booking for " +
            childName +
            " as completed?";


        button.textContent =
            "Mark Completed";


        button.classList.remove(
            "deactivate"
        );


        button.classList.add(
            "activate"
        );


        icon.textContent =
            "✓";

    }


    document
        .getElementById(
            "bookingStatusModal"
        )
        .classList.add("show");

}


function closeBookingStatusModal() {

    document
        .getElementById(
            "bookingStatusModal"
        )
        .classList.remove("show");

}


/* =========================================================
   CLOSE MODALS OUTSIDE
========================================================= */

window.addEventListener(
    "click",
    function(event) {


        if (
            event.target ===
            document.getElementById(
                "viewBookingModal"
            )
        ) {

            closeViewBookingModal();

        }


        if (
            event.target ===
            document.getElementById(
                "bookingStatusModal"
            )
        ) {

            closeBookingStatusModal();

        }

    }
);


</script>


</body>

</html>


<?php

mysqli_stmt_close($stmt);

?>