<?php
session_start();
require '../Backend/connexion/conn.php'; 

$error = "";
$success = "";
function generateUserId($length = 12) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';

    if (empty($email) || empty($password) || empty($nom) || empty($prenom) || empty($type)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'email n'est pas valide.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        $query = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            $stmt->close();
            $user_id = generateUserId(); // cfr Dada Kibas Génère un ID aléatoire
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (user_id, email, mot_de_passe, nom, prenom, type) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssss", $user_id, $email, $hashed_password, $nom, $prenom, $type);

            if ($stmt->execute()) {
                $stmt->close();

                $matricule = "MAT" . date("Y") . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $statut = "actif";

                $query = "INSERT INTO personnels (id, type, matricule, statut) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssss", $user_id, $type, $matricule, $statut);

                if ($stmt->execute()) {
                    $success = "Inscription réussie. Vous pouvez maintenant vous connecter.";
                } else {
                    $error = "Erreur lors de l'enregistrement dans personnels.";
                }

                $stmt->close();
            } else {
                $error = "Une erreur est survenue lors de l'inscription.";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Guichet Automatique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/global.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="form-container p-4 shadow rounded" style="min-width: 400px; background-color: white;">
        <h4 class="text-center mb-4">Inscription au Système</h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row mb-3">
                <div class="col">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>
                <div class="col">
                    <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <input type="text" name="nom" class="form-control" placeholder="Nom" required>
                </div>
                <div class="col">
                    <input type="text" name="prenom" class="form-control" placeholder="Prénom" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Profil :</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="type" id="professeur" checked value="utilisateur" required>
                    <label class="form-check-label" for="Client">Utilisateur simple</label>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
            </div>
            <p class="text-center mt-3">Vous avez déjà un compte ? <a href="../">Connectez-vous</a></p>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../Js/Inscription.js"></script>
</body>
</html>
