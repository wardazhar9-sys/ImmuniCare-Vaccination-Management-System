<?php

session_start();

include("../config/db.php");


// Make sure the user is logged in as a parent
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "parent") {
    header("Location: ../login.php");
    exit();
}


// Get the logged-in parent's ID
$parent_id = $_SESSION["user_id"];


// Make sure a child ID was submitted
if (!isset($_POST["child_id"])) {
    header("Location: children.php");
    exit();
}

$child_id = $_POST["child_id"];


// Check if the child has any bookings
$booking_sql = "SELECT id
                FROM bookings
                WHERE child_id = ?";

$booking_stmt = mysqli_prepare($conn, $booking_sql);

mysqli_stmt_bind_param(
    $booking_stmt,
    "i",
    $child_id
);

mysqli_stmt_execute($booking_stmt);

$booking_result = mysqli_stmt_get_result($booking_stmt);


// If bookings exist, do not delete the child
if (mysqli_num_rows($booking_result) > 0) {

    $error_message = "This child cannot be deleted because they have existing vaccination bookings.";

    include("delete_error.php");
    exit();

}

// Check if the child has any vaccination records
$record_sql = "SELECT id
               FROM vaccination_records
               WHERE child_id = ?";

$record_stmt = mysqli_prepare($conn, $record_sql);

mysqli_stmt_bind_param(
    $record_stmt,
    "i",
    $child_id
);

mysqli_stmt_execute($record_stmt);

$record_result = mysqli_stmt_get_result($record_stmt);

// If vaccination records exist, do not delete the child
if (mysqli_num_rows($record_result) > 0) {

    $error_message = "This child cannot be deleted because they have existing vaccination records.";

    include("delete_error.php");
    exit();

}

// Delete only the child belonging to the logged-in parent
$sql = "DELETE FROM children
        WHERE id = ?
        AND parent_id = ?";


$stmt = mysqli_prepare($conn, $sql);


mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $child_id,
    $parent_id
);


if (mysqli_stmt_execute($stmt)) {

    header("Location: children.php");
    exit();

} else {

    echo "Error deleting child: " . mysqli_error($conn);

}

?>