<?php
require_once "../config/config.php";

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
