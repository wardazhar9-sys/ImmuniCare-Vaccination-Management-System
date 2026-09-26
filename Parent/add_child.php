<?php

require_once "../includes/app.php";
require_role($conn, "parent");
redirect_to("children.php");
