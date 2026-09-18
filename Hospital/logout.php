<?php

session_start();

// Remove all session variables for the current logged-in user.
session_unset();

// Destroy the session so protected pages cannot use the old login.
session_destroy();

header("Location: ../login.php");
exit();

?>
