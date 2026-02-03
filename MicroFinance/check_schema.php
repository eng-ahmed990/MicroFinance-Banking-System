<?php
require('includes/db_connect.php');
$result = $conn->query("DESCRIBE users");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
