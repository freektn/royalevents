<?php
require_once 'includes/auth.php';

// Log out user
logout();

// Redirect to login page
header('Location: login.php');
exit;