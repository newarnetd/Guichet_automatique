<?php
require 'Backend/connexion/conn.php';  

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $userId = (int) $_GET['id'];

    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            
            header("Location: admin.php");
            exit();
        } else {
            echo "Aucun utilisateur trouvé avec cet ID.";
            die;
        }
    } else {
        echo "Erreur lors de la préparation de la requête.";
    }
} else {
    echo "ID invalide ou manquant.";
}
