<?php
session_start();
require 'Backend/connexion/conn.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $query = "SELECT id, email, mot_de_passe, nom, prenom, type FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['type'] = $user['type'];

                header("Location: home.php");
                exit();
            } else {
                $error = "Mot de passe incorrect.";
            }
        } else {
            $error = "Aucun compte trouvé avec cet email.";
        }

        $stmt->close();
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon_io/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="favicon_io/favicon-16x16.png" />
    <title>Système d'un Guichet automatique</title>
</head>
<body>
    <main class="d-flex justify-content-center align-items-center vh-100">
        <article class="form-container parent-Basic p-4 rounded shadow bg-light text-center">
            <form action="" method="POST">
                <div class="mb-3 input-group">
                    <input type="text" class="form-control" name="email" placeholder="Email" required>
                </div>
                <div class="mb-3 input-group">
                    <input type="password" class="form-control" name="password" placeholder="Mot de passe" required>
                </div>
                <p class="text-danger Description_Erreur"><?= $error; ?></p>
                <p class="text-muted">En vous connectant, vous acceptez nos règles.</p>
                <button type="submit" class="btn btn-primary w-100 mb-2">Se connecter</button>
            </form>
            <p class="text-muted">Besoin d'un compte ?</p>
            <a href="pages/inscription.php"><button class="btn btn-success w-100">S'inscrire</button></a>
        </article>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="Js/script.js"></script>
</body>
</html>
