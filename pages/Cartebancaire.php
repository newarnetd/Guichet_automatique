<?php
session_start();
require 'Backend/connexion/conn.php';

$error = "";
$success = "";

// ➕ Ajouter une carte bancaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addCard'])) {
    $numeroCarte = $_POST['numeroCarte'] ?? '';
    $codePIN = $_POST['codePIN'] ?? '';
    $dateExpiration = $_POST['dateExpiration'] ?? '';
    $etat = $_POST['etat'] ?? 'active';
    $clientId = $_POST['clientId'] ?? '';

    if ($numeroCarte && $codePIN && $dateExpiration && $clientId) {
        $stmt = $conn->prepare("INSERT INTO cartebancaire (numeroCarte, codePIN, dateExpiration, etat, clientId) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $numeroCarte, $codePIN, $dateExpiration, $etat, $clientId);
        if ($stmt->execute()) {
            $success = "Carte bancaire ajoutée avec succès.";
        } else {
            $error = "Erreur lors de l'ajout.";
        }
    } else {
        $error = "Tous les champs sont requis.";
    }
}

// 🔁 Modifier l’état d’une carte
if (isset($_POST['toggleEtat'])) {
    $id = $_POST['id'];
    $etat = $_POST['etat'] === 'active' ? 'bloquée' : 'active';

    $stmt = $conn->prepare("UPDATE cartebancaire SET etat = ? WHERE numeroCarte = ?");
    $stmt->bind_param("ss", $etat, $id);
    if ($stmt->execute()) {
        $success = "État de la carte mis à jour.";
    } else {
        $error = "Erreur de mise à jour.";
    }
}

// 📋 Affichage des cartes
$cartes = $conn->query("SELECT * FROM cartebancaire");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte Bancaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container my-5">
    <h2 class="text-center mb-4">Gestion des Cartes Bancaires</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success; ?></div>
    <?php endif; ?>

    <section class="mb-5">
        <h4>Ajouter une carte</h4>
        <form method="POST" class="row g-2 border p-4 rounded shadow-sm">
            <div class="col-md-3">
                <input type="text" name="numeroCarte" class="form-control" placeholder="Numéro Carte" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="codePIN" class="form-control" placeholder="Code PIN" required>
            </div>
            <div class="col-md-3">
                <input type="date" name="dateExpiration" class="form-control" required>
            </div>
            <div class="col-md-2">
                <select name="etat" class="form-select">
                    <option value="active">Active</option>
                    <option value="bloquée">Bloquée</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="clientId" class="form-control" placeholder="ID Client" required>
            </div>
            <div class="col-md-12">
                <button type="submit" name="addCard" class="btn btn-success w-100">Ajouter</button>
            </div>
        </form>
    </section>

    <section>
        <h4>Liste des cartes</h4>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Numéro</th>
                    <th>PIN</th>
                    <th>Expiration</th>
                    <th>État</th>
                    <th>Client</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($carte = $cartes->fetch_assoc()): ?>
                <tr>
                    <td><?= $carte['numeroCarte']; ?></td>
                    <td><?= $carte['codePIN']; ?></td>
                    <td><?= $carte['dateExpiration']; ?></td>
                    <td><?= ucfirst($carte['etat']); ?></td>
                    <td><?= $carte['clientId']; ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?= $carte['numeroCarte']; ?>">
                            <input type="hidden" name="etat" value="<?= $carte['etat']; ?>">
                            <button type="submit" name="toggleEtat" class="btn btn-warning btn-sm">
                                <?= $carte['etat'] === 'active' ? 'Bloquer' : 'Activer'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
