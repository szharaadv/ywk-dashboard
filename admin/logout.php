<?php
session_start();
require_once 'config.php';
unset($_SESSION[ADMIN_SESSION_KEY]);
header('Location: login.php');
exit;