<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Unable to Delete - ImmuniCare</title>

    <link rel="stylesheet" href="../Assets/css/style.css">

    <style>

        .delete-error-container {
            max-width: 650px;
            margin: 100px auto;
            padding: 0 25px;
        }

        .delete-error-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 45px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(11, 31, 58, 0.08);
        }

        .error-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 22px;
            border-radius: 50%;
            background: #FEF2F2;
            color: #DC2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .delete-error-card h1 {
            margin: 0 0 12px;
            color: #0B1F3A;
            font-size: 26px;
        }

        .delete-error-card p {
            margin: 0 auto 28px;
            max-width: 500px;
            color: #64748B;
            font-size: 15px;
            line-height: 1.7;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1E40AF;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .back-btn:hover {
            background: #0B1F3A;
        }

        @media (max-width: 600px) {

            .delete-error-container {
                margin: 50px auto;
                padding: 0 15px;
            }

            .delete-error-card {
                padding: 30px 20px;
            }

        }

    </style>

</head>

<body>

    <div class="delete-error-container">

        <div class="delete-error-card">

            <div class="error-icon">
                ⚠
            </div>

            <h1>Unable to Delete Child</h1>

            <p>
                <?php echo htmlspecialchars($error_message); ?>
            </p>

            <a href="children.php" class="back-btn">
                Back to My Children
            </a>

        </div>

    </div>

</body>

</html>