<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "The new guaranteed hash for 'password' is: <br><strong>" . $hash . "</strong>";
?>