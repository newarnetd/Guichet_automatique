<?php
session_start();
require '../Backend/connexion/conn.php'; 

$error = "";
$success = "";
if (!isset($_SESSION['user_id'])) {
    header("Location: ../");
    exit();
}

$clientId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCompte = $_POST['idCompte'] ?? null;
    $montant = floatval($_POST['montant'] ?? 0);
    $idGuichet = $_POST['idGuichet'] ?? null;
    $limiteDepotMax = 10000;

    if (!$idCompte || $montant <= 0 || !$idGuichet) {
        $error = "Veuillez saisir un montant valide, choisir un compte et un guichet.";
    } elseif ($montant > $limiteDepotMax) {
        $error = "Le montant dépasse la limite maximale de dépôt autorisée (".$limiteDepotMax.").";
    } else {
        $stmt = $conn->prepare("SELECT solde, statut_compte FROM comptebancaire WHERE idCompte = ? AND clientId = ?");
        $stmt->bind_param("ii", $idCompte, $clientId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $compte = $result->fetch_assoc();

            if ($compte['statut_compte'] !== 'Activé') {
                $error = "Le compte sélectionné n'est pas actif. Impossible de faire un dépôt.";
            } else {
                $nouveauSolde = $compte['solde'] + $montant;

                $update = $conn->prepare("UPDATE comptebancaire SET solde = ? WHERE idCompte = ?");
                $update->bind_param("di", $nouveauSolde, $idCompte);

                if ($update->execute()) {
                    
                    $dateHeure = date('Y-m-d H:i:s');
                    $typeEvenement = "Dépôt";
                    $message = "Dépôt de $montant effectué sur le compte $idCompte via guichet $idGuichet.";
                    $idPersonnel = $_SESSION['user_id']; 

                    $insertHist = $conn->prepare("INSERT INTO historique (dateHeure, typeEvenement, message, idGuichet, idPersonnel, idClient) VALUES (?, ?, ?, ?, ?, ?)");
                    $insertHist->bind_param("sssiii", $dateHeure, $typeEvenement, $message, $idGuichet, $idPersonnel, $clientId);
                    $insertHist->execute();

                    $success = "Dépôt effectué avec succès.";
                } else {
                    $error = "Erreur lors de la mise à jour du solde.";
                }
            }
        } else {
            $error = "Compte invalide ou non autorisé.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dépôt bancaire</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/global.css">
</head>
<body>
<main class="d-flex justify-content-center align-items-center vh-100">
    <article class="form-container parent-Basic p-4 rounded shadow bg-light text-center" style="min-width: 350px;">
        <form action="" method="POST">
            <h3 class="mb-4">Effectuer un dépôt</h3>

            <div class="mb-3 input-group">
                <select name="idCompte" class="form-select" required>
                    <option value="">-- Sélectionnez un compte --</option>
                    <?php
                    $query = $conn->prepare("SELECT idCompte, type, solde, devise FROM comptebancaire WHERE clientId = ?");
                    $query->bind_param("i", $clientId);
                    $query->execute();
                    $result = $query->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['idCompte']}'>Compte Num {$row['idCompte']} (Solde: {$row['solde']} {$row['devise']})</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3 input-group">
                <input type="number" name="montant" class="form-control" placeholder="Montant à déposer" min="1" step="0.01" required>
            </div>

            <div class="mb-3 input-group">
                <select name="idGuichet" id="idGuichet" class="form-select" required>
                    <option value="">-- Sélectionnez un guichet --</option>
                    <?php
                    $guichets = $conn->query("SELECT idGuichet, localisation, nom FROM guichetautomatique WHERE etat = 'Ouvert'");
                    while ($guichet = $guichets->fetch_assoc()) {
                        echo "<option value='{$guichet['idGuichet']}'>{$guichet['nom']}</option>";
                    }
                    ?>
                </select>
            </div>

            <?php if ($error): ?>
                <p class="text-danger Description_Erreur"><?= htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="text-success"><?= htmlspecialchars($success); ?></p>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary w-100 mb-2">Déposer</button>
            <a href="../home.php"><button type="button" class="btn btn-secondary w-100">Retour</button></a>
        </form>
    </article>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="Js/script.js"></script>
</body>
</html>
