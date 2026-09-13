<?php

session_start();

include("../config/db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "parent") {
    header("Location: ../login.php");
    exit();
}

$parent_id = $_SESSION["user_id"];

$sql = "SELECT id, child_name, date_of_birth, gender, blood_group, address
        FROM children
        WHERE parent_id = '$parent_id'
        ORDER BY child_name ASC";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Children - ImmuniCare</title>

    <link rel="stylesheet" href="../Assets/css/style.css">

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

    </style>

</head>


<body>

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


        <a href="add_child.php" class="add-child-btn">

            <!-- Plus Icon -->
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor"
                 stroke-width="2.5"
                 stroke-linecap="round">

                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>

            </svg>

            Add New Child

        </a>

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

    <a href="edit_child.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">

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

</a>


                        <button type="button" class="action-btn delete-btn">

                            <!-- Delete Icon -->
                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2"
                                 stroke-linecap="round"
                                 stroke-linejoin="round">

                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14H5V6"></path>
                                <path d="M10 11v6"></path>
                                <path d="M14 11v6"></path>
                                <path d="M9 6V3h6v3"></path>

                            </svg>

                            Delete

                        </button>

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

            <a href="add_child.php" class="add-child-btn">
                + Add New Child
            </a>

        </div>


    <?php endif; ?>


</div>

</body>

</html>