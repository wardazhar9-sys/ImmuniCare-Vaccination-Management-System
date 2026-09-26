<?php

require_once "../includes/app.php";

$_SESSION = [];
session_destroy();
redirect_to("../login.php");
