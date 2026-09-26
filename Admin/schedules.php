<?php

require_once "../includes/app.php";
$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];


/* =========================================================
   UPDATE SCHEDULE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["edit_schedule"])
) {
    verify_csrf();

    $schedule_id = intval($_POST["schedule_id"]);
    $scheduled_date = trim($_POST["scheduled_date"] ?? "");
    $scheduled_time = trim($_POST["scheduled_time"] ?? "");


    /* -----------------------------------------------------
       BASIC VALIDATION
    ----------------------------------------------------- */

    if (
        $schedule_id <= 0
        || $scheduled_date === ""
        || $scheduled_time === ""
        || !valid_date($scheduled_date)
        || !valid_time($scheduled_time)
        || strtotime("$scheduled_date $scheduled_time") <= time()
    ) {

        $_SESSION["schedule_message"] =
            "Please provide a valid date and time.";

        $_SESSION["schedule_message_type"] =
            "error";

        header("Location: schedules.php");
        exit();

    }


    /* -----------------------------------------------------
       GET CURRENT SCHEDULE
    ----------------------------------------------------- */

    $current_sql = "
        SELECT
            id,
            status
        FROM vaccination_schedules
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
        $schedule_id
    );


    mysqli_stmt_execute($current_stmt);


    $current_result =
        mysqli_stmt_get_result($current_stmt);


    $current_schedule =
        mysqli_fetch_assoc($current_result);


    mysqli_stmt_close($current_stmt);


    if (!$current_schedule) {

        $_SESSION["schedule_message"] =
            "Vaccination schedule not found.";

        $_SESSION["schedule_message_type"] =
            "error";

        header("Location: schedules.php");
        exit();

    }


    /* -----------------------------------------------------
       ONLY SCHEDULED RECORDS CAN BE EDITED
    ----------------------------------------------------- */

    if ($current_schedule["status"] !== "Scheduled") {

        $_SESSION["schedule_message"] =
            "Completed vaccination schedules cannot be edited.";

        $_SESSION["schedule_message_type"] =
            "error";

        header("Location: schedules.php");
        exit();

    }


    /* -----------------------------------------------------
       UPDATE DATE AND TIME
    ----------------------------------------------------- */

    $update_sql = "
        UPDATE vaccination_schedules
        SET
            scheduled_date = ?,
            scheduled_time = ?
        WHERE id = ?
        AND status = 'Scheduled'
    ";


    $update_stmt = mysqli_prepare(
        $conn,
        $update_sql
    );


    mysqli_stmt_bind_param(
        $update_stmt,
        "ssi",
        $scheduled_date,
        $scheduled_time,
        $schedule_id
    );


    if (mysqli_stmt_execute($update_stmt)) {

        $_SESSION["schedule_message"] =
            "Vaccination schedule updated successfully.";

        $_SESSION["schedule_message_type"] =
            "success";

    } else {

        $_SESSION["schedule_message"] =
            "Unable to update vaccination schedule.";

        $_SESSION["schedule_message_type"] =
            "error";

    }


    mysqli_stmt_close($update_stmt);


    header("Location: schedules.php");
    exit();

}


/* =========================================================
   FLASH MESSAGE
========================================================= */

$message =
    $_SESSION["schedule_message"] ?? "";

$message_type =
    $_SESSION["schedule_message_type"] ?? "";


unset($_SESSION["schedule_message"]);
unset($_SESSION["schedule_message_type"]);


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


/* =========================================================
   VALIDATE STATUS FILTER
========================================================= */

$valid_filter_statuses = [
    "Scheduled",
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
   GET VACCINATION SCHEDULES
========================================================= */

$sql = "
    SELECT

        vs.id,
        vs.child_id,
        vs.vaccine_id,
        vs.scheduled_date,
        vs.scheduled_time,
        vs.status,
        vs.created_at,

        c.child_name,

        u.name AS parent_name,
        u.email AS parent_email,

        v.vaccine_name,
        v.dose_number

    FROM vaccination_schedules vs

    INNER JOIN children c
        ON vs.child_id = c.id

    INNER JOIN users u
        ON c.parent_id = u.id

    INNER JOIN vaccines v
        ON vs.vaccine_id = v.id

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
            OR v.vaccine_name LIKE ?
        )
    ";


    $search_value =
        "%" . $search . "%";


    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;


    $types .= "ssss";

}


/* =========================================================
   STATUS FILTER
========================================================= */

if ($status_filter !== "") {

    $sql .= "
        AND vs.status = ?
    ";


    $params[] =
        $status_filter;


    $types .= "s";

}


/* =========================================================
   ORDER
========================================================= */

$sql .= "
    ORDER BY
        vs.scheduled_date ASC,
        vs.scheduled_time ASC,
        vs.id ASC
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
        Vaccination Schedule | ImmuniCare Admin
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =================================================
           SCHEDULE STATUS
        ================================================= */

        .admin-schedule-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }


        .admin-schedule-status.scheduled {
            background: #e0f2fe;
            color: #0369a1;
        }


        .admin-schedule-status.completed {
            background: #dcfce7;
            color: #15803d;
        }


        /* =================================================
           SCHEDULE TABLE CELLS
        ================================================= */

        .schedule-person-cell,
        .schedule-vaccine-cell {

            display: flex;
            flex-direction: column;
            gap: 3px;

        }


        .schedule-person-cell strong,
        .schedule-vaccine-cell strong {

            font-size: 14px;

        }


        .schedule-person-cell span,
        .schedule-vaccine-cell span {

            font-size: 12px;
            color: #64748b;

        }


        .schedule-date-cell {

            white-space: nowrap;

        }


        .schedule-time-cell {

            white-space: nowrap;

        }


        /* =================================================
           EDIT FORM
        ================================================= */

        .schedule-form-group {

            margin-bottom: 18px;

        }


        .schedule-form-group label {

            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;

        }


        .schedule-form-group input {

            width: 100%;
            box-sizing: border-box;
            padding: 11px 13px;
            border: 1px solid #dbe3ec;
            border-radius: 9px;
            background: #ffffff;
            color: #0f172a;
            font-size: 14px;
            outline: none;

        }


        .schedule-form-group input:focus {

            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.12);

        }


        .schedule-edit-note {

            margin-top: 4px;
            padding: 11px 13px;
            border-radius: 9px;
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;

        }


        /* =================================================
           SCHEDULE VIEW
        ================================================= */

        .schedule-status-view {

            display: inline-flex;
            align-items: center;
            justify-content: center;

        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 900px) {

            .schedules-table {
                min-width: 900px;
            }

        }

    </style>

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


        <!-- =================================================
             PAGE CONTENT
        ================================================= -->

        <div class="dashboard-content">


            <!-- PAGE HEADING -->

            <div class="page-heading">

                <div>

                    <h2>
                        Vaccination Schedule
                    </h2>

                    <p>
                        View and manage scheduled vaccinations.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 FLASH MESSAGE
            ================================================= -->

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
                    action="schedules.php"
                    class="bookings-filter-form"
                >


                    <!-- SEARCH -->

                    <div class="booking-filter-group search-filter">

                        <label for="schedule_search">
                            Search
                        </label>

                        <input
                            type="text"
                            id="schedule_search"
                            name="search"
                            placeholder="Child, parent, vaccine or email..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="booking-filter-group">

                        <label for="schedule_status">
                            Status
                        </label>

                        <select
                            id="schedule_status"
                            name="status"
                        >

                            <option value="">
                                All Statuses
                            </option>


                            <option
                                value="Scheduled"
                                <?php
                                echo $status_filter === "Scheduled"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Scheduled
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


                    <!-- FILTER BUTTONS -->

                    <div class="booking-filter-actions">

                        <button
                            type="submit"
                            class="booking-filter-button"
                        >
                            Apply Filters
                        </button>


                        <a
                            href="schedules.php"
                            class="booking-clear-button"
                        >
                            Clear
                        </a>

                    </div>


                </form>


            </div>


            <!-- =================================================
                 SCHEDULE TABLE
            ================================================= -->

            <div class="users-card">


                <div class="users-card-header">

                    <div>

                        <h3>
                            All Vaccination Schedules
                        </h3>

                        <p>
                            Scheduled and completed vaccination appointments
                        </p>

                    </div>

                </div>


                <div class="users-table-wrapper">


                    <table class="users-table schedules-table">


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
                                    $schedule =
                                    mysqli_fetch_assoc($result)
                                ) {

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
                                                    $schedule["child_name"]
                                                );

                                                ?>

                                            </strong>

                                        </td>


                                        <!-- PARENT -->

                                        <td>

                                            <div class="schedule-person-cell">

                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $schedule["parent_name"]
                                                    );

                                                    ?>

                                                </strong>

                                                <span>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $schedule["parent_email"]
                                                    );

                                                    ?>

                                                </span>

                                            </div>

                                        </td>


                                        <!-- VACCINE -->

                                        <td>

                                            <div class="schedule-vaccine-cell">

                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $schedule["vaccine_name"]
                                                    );

                                                    ?>

                                                </strong>

                                                <span>

                                                    Dose
                                                    <?php

                                                    echo (int)
                                                        $schedule["dose_number"];

                                                    ?>

                                                </span>

                                            </div>

                                        </td>


                                        <!-- DATE -->

                                        <td class="schedule-date-cell">

                                            <?php

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $schedule["scheduled_date"]
                                                )
                                            );

                                            ?>

                                        </td>


                                        <!-- TIME -->

                                        <td class="schedule-time-cell">

                                            <?php

                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $schedule["scheduled_time"]
                                                )
                                            );

                                            ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="admin-schedule-status
                                                <?php
                                                echo strtolower(
                                                    $schedule["status"]
                                                );
                                                ?>"
                                            >

                                                <?php

                                                echo htmlspecialchars(
                                                    $schedule["status"]
                                                );

                                                ?>

                                            </span>

                                        </td>


                                        <!-- ACTIONS -->

                                        <td>

                                            <div class="booking-actions">


                                                <!-- VIEW -->

                                                <button
                                                    type="button"
                                                    class="user-action-edit"
                                                    onclick="openViewScheduleModal(
                                                        <?php echo (int)$schedule['id']; ?>,
                                                        <?php echo htmlspecialchars(json_encode($schedule['child_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($schedule['parent_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($schedule['parent_email']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($schedule['vaccine_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo (int)$schedule['dose_number']; ?>,
                                                        <?php echo htmlspecialchars(json_encode($schedule['scheduled_date']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($schedule['scheduled_time']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($schedule['status']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?php echo htmlspecialchars(json_encode($schedule['created_at']), ENT_QUOTES, 'UTF-8'); ?>
                                                    )"
                                                >
                                                    View
                                                </button>


                                                <!-- EDIT -->

                                                <?php if (
                                                    $schedule["status"] === "Scheduled"
                                                ) { ?>

                                                    <button
                                                        type="button"
                                                        class="user-action-activate"
                                                        onclick="openEditScheduleModal(
                                                            <?php echo (int)$schedule['id']; ?>,
                                                            <?php echo htmlspecialchars(json_encode($schedule['child_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                            <?php echo htmlspecialchars(json_encode($schedule['vaccine_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                            <?php echo htmlspecialchars(json_encode($schedule['scheduled_date']), ENT_QUOTES, 'UTF-8'); ?>,
                                                            <?php echo htmlspecialchars(json_encode($schedule['scheduled_time']), ENT_QUOTES, 'UTF-8'); ?>
                                                        )"
                                                    >
                                                        Edit
                                                    </button>

                                                <?php } else { ?>

                                                    <span class="booking-no-action">
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
                                        colspan="8"
                                        class="users-empty"
                                    >

                                        No vaccination schedules found.

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
     VIEW SCHEDULE MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="viewScheduleModal"
>


    <div class="user-modal booking-view-modal">


        <div class="user-modal-header">


            <div>

                <h3>
                    Schedule Details
                </h3>

                <p>
                    View complete vaccination schedule information
                </p>

            </div>


            <button
                type="button"
                class="user-modal-close"
                onclick="closeViewScheduleModal()"
            >
                &times;
            </button>


        </div>


        <div class="user-modal-body">


            <div class="child-details-grid">


                <div class="child-detail-item">

                    <span>
                        Schedule ID
                    </span>

                    <strong
                        id="view_schedule_id"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Status
                    </span>

                    <strong
                        id="view_schedule_status"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Child
                    </span>

                    <strong
                        id="view_schedule_child"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Parent
                    </span>

                    <strong
                        id="view_schedule_parent"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Parent Email
                    </span>

                    <strong
                        id="view_schedule_email"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Vaccine
                    </span>

                    <strong
                        id="view_schedule_vaccine"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Dose
                    </span>

                    <strong
                        id="view_schedule_dose"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Scheduled Date
                    </span>

                    <strong
                        id="view_schedule_date"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Scheduled Time
                    </span>

                    <strong
                        id="view_schedule_time"
                    ></strong>

                </div>


                <div class="child-detail-item">

                    <span>
                        Created On
                    </span>

                    <strong
                        id="view_schedule_created"
                    ></strong>

                </div>


            </div>


        </div>


        <div class="user-modal-footer">

            <button
                type="button"
                class="user-modal-cancel"
                onclick="closeViewScheduleModal()"
            >
                Close
            </button>

        </div>


    </div>


</div>


<!-- =========================================================
     EDIT SCHEDULE MODAL
========================================================= -->

<div
    class="user-modal-overlay"
    id="editScheduleModal"
>


    <div class="user-modal">


        <div class="user-modal-header">


            <div>

                <h3>
                    Edit Vaccination Schedule
                </h3>

                <p>
                    Update the scheduled date and time
                </p>

            </div>


            <button
                type="button"
                class="user-modal-close"
                onclick="closeEditScheduleModal()"
            >
                &times;
            </button>


        </div>


        <form method="POST">
            <?php echo csrf_field(); ?>


            <div class="user-modal-body">


                <input
                    type="hidden"
                    name="edit_schedule"
                    value="1"
                >


                <input
                    type="hidden"
                    name="schedule_id"
                    id="edit_schedule_id"
                >


                <div class="schedule-form-group">

                    <label>
                        Child
                    </label>

                    <input
                        type="text"
                        id="edit_schedule_child"
                        readonly
                    >

                </div>


                <div class="schedule-form-group">

                    <label>
                        Vaccine
                    </label>

                    <input
                        type="text"
                        id="edit_schedule_vaccine"
                        readonly
                    >

                </div>


                <div class="schedule-form-group">

                    <label for="edit_schedule_date">
                        Scheduled Date
                    </label>

                    <input
                        type="date"
                        name="scheduled_date"
                        id="edit_schedule_date"
                        min="<?php echo date('Y-m-d'); ?>"
                        required
                    >

                </div>


                <div class="schedule-form-group">

                    <label for="edit_schedule_time">
                        Scheduled Time
                    </label>

                    <input
                        type="time"
                        name="scheduled_time"
                        id="edit_schedule_time"
                        required
                    >

                </div>


                <div class="schedule-edit-note">

                    Only the scheduled date and time can be changed.
                    Completed vaccination schedules cannot be edited.

                </div>


            </div>


            <div class="user-modal-footer">


                <button
                    type="button"
                    class="user-modal-cancel"
                    onclick="closeEditScheduleModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="user-status-confirm activate"
                >
                    Save Changes
                </button>


            </div>


        </form>


    </div>


</div>


<script>


/* =========================================================
   VIEW SCHEDULE
========================================================= */

function openViewScheduleModal(
    id,
    childName,
    parentName,
    parentEmail,
    vaccineName,
    doseNumber,
    scheduledDate,
    scheduledTime,
    status,
    createdAt
) {


    document.getElementById(
        "view_schedule_id"
    ).textContent =
        "#" + id;


    document.getElementById(
        "view_schedule_status"
    ).textContent =
        status;


    document.getElementById(
        "view_schedule_child"
    ).textContent =
        childName;


    document.getElementById(
        "view_schedule_parent"
    ).textContent =
        parentName;


    document.getElementById(
        "view_schedule_email"
    ).textContent =
        parentEmail;


    document.getElementById(
        "view_schedule_vaccine"
    ).textContent =
        vaccineName;


    document.getElementById(
        "view_schedule_dose"
    ).textContent =
        "Dose " + doseNumber;


    /* -----------------------------------------------------
       DATE
    ----------------------------------------------------- */

    const scheduleDate =
        new Date(scheduledDate);


    document.getElementById(
        "view_schedule_date"
    ).textContent =
        scheduleDate.toLocaleDateString(
            "en-GB",
            {
                day: "2-digit",
                month: "short",
                year: "numeric"
            }
        );


    /* -----------------------------------------------------
       TIME
    ----------------------------------------------------- */

    const timeParts =
        scheduledTime.split(":");


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
        "view_schedule_time"
    ).textContent =
        hours +
        ":" +
        minutes +
        " " +
        period;


    /* -----------------------------------------------------
       CREATED DATE
    ----------------------------------------------------- */

    const createdDate =
        new Date(createdAt);


    document.getElementById(
        "view_schedule_created"
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
            "viewScheduleModal"
        )
        .classList.add("show");

}


function closeViewScheduleModal() {

    document
        .getElementById(
            "viewScheduleModal"
        )
        .classList.remove("show");

}


/* =========================================================
   EDIT SCHEDULE
========================================================= */

function openEditScheduleModal(
    id,
    childName,
    vaccineName,
    scheduledDate,
    scheduledTime
) {


    document.getElementById(
        "edit_schedule_id"
    ).value =
        id;


    document.getElementById(
        "edit_schedule_child"
    ).value =
        childName;


    document.getElementById(
        "edit_schedule_vaccine"
    ).value =
        vaccineName;


    document.getElementById(
        "edit_schedule_date"
    ).value =
        scheduledDate;


    document.getElementById(
        "edit_schedule_time"
    ).value =
        scheduledTime;


    document
        .getElementById(
            "editScheduleModal"
        )
        .classList.add("show");

}


function closeEditScheduleModal() {

    document
        .getElementById(
            "editScheduleModal"
        )
        .classList.remove("show");

}


/* =========================================================
   CLOSE MODALS WHEN CLICKING OUTSIDE
========================================================= */

window.addEventListener(
    "click",
    function(event) {


        if (
            event.target ===
            document.getElementById(
                "viewScheduleModal"
            )
        ) {

            closeViewScheduleModal();

        }


        if (
            event.target ===
            document.getElementById(
                "editScheduleModal"
            )
        ) {

            closeEditScheduleModal();

        }

    }
);


</script>


</body>

</html>


<?php

mysqli_stmt_close($stmt);

?>