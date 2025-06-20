<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$dbname = "guichet_automatique";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";
$queryUser = "SELECT nom, email FROM users WHERE id = ?";
$stmtUser = $conn->prepare($queryUser);
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

if ($resultUser->num_rows === 1) {
    $user = $resultUser->fetch_assoc();
} else {
    die("Utilisateur introuvable.");
}

// Récupérer les infos compte bancaire
$queryCompte = "SELECT limite_retrait, statut_compte, devise FROM comptebancaire WHERE clientId = ?";
$stmtCompte = $conn->prepare($queryCompte);
$stmtCompte->bind_param("i", $user_id);
$stmtCompte->execute();
$resultCompte = $stmtCompte->get_result();

if ($resultCompte->num_rows === 1) {
    $compte = $resultCompte->fetch_assoc();
} else {
    $compte = [
        'limite_retrait' => '',
        'statut_compte' => '',
        'devise' => ''
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $limite_retrait = trim($_POST['limite_retrait'] ?? '');
    $statut_compte = $_POST['statut_compte'] ?? '';
    $devise_utilisee = $_POST['devise_utilisee'] ?? '';

    if ($nom === '' || $email === '') {
        $error = "Nom et email sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif (!preg_match('/^\d{1,3}$/', $limite_retrait)) {
        $error = "La limite de retrait doit être un nombre entre 1 et 999.";
    } elseif (!in_array($statut_compte, ['Désactivé', 'Activé', 'Bloqué'])) {
        $error = "Statut du compte invalide.";
    } elseif (!in_array($devise_utilisee, ['EUR', 'USD', 'BIF', 'XOF'])) {
        $error = "Devise utilisée invalide.";
    } else {
        $updateUser = "UPDATE users SET nom = ?, email = ? WHERE id = ?";
        $stmtUpdateUser = $conn->prepare($updateUser);
        if (!$stmtUpdateUser) {
            $error = "Erreur préparation requête utilisateur : " . $conn->error;
        } else {
            $stmtUpdateUser->bind_param("ssi", $nom, $email, $user_id);
            $stmtUpdateUser->execute();
            $stmtUpdateUser->close();
            $updateCompte = "UPDATE comptebancaire SET limite_retrait = ?, statut_compte = ?, devise = ? WHERE clientId = ?";
            $stmtUpdateCompte = $conn->prepare($updateCompte);
            if (!$stmtUpdateCompte) {
                $error = "Erreur préparation requête compte bancaire : " . $conn->error;
            } else {
                $stmtUpdateCompte->bind_param("issi", $limite_retrait, $statut_compte, $devise_utilisee, $user_id);
                if ($stmtUpdateCompte->execute()) {
                    $success = "Informations mises à jour avec succès.";
                    $user['nom'] = $nom;
                    $user['email'] = $email;
                    $compte['limite_retrait'] = $limite_retrait;
                    $compte['statut_compte'] = $statut_compte;
                    $compte['devise'] = $devise_utilisee;
                } else {
                    $error = "Erreur lors de la mise à jour du compte bancaire : " . $stmtUpdateCompte->error;
                }
                $stmtUpdateCompte->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Paramètres utilisateur - Guichet Automatique</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body {
      background-color: #f4f6f9;
    }
    .container {
      max-width: 800px;
      margin-top: 50px;
    }
    .form-section {
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="form-section">

      <h3 class="mb-4">Modifier les informations utilisateur et paramètres du compte bancaire</h3>

      <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form action="" method="POST" novalidate>
        <div class="mb-3">
          <label for="nom" class="form-label">Nom</label>
          <input
            type="text"
            class="form-control"
            id="nom"
            name="nom"
            required
            value="<?= htmlspecialchars($user['nom']) ?>"
          >
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input
            type="email"
            class="form-control"
            id="email"
            name="email"
            required
            value="<?= htmlspecialchars($user['email']) ?>"
          >
        </div>

        <h5 class="text-secondary mt-4 mb-3">Paramètres du compte bancaire</h5>

        <div class="d-flex justify-content-start gap-3 mb-4 flex-wrap">
          <div class="mb-3 flex-fill" style="min-width: 220px;">
            <label for="limite_retrait" class="form-label">Limite de retrait quotidienne (€)</label>
            <input
              type="text"
              class="form-control"
              id="limite_retrait"
              name="limite_retrait"
              maxlength="3"
              pattern="\d{1,3}"
              title="Entrez un nombre de 1 à 3 chiffres"
              required
              value="<?= htmlspecialchars($compte['limite_retrait']) ?>"
            >
          </div>
          <div class="mb-3 flex-fill" style="min-width: 180px;">
            <label for="statut_compte" class="form-label">Statut du compte</label>
            <select class="form-select" id="statut_compte" name="statut_compte" required>
              <?php
              $options_statut = ['Désactivé', 'Activé', 'Bloqué'];
              foreach ($options_statut as $option) {
                  $sel = ($compte['statut_compte'] === $option) ? 'selected' : '';
                  echo "<option value=\"$option\" $sel>$option</option>";
              }
              ?>
            </select>
          </div>

          <div class="mb-3 flex-fill" style="min-width: 180px;">
            <label for="devise_utilisee" class="form-label">Devise utilisée</label>
            <select class="form-select" id="devise_utilisee" name="devise_utilisee" required>
              <?php
              $options_devise = ['EUR', 'USD', 'BIF', 'XOF'];
              foreach ($options_devise as $option) {
                  $sel = ($compte['devise'] === $option) ? 'selected' : '';
                  echo "<option value=\"$option\" $sel>$option</option>";
              }
              ?>
            </select>
          </div>

        </div>

        <div class="w-60 mx-auto mb-4">
          <div class="text-muted text-center fst-italic">
            Ces modifications affecteront votre compte utilisateur et les paramètres bancaires associés. Veuillez vérifier avant d'enregistrer.
          </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-3">
          <button type="button" class="btn btn-outline-dark" onclick="window.location.href='home.php'">Retourner</button>
          <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </div>
      </form>

    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
