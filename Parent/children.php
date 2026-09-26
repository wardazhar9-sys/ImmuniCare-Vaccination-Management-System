<?php

require_once "../includes/app.php";

$user = require_role($conn, "parent");
$parent_id = (int)$user["id"];

$message = "";
$message_type = "";
$updated_message = isset($_GET["updated"]);

if (isset($_POST["update_child"])) {
    verify_csrf();
    $child_id = post_int("child_id");
    $child_name = post_string("child_name", 100);
    $date_of_birth = post_string("date_of_birth", 10);
    $gender = post_string("gender", 20);
    $blood_group = post_string("blood_group", 10);
    $address = post_string("address", 500);

    $valid = $child_id > 0 &&
        $child_name !== "" &&
        valid_date($date_of_birth) &&
        $date_of_birth <= date("Y-m-d") &&
        in_array($gender, ["Male", "Female"], true) &&
        in_array($blood_group, ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"], true) &&
        $address !== "";

    if (!$valid) {
        $message = "Enter valid child information.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare(
            "UPDATE children SET child_name = ?, date_of_birth = ?, gender = ?,
             blood_group = ?, address = ?
             WHERE id = ? AND parent_id = ? AND archived_at IS NULL"
        );
        $stmt->bind_param(
            "sssssii",
            $child_name,
            $date_of_birth,
            $gender,
            $blood_group,
            $address,
            $child_id,
            $parent_id
        );
        $updated = $stmt->execute();
        $stmt->close();

        $message = $updated ? "Child information updated." : "Unable to update child.";
        $message_type = $updated ? "success" : "error";
        if ($updated) {
            audit($conn, $parent_id, "child.updated", "child", $child_id);
            header("Location: children.php?updated=1");
            exit();
        }
    }
}


/* ==========================================
   ADD CHILD
========================================== */

if (isset($_POST["add_child"])) {
    verify_csrf();
    $child_name = post_string("child_name", 100);
    $date_of_birth = post_string("date_of_birth", 10);
    $gender = post_string("gender", 20);
    $blood_group = post_string("blood_group", 10);
    $address = post_string("address", 500);

    // Check if all fields are filled
    if (
        empty($child_name) ||
        empty($date_of_birth) ||
        empty($gender) ||
        empty($blood_group) ||
        empty($address) ||
        !valid_date($date_of_birth) ||
        $date_of_birth > date("Y-m-d") ||
        !in_array($gender, ["Male", "Female"], true) ||
        !in_array($blood_group, ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"], true)
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

    // Clear the form after successful registration
    $child_name = "";
    $date_of_birth = "";
    $gender = "";
    $blood_group = "";
    $address = "";

} else {

    $message = "Something went wrong. Please try again.";
    $message_type = "error";
}

        mysqli_stmt_close($stmt);
    }
}


/* ==========================================
   GET REGISTERED CHILDREN
========================================== */

$stmt = $conn->prepare(
    "SELECT id, child_name, date_of_birth, gender, blood_group, address
     FROM children
     WHERE parent_id = ? AND archived_at IS NULL
     ORDER BY child_name ASC"
);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();

if ($updated_message) {
    $message = "Child information updated.";
    $message_type = "success";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Children - ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>

        /* ==============================
           PAGE
        ============================== */

        body {
            background: #F5F7FA;
            color: #0F172A;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        .children-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 45px 30px 60px;
        }


        /* ==============================
           PAGE HEADER
        ============================== */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            gap: 25px;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .page-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #DBEAFE;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1E40AF;
        }

        .page-icon svg {
            width: 34px;
            height: 34px;
        }

        .page-title {
            margin: 0;
            font-size: 40px;
            font-weight: 700;
            color: #0B1F3A;
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            margin: 7px 0 0;
            color: #64748B;
            font-size: 17px;
        }


        /* ==============================
           ADD CHILD BUTTON
        ============================== */

        .add-child-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #1E40AF;
            color: white;
            text-decoration: none;
            padding: 15px 23px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            transition: 0.2s ease;
            box-shadow: 0 5px 15px rgba(30, 64, 175, 0.20);
        }

        .add-child-btn:hover {
            background: #0B1F3A;
            transform: translateY(-2px);
        }

        .add-child-btn svg {
            width: 21px;
            height: 21px;
        }


        /* ==============================
           CHILD CARD
        ============================== */

        .child-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 30px rgba(11, 31, 58, 0.08);
        }


        /* ==============================
           CHILD PROFILE HEADER
        ============================== */

        .child-profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #E2E8F0;
        }

        .child-profile-left {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .child-avatar {
            width: 125px;
            height: 125px;
            border-radius: 50%;
            background: #DBEAFE;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 65px;
            flex-shrink: 0;
        }

        .child-name {
            margin: 0;
            color: #0B1F3A;
            font-size: 38px;
            font-weight: 700;
        }

        .child-profile-label {
            margin: 5px 0 12px;
            color: #64748B;
            font-size: 18px;
        }


        /* ==============================
           GENDER BADGE
        ============================== */

        .gender-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #DBEAFE;
            color: #1E40AF;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 700;
        }

        .gender-badge svg {
            width: 18px;
            height: 18px;
        }


        /* ==============================
           ACTION BUTTONS
        ============================== */

        .child-actions {
            display: flex;
            gap: 15px;
        }

        .action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 22px;
    border-radius: 11px;
    background: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s ease;
    text-decoration: none;
}
        .edit-btn {
            color: #1E40AF;
            border: 1.5px solid #1E40AF;
        }

        .edit-btn:hover {
            background: #DBEAFE;
        }

        .delete-btn {
            color: #DC2626;
            border: 1.5px solid #DC2626;
        }

        .delete-btn:hover {
            background: #FEF2F2;
        }

        .action-btn svg {
            width: 18px;
            height: 18px;
        }


        /* ==============================
           CHILD DETAILS
        ============================== */

        .child-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 25px;
        }

        .child-detail {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 20px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 15px;
            transition: 0.2s ease;
        }

        .child-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(11, 31, 58, 0.05);
        }


        /* ==============================
           DETAIL ICONS
        ============================== */

        .detail-icon {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .detail-icon svg {
            width: 27px;
            height: 27px;
        }

        .date-icon {
            background: #DBEAFE;
            color: #2563EB;
        }

        .gender-icon {
            background: #DBEAFE;
            color: #2563EB;
        }

        .blood-icon {
            background: #FEE2E2;
            color: #DC2626;
        }

        .address-icon {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .detail-label {
            margin: 0 0 5px;
            color: #64748B;
            font-size: 15px;
            font-weight: 600;
        }

        .detail-value {
            margin: 0;
            color: #0F172A;
            font-size: 18px;
            font-weight: 500;
        }


        /* ==============================
           INFORMATION BANNER
        ============================== */

        .info-banner {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 25px;
            padding: 18px 22px;
            background: #EFF6FF;
            border-radius: 14px;
            color: #1E40AF;
        }

        .info-banner-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #BFDBFE;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-banner-icon svg {
            width: 21px;
            height: 21px;
        }

        .info-banner p {
            margin: 0;
            font-size: 15px;
            font-weight: 500;
        }


        /* ==============================
           NO CHILDREN
        ============================== */

        .no-children {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 18px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(11, 31, 58, 0.06);
        }

        .no-children h2 {
            color: #0B1F3A;
            margin-bottom: 10px;
        }

        .no-children p {
            color: #64748B;
            margin-bottom: 25px;
        }


        /* ==============================
           RESPONSIVE DESIGN
        ============================== */

        @media (max-width: 800px) {

            .children-container {
                padding: 30px 18px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .add-child-btn {
                width: 100%;
                justify-content: center;
            }

            .child-profile-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .child-actions {
                width: 100%;
            }

            .action-btn {
                flex: 1;
            }

            .child-details {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 32px;
            }

            .child-name {
                font-size: 30px;
            }

        }

        @media (max-width: 500px) {

            .page-header-left {
                align-items: flex-start;
            }

            .page-icon {
                width: 52px;
                height: 52px;
            }

            .page-icon svg {
                width: 28px;
                height: 28px;
            }

            .child-profile-left {
                flex-direction: column;
                align-items: flex-start;
            }

            .child-avatar {
                width: 100px;
                height: 100px;
                font-size: 52px;
            }

            .child-card {
                padding: 20px;
            }

            .child-actions {
                flex-direction: column;
            }

        }

        /* ==============================
   ADD CHILD MODAL
============================== */

body.modal-open {
    overflow: hidden;
}

.child-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.child-modal.show {
    display: flex;
}

.child-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(11, 31, 58, 0.60);
    backdrop-filter: blur(4px);
}

.child-modal-content {
    position: relative;
    width: 100%;
    max-width: 650px;
    max-height: 90vh;
    overflow-y: auto;
    background: #FFFFFF;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 20px 60px rgba(11, 31, 58, 0.25);
    z-index: 2;
    animation: modalSlideIn 0.25s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}


/* MODAL HEADER */

.child-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #E2E8F0;
}

.child-modal-header h2 {
    margin: 0;
    color: #0B1F3A;
    font-size: 25px;
    font-weight: 700;
}

.child-modal-header p {
    margin: 6px 0 0;
    color: #64748B;
    font-size: 14px;
}

.child-modal-close {
    width: 38px;
    height: 38px;
    border: none;
    border-radius: 50%;
    background: #F1F5F9;
    color: #64748B;
    font-size: 25px;
    line-height: 1;
    cursor: pointer;
    transition: 0.2s ease;
    flex-shrink: 0;
}

.child-modal-close:hover {
    background: #E2E8F0;
    color: #0B1F3A;
}


/* FORM */

.add-child-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.add-child-form-group {
    display: flex;
    flex-direction: column;
}

.add-child-form-group.full-width {
    grid-column: 1 / -1;
}

.add-child-form-group label {
    margin-bottom: 8px;
    color: #0F172A;
    font-size: 14px;
    font-weight: 600;
}

.add-child-form-group input,
.add-child-form-group select,
.add-child-form-group textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 13px 14px;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    background: #FFFFFF;
    color: #0F172A;
    font-family: Arial, sans-serif;
    font-size: 14px;
    outline: none;
    transition: 0.2s ease;
}

.add-child-form-group input:focus,
.add-child-form-group select:focus,
.add-child-form-group textarea:focus {
    border-color: #1E40AF;
    box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.10);
}

.add-child-form-group textarea {
    resize: vertical;
    min-height: 100px;
}


/* SUBMIT BUTTON */

.add-child-submit {
    grid-column: 1 / -1;
    width: 100%;
    border: none;
    padding: 14px 20px;
    border-radius: 11px;
    background: #1E40AF;
    color: #FFFFFF;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s ease;
}

.add-child-submit:hover {
    background: #0B1F3A;
    transform: translateY(-1px);
}


/* MESSAGE */

.modal-message {
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
}

.modal-message.success {
    background: #DCFCE7;
    color: #166534;
    border: 1px solid #BBF7D0;
}

.modal-message.error {
    background: #FEE2E2;
    color: #991B1B;
    border: 1px solid #FECACA;
}


/* MOBILE */

@media (max-width: 650px) {

    .child-modal {
        padding: 12px;
    }

    .child-modal-content {
        padding: 22px;
        max-height: 92vh;
    }

    .add-child-form {
        grid-template-columns: 1fr;
    }

    .add-child-form-group.full-width {
        grid-column: auto;
    }

    .add-child-submit {
        grid-column: auto;
    }

    .child-modal-header h2 {
        font-size: 21px;
    }
}
    </style>

</head>


<body>
<div class="parent-dashboard">
<?php include "sidebar.php"; ?>
<main class="dashboard-main">
<?php include "../includes/portal_header.php"; ?>
<div class="children-container">


    <!-- ==============================
         PAGE HEADER
    =============================== -->

    <div class="page-header">

        <div class="page-header-left">

            <div class="page-icon">

                <!-- People Icon -->
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <circle cx="9" cy="7" r="3"></circle>
                    <circle cx="17" cy="8" r="2.5"></circle>

                    <path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path>
                    <path d="M14 15c.9-.6 1.9-.9 3-.9 2.8 0 5 2.2 5 5"></path>

                </svg>

            </div>


            <div>

                <h1 class="page-title">
                    My Children
                </h1>

                <p class="page-subtitle">
                    View and manage your registered children.
                </p>

            </div>

        </div>


        <button type="button" class="add-child-btn" onclick="openAddChildModal()">

    <!-- Plus Icon -->
    <svg viewBox="0 0 24 24" fill="none"
         stroke="currentColor"
         stroke-width="2.5"
         stroke-linecap="round">

        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>

    </svg>

    Add New Child

</button>

    </div>



    <!-- ==============================
         CHILDREN
    =============================== -->

    <?php if (mysqli_num_rows($result) > 0): ?>

        <?php while ($row = mysqli_fetch_assoc($result)): ?>


            <div class="child-card">


                <!-- CHILD PROFILE HEADER -->

                <div class="child-profile-header">


                    <div class="child-profile-left">


                        <div class="child-avatar">
                            👦
                        </div>


                        <div>

                            <h2 class="child-name">
                                <?php echo htmlspecialchars($row["child_name"]); ?>
                            </h2>

                            <p class="child-profile-label">
                                Child Profile
                            </p>


                            <span class="gender-badge">

                                <!-- Person Icon -->
                                <svg viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2">

                                    <circle cx="12" cy="7" r="4"></circle>
                                    <path d="M5 21c0-4 3-7 7-7s7 3 7 7"></path>

                                </svg>

                                <?php echo htmlspecialchars($row["gender"]); ?>

                            </span>

                        </div>

                    </div>



                    <!-- ACTION BUTTONS -->

    <div class="child-actions">

    <button type="button" class="action-btn edit-btn"
            onclick="openEditChildModal(
                <?php echo (int)$row['id']; ?>,
                <?php echo htmlspecialchars(json_encode($row['child_name']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($row['date_of_birth']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($row['gender']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($row['blood_group']), ENT_QUOTES, 'UTF-8'); ?>,
                <?php echo htmlspecialchars(json_encode($row['address']), ENT_QUOTES, 'UTF-8'); ?>
            )">

    <!-- Edit Icon -->
    <svg viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="2"
         stroke-linecap="round"
         stroke-linejoin="round">

        <path d="M12 20h9"></path>
        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>

    </svg>

    Edit

</button>


<form method="POST" action="delete_child.php" style="display: inline;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="child_id" value="<?php echo $row['id']; ?>">

    <button type="submit" class="action-btn delete-btn"
            onclick="return confirm('Are you sure you want to delete this child?');">
        <!-- your existing delete icon, if you have one -->
        Delete
    </button>
</form>

                    </div>

                </div>



                <!-- CHILD DETAILS -->

                <div class="child-details">


                    <!-- DATE OF BIRTH -->

                    <div class="child-detail">

                        <div class="detail-icon date-icon">

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2"
                                 stroke-linecap="round"
                                 stroke-linejoin="round">

                                <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>

                            </svg>

                        </div>


                        <div>

                            <p class="detail-label">
                                Date of Birth
                            </p>

                            <p class="detail-value">
                                <?php echo htmlspecialchars($row["date_of_birth"]); ?>
                            </p>

                        </div>

                    </div>



                    <!-- GENDER -->

                    <div class="child-detail">

                        <div class="detail-icon gender-icon">

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2"
                                 stroke-linecap="round"
                                 stroke-linejoin="round">

                                <circle cx="10" cy="14" r="5"></circle>
                                <line x1="14" y1="10" x2="20" y2="4"></line>
                                <polyline points="15 4 20 4 20 9"></polyline>

                            </svg>

                        </div>


                        <div>

                            <p class="detail-label">
                                Gender
                            </p>

                            <p class="detail-value">
                                <?php echo htmlspecialchars($row["gender"]); ?>
                            </p>

                        </div>

                    </div>



                    <!-- BLOOD GROUP -->

                    <div class="child-detail">

                        <div class="detail-icon blood-icon">

                            <svg viewBox="0 0 24 24"
                                 fill="currentColor">

                                <path d="M12 2
                                         C12 2 5 10 5 15
                                         C5 19 8 22 12 22
                                         C16 22 19 19 19 15
                                         C19 10 12 2 12 2Z">
                                </path>

                            </svg>

                        </div>


                        <div>

                            <p class="detail-label">
                                Blood Group
                            </p>

                            <p class="detail-value">
                                <?php echo htmlspecialchars($row["blood_group"]); ?>
                            </p>

                        </div>

                    </div>



                    <!-- ADDRESS -->

                    <div class="child-detail">

                        <div class="detail-icon address-icon">

                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2"
                                 stroke-linecap="round"
                                 stroke-linejoin="round">

                                <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path>
                                <circle cx="12" cy="10" r="2.5"></circle>

                            </svg>

                        </div>


                        <div>

                            <p class="detail-label">
                                Address
                            </p>

                            <p class="detail-value">
                                <?php echo htmlspecialchars($row["address"]); ?>
                            </p>

                        </div>

                    </div>


                </div>



                <!-- INFORMATION BANNER -->

                <div class="info-banner">

                    <div class="info-banner-icon">

                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2.5"
                             stroke-linecap="round">

                            <circle cx="12" cy="12" r="9"></circle>
                            <line x1="12" y1="11" x2="12" y2="16"></line>
                            <line x1="12" y1="7" x2="12.01" y2="7"></line>

                        </svg>

                    </div>

                    <p>
                        Keep your child's information up to date to receive accurate vaccination reminders.
                    </p>

                </div>


            </div>


        <?php endwhile; ?>


    <?php else: ?>


        <div class="no-children">

            <h2>
                No Children Registered
            </h2>

            <p>
                You haven't registered any children yet.
            </p>

            <button type="button" class="add-child-btn" onclick="openAddChildModal()">

    <!-- Plus Icon -->
    <svg viewBox="0 0 24 24" fill="none"
         stroke="currentColor"
         stroke-width="2.5"
         stroke-linecap="round">

        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>

    </svg>

    Add New Child

</button>

        </div>


    <?php endif; ?>


</div>

<!-- ==========================================
     ADD CHILD MODAL
========================================== -->

<div id="addChildModal" class="child-modal">

    <div class="child-modal-overlay" onclick="closeAddChildModal()"></div>

    <div class="child-modal-content">

        <div class="child-modal-header">

            <div>
                <h2>Register Your Child</h2>

                <p>
                    Add your child's information to ImmuniCare
                </p>
            </div>

            <button
                type="button"
                class="child-modal-close"
                onclick="closeAddChildModal()"
            >
                &times;
            </button>

        </div>


        <?php if ($message != ""): ?>

            <div class="modal-message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <form method="POST" class="add-child-form">
            <?php echo csrf_field(); ?>


            <!-- CHILD NAME -->

            <div class="add-child-form-group full-width">

                <label>Child Name</label>

                <input
                    type="text"
                    name="child_name"
                    placeholder="Enter child's full name"
                    value="<?php echo htmlspecialchars($child_name ?? ''); ?>"
                    maxlength="100"
                    required
                >

            </div>


            <!-- DATE OF BIRTH -->

            <div class="add-child-form-group">

                <label>Date of Birth</label>

                <input
                    type="date"
                    name="date_of_birth"
                    value="<?php echo htmlspecialchars($date_of_birth ?? ''); ?>"
                    max="<?php echo date('Y-m-d'); ?>"
                    required
                >

            </div>


            <!-- GENDER -->

            <div class="add-child-form-group">

                <label>Gender</label>

                <select id="add_child_gender" name="gender" required>

                    <option value="">Select Gender</option>

                    <option value="Male"
                        <?php echo (($gender ?? '') == 'Male') ? 'selected' : ''; ?>>
                        Male
                    </option>

                    <option value="Female"
                        <?php echo (($gender ?? '') == 'Female') ? 'selected' : ''; ?>>
                        Female
                    </option>

                </select>

            </div>


            <!-- BLOOD GROUP -->

            <div class="add-child-form-group">

                <label>Blood Group</label>

                <select id="add_child_blood_group" name="blood_group" required>

                    <option value="">Select Blood Group</option>

                    <option value="A+"
                        <?php echo (($blood_group ?? '') == 'A+') ? 'selected' : ''; ?>>
                        A+
                    </option>

                    <option value="A-"
                        <?php echo (($blood_group ?? '') == 'A-') ? 'selected' : ''; ?>>
                        A-
                    </option>

                    <option value="B+"
                        <?php echo (($blood_group ?? '') == 'B+') ? 'selected' : ''; ?>>
                        B+
                    </option>

                    <option value="B-"
                        <?php echo (($blood_group ?? '') == 'B-') ? 'selected' : ''; ?>>
                        B-
                    </option>

                    <option value="AB+"
                        <?php echo (($blood_group ?? '') == 'AB+') ? 'selected' : ''; ?>>
                        AB+
                    </option>

                    <option value="AB-"
                        <?php echo (($blood_group ?? '') == 'AB-') ? 'selected' : ''; ?>>
                        AB-
                    </option>

                    <option value="O+"
                        <?php echo (($blood_group ?? '') == 'O+') ? 'selected' : ''; ?>>
                        O+
                    </option>

                    <option value="O-"
                        <?php echo (($blood_group ?? '') == 'O-') ? 'selected' : ''; ?>>
                        O-
                    </option>

                </select>

            </div>


            <!-- ADDRESS -->

            <div class="add-child-form-group full-width">

                <label>Address</label>

                <textarea
                    name="address"
                    placeholder="Enter child's address"
                    rows="4"
                    maxlength="500"
                    required
                ><?php echo htmlspecialchars($address ?? ''); ?></textarea>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                name="add_child"
                class="add-child-submit"
            >
                Register Child
            </button>


        </form>

    </div>

</div>
</main>
</div>

<div class="user-modal-overlay" id="editChildModal">
    <div class="user-modal">
        <div class="user-modal-header">
            <div>
                <h2>Edit Child</h2>
                <p>Update your child's profile without leaving this page.</p>
            </div>
            <button type="button" class="user-modal-close" onclick="closeEditChildModal()">&times;</button>
        </div>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="user-modal-body">
                <input type="hidden" name="update_child" value="1">
                <input type="hidden" id="parent_edit_child_id" name="child_id">
                <div class="user-form-group">
                    <label for="parent_edit_child_name">Child Name</label>
                    <input id="parent_edit_child_name" name="child_name" maxlength="100" required>
                </div>
                <div class="user-form-group">
                    <label for="parent_edit_child_dob">Date of Birth</label>
                    <input id="parent_edit_child_dob" type="date" name="date_of_birth" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="user-form-group">
                    <label for="parent_edit_child_gender">Gender</label>
                    <select id="parent_edit_child_gender" name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="user-form-group">
                    <label for="parent_edit_child_blood">Blood Group</label>
                    <select id="parent_edit_child_blood" name="blood_group" required>
                        <?php foreach (["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"] as $blood): ?>
                            <option value="<?php echo e($blood); ?>"><?php echo e($blood); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="user-form-group">
                    <label for="parent_edit_child_address">Address</label>
                    <textarea id="parent_edit_child_address" name="address" rows="3" maxlength="500" required></textarea>
                </div>
            </div>
            <div class="user-modal-footer">
                <button type="button" class="user-modal-cancel" onclick="closeEditChildModal()">Cancel</button>
                <button type="submit" class="user-modal-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>

function openEditChildModal(id, name, dob, gender, blood, address) {
    document.getElementById("parent_edit_child_id").value = id;
    document.getElementById("parent_edit_child_name").value = name;
    document.getElementById("parent_edit_child_dob").value = dob;
    document.getElementById("parent_edit_child_gender").value = gender;
    document.getElementById("parent_edit_child_blood").value = blood;
    document.getElementById("parent_edit_child_address").value = address;
    document.getElementById("editChildModal").classList.add("show");
    document.body.classList.add("modal-open");
}

function closeEditChildModal() {
    document.getElementById("editChildModal").classList.remove("show");
    document.body.classList.remove("modal-open");
}

function openAddChildModal() {

    document.getElementById("addChildModal").classList.add("show");

    document.body.classList.add("modal-open");
}


function closeAddChildModal() {

    document.getElementById("addChildModal").classList.remove("show");

    document.body.classList.remove("modal-open");
}


// Close modal with Escape key

document.addEventListener("keydown", function(event) {

    if (event.key === "Escape") {

        closeAddChildModal();
        closeEditChildModal();

    }

});

document.getElementById("editChildModal").addEventListener("click", function(event) {
    if (event.target === this) closeEditChildModal();
});


<?php if ($message != "" && isset($_POST["add_child"])): ?>

    // Automatically open modal when there is a success/error message
    openAddChildModal();

<?php endif; ?>

</script>


</body>

</html>