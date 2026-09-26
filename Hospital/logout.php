<?php

require_once "../includes/app.php";

// Remove all session variables for the current logged-in user.
session_unset();

// Destroy the session so protected pages cannot use the old login.
session_destroy();
setcookie(session_name(), "", time() - 3600, "/");

header("Location: ../login.php");
exit();

?>
