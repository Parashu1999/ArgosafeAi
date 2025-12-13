<?php
// logout.php (The central file)
session_start();
session_destroy();

// Check if the request came from the Admin panel
if (isset($_GET['redirect']) && $_GET['redirect'] === 'admin') {
    // Send Admin users back to the Admin login page
    header("Location: admin/login.php");
    exit();
} else {
    // Default: Send regular users back to the main site login/index
    header("Location: index.php"); 
    exit();
}
?>