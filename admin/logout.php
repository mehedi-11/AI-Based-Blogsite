<?php
session_start();
session_unset();
session_destroy();
require_once '../includes/config.php';
header("Location: login.php");
exit;
