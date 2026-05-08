<?php
require_once 'config.php';

// Hancurkan seluruh session
session_unset();
session_destroy();

header('Location: login.php');
exit;
?>