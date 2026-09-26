<?php

require_once "../includes/app.php";

// Destroy all session data
session_unset();
session_destroy();
setcookie(session_name(), "", time() - 3600, "/");

// Send user back to login page
header("Location: ../login.php");
exit();

?>