<?php
require_once "../config/config.php";
/*
|--------------------------------------------------------------------------
| Educational Use License (EUL)
|--------------------------------------------------------------------------
| Copyright © 2026 CodeAstro
|
| This file is part of an educational project developed by CodeAstro.
| It is licensed for educational and academic use only.
|
| ❌ Redistribution, re-uploading, commercial use, or removal of this
|    notice is strictly prohibited without written permission.
|
| Author  : CodeAstro
| Website : https://codeastro.com
|--------------------------------------------------------------------------
*/
if (isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    // Fetch the availability data for the user
    $stmt = $conn->prepare("SELECT * FROM availability WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $availability_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Output the availability data
    if ($availability_data) {
        echo "Availability: ";
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            echo ucfirst($day) . ": " . $availability_data[$day] . " | ";
        }
    } else {
        echo "No availability data available.";
    }
} else {
    echo "Invalid request.";
}
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
