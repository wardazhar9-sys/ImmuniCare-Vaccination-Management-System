<?php

require_once "../includes/app.php";

$_SESSION = [];
session_destroy();
setcookie(session_name(), "", time() - 3600, "/");
redirect_to("../login.php");
