<?php
$host = "localhost";
$user = "u331909252_CkfJY";
$password = "P#MK|0Phg4"; 
$dbname = "u331909252_t5z3E";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}
?>
