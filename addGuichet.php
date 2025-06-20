<?php
session_start();
require 'Backend/connexion/conn.php'; 

$error = "";
$success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $localisation = trim($_POST['localisation']);
    $nom = trim($_POST['nom']);
    $etat = trim($_POST['etat']);
    $soldedisponible = trim($_POST['soldedisponible']);
    if (empty($localisation) || empty($nom) || empty($etat) || $soldedisponible === '') {
        $error = "Veuillez remplir tous les champs.";
    } elseif (!is_numeric($soldedisponible) || $soldedisponible < 0) {
        $error = "Le solde disponible doit être un nombre positif.";
    } else {
        $query = "INSERT INTO guichetautomatique (localisation, nom, etat, soldedisponible) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("sssd", $localisation, $nom, $etat, $soldedisponible);

            if ($stmt->execute()) {
                $success = "Guichet ajouté avec succès.";
            } else {
                $error = "Erreur lors de l'ajout du guichet : " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Erreur de préparation de la requête : " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ajouter un guichet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="apple-touch-icon" sizes="180x180" href="favicon_io/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="favicon_io/favicon-16x16.png" />
  <style>
    body {
      background-color: #f4f6f9;
    }
    .container {
      max-width: 600px;
      margin-top: 60px;
    }
    .form-section {
      background: #fff;
      padding: 25px 30px;
      border-radius: 8px;
      box-shadow: 0 0 12px rgba(0,0,0,0.08);
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="form-section">
      <h3 class="mb-4 text-secondary">Ajouter un guichet</h3>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form action="" method="POST">

        <div class="mb-3">
          <label for="localisation" class="form-label">Localisation</label>
          <select id="localisation" name="localisation" class="form-select" required>
            <option value="">-- Sélectionner une localité --</option>
            <optgroup label="Provinces du Burundi">
              <option value="Bubanza" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Bubanza') ? 'selected' : '' ?>>Bubanza</option>
              <option value="Bujumbura Mairie" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Bujumbura Mairie') ? 'selected' : '' ?>>Bujumbura Mairie</option>
              <option value="Bujumbura Rural" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Bujumbura Rural') ? 'selected' : '' ?>>Bujumbura Rural</option>
              <option value="Bururi" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Bururi') ? 'selected' : '' ?>>Bururi</option>
              <option value="Cankuzo" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Cankuzo') ? 'selected' : '' ?>>Cankuzo</option>
              <option value="Cibitoke" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Cibitoke') ? 'selected' : '' ?>>Cibitoke</option>
              <option value="Gitega" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Gitega') ? 'selected' : '' ?>>Gitega</option>
              <option value="Karuzi" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Karuzi') ? 'selected' : '' ?>>Karuzi</option>
              <option value="Kayanza" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Kayanza') ? 'selected' : '' ?>>Kayanza</option>
              <option value="Kirundo" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Kirundo') ? 'selected' : '' ?>>Kirundo</option>
              <option value="Makamba" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Makamba') ? 'selected' : '' ?>>Makamba</option>
              <option value="Muramvya" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Muramvya') ? 'selected' : '' ?>>Muramvya</option>
              <option value="Muyinga" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Muyinga') ? 'selected' : '' ?>>Muyinga</option>
              <option value="Mwaro" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Mwaro') ? 'selected' : '' ?>>Mwaro</option>
              <option value="Ngozi" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Ngozi') ? 'selected' : '' ?>>Ngozi</option>
              <option value="Rumonge" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Rumonge') ? 'selected' : '' ?>>Rumonge</option>
              <option value="Rutana" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Rutana') ? 'selected' : '' ?>>Rutana</option>
              <option value="Ruyigi" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Ruyigi') ? 'selected' : '' ?>>Ruyigi</option>
            </optgroup>
            <optgroup label="Villes principales">
              <option value="Bujumbura" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Bujumbura') ? 'selected' : '' ?>>Bujumbura</option>
              <option value="Gitega" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Gitega') ? 'selected' : '' ?>>Gitega</option>
              <option value="Ngozi" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Ngozi') ? 'selected' : '' ?>>Ngozi</option>
              <option value="Muyinga" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Muyinga') ? 'selected' : '' ?>>Muyinga</option>
              <option value="Ruyigi" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Ruyigi') ? 'selected' : '' ?>>Ruyigi</option>
              <option value="Bururi" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Bururi') ? 'selected' : '' ?>>Bururi</option>
            </optgroup>
          </select>
        </div>

        <div class="mb-3">
          <label for="nom" class="form-label">Nom du guichet</label>
          <input type="text" id="nom" name="nom" class="form-control" value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>" required />
        </div>

        <div class="mb-3">
          <label for="etat" class="form-label">État</label>
          <select id="etat" name="etat" class="form-select" required>
            <option value="">-- Sélectionner --</option>
            <option value="Ouvert" <?= (isset($_POST['etat']) && $_POST['etat'] == 'Ouvert') ? 'selected' : '' ?>>Ouvert</option>
            <option value="Fermé" <?= (isset($_POST['etat']) && $_POST['etat'] == 'Fermé') ? 'selected' : '' ?>>Fermé</option>
            <option value="En maintenance" <?= (isset($_POST['etat']) && $_POST['etat'] == 'En maintenance') ? 'selected' : '' ?>>En maintenance</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="soldedisponible" class="form-label">Solde disponible</label>
          <input type="number" id="soldedisponible" name="soldedisponible" class="form-control" min="0" step="0.01" value="<?= isset($_POST['soldedisponible']) ? htmlspecialchars($_POST['soldedisponible']) : '' ?>" required />
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <button type="reset" class="btn btn-outline-secondary">Réinitialiser</button>
          <button type="submit" class="btn btn-primary">Ajouter le guichet</button>
        </div>

      </form>
    </div>
  </div>

</body>
</html>
