<?php
require_once '../config/db.php';
$db = getDB();
echo $db->query("SELECT 1")->fetch() ? 'DB OK' : 'DB ERROR';
?>