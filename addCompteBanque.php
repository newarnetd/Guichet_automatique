<?php
session_start();
require 'Backend/connexion/conn.php';

$error = "";
$success = "";

// Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../");
    exit();
}

$clientId = $_SESSION['user_id'];

// Récupérer la liste des guichets automatiques
$guichets = [];
$guichet_query = $conn->prepare("SELECT idGuichet, localisation FROM guichetautomatique ORDER BY localisation");
$guichet_query->execute();
$guichet_result = $guichet_query->get_result();
while ($row = $guichet_result->fetch_assoc()) {
    $guichets[] = $row;
}
$guichet_query->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $type = trim($_POST['type'] ?? '');
    $solde = floatval($_POST['solde'] ?? 0);
    $devise = trim($_POST['devise'] ?? '');
    $limite_retrait = floatval($_POST['limite_retrait'] ?? 0);
    $statut_compte = trim($_POST['statut_compte'] ?? '');
    $idGuichet = intval($_POST['idGuichet'] ?? 0);

    // Enhanced validation
    $valid_types = ['Épargne', 'Courant', 'Joint', 'Professionnel'];
    $valid_currencies = ['CDF', 'BIF', 'USD', 'EUR', 'UGX'];
    $valid_statuses = ['Activé', 'Désactivé'];

    // Check if user already has an account
    $verif = $conn->prepare("SELECT idCompte FROM comptebancaire WHERE clientId = ?");
    $verif->bind_param("i", $clientId);
    $verif->execute();
    $result = $verif->get_result();

    // Vérifier si le guichet existe
    $guichet_check = $conn->prepare("SELECT idGuichet FROM guichetautomatique WHERE idGuichet = ?");
    $guichet_check->bind_param("i", $idGuichet);
    $guichet_check->execute();
    $guichet_exists = $guichet_check->get_result()->num_rows > 0;
    $guichet_check->close();

    if ($result->num_rows > 0) {
        $error = "Vous avez déjà un compte bancaire. Un seul compte est autorisé par utilisateur.";
    } elseif (!in_array($type, $valid_types)) {
        $error = "Type de compte invalide.";
    } elseif ($solde <= 0) {
        $error = "Le solde initial doit être supérieur à 0.";
    } elseif (!in_array($devise, $valid_currencies)) {
        $error = "Devise non supportée.";
    } elseif ($limite_retrait <= 0) {
        $error = "La limite de retrait doit être supérieure à 0.";
    } elseif (!in_array($statut_compte, $valid_statuses)) {
        $error = "Statut de compte invalide.";
    } elseif ($limite_retrait > $solde) {
        $error = "La limite de retrait ne peut pas être supérieure au solde initial.";
    } elseif ($idGuichet <= 0 || !$guichet_exists) {
        $error = "Veuillez sélectionner un guichet automatique valide.";
    } else {

        $conn->autocommit(FALSE);
        
        try {
            $insert = $conn->prepare("INSERT INTO comptebancaire (type, solde, devise, clientId, limite_retrait, statut_compte) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->bind_param("sdsids", $type, $solde, $devise, $clientId, $limite_retrait, $statut_compte);
            
            if (!$insert->execute()) {
                throw new Exception("Erreur lors de la création du compte.");
            }
            
            $idCompte = $insert->insert_id;
            $numeroCompte = str_pad($idCompte, 10, '0', STR_PAD_LEFT);

            $update = $conn->prepare("UPDATE comptebancaire SET solde = ? WHERE idCompte = ?");
            $update->bind_param("di", $solde, $idCompte);
            $update->execute();

            $guichet_info = $conn->prepare("SELECT localisation FROM guichetautomatique WHERE idGuichet = ?");
            $guichet_info->bind_param("i", $idGuichet);
            $guichet_info->execute();
            $guichet_location = $guichet_info->get_result()->fetch_assoc()['localisation'];
            $guichet_info->close();

            $dateHeure = date('Y-m-d H:i:s');
            $typeEvenement = "Création de compte";
            $message = "Création du compte n°$numeroCompte de type $type avec un solde initial de $solde $devise. Guichet attribué: $guichet_location";
            
            $hist = $conn->prepare("INSERT INTO historique (dateHeure, typeEvenement, message, idGuichet, idPersonnel, idClient) VALUES (?, ?, ?, ?, ?, ?)");
            $hist->bind_param("sssiii", $dateHeure, $typeEvenement, $message, $idGuichet, $clientId, $clientId);
            
            if (!$hist->execute()) {
                throw new Exception("Erreur lors de l'enregistrement de l'historique.");
            }

            $conn->commit();
            $success = "Compte bancaire créé avec succès. Numéro de compte: $numeroCompte. Guichet attribué: $guichet_location";
            
        } catch (Exception $e) {
            
            $conn->rollback();
            $error = $e->getMessage();
        }
        $conn->autocommit(TRUE);
    }

    if (isset($verif)) $verif->close();
    if (isset($insert)) $insert->close();
    if (isset($update)) $update->close();
    if (isset($hist)) $hist->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Créer un compte bancaire</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="apple-touch-icon" sizes="180x180" href="favicon_io/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="favicon_io/favicon-16x16.png" />
    <link rel="stylesheet" href="styles/global.css" />
    <style>
        .currency-info {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }
        .guichet-info {
            font-size: 0.8em;
            color: #28a745;
            margin-top: 5px;
            font-weight: 500;
        }
    </style>
</head>
<body>
<main class="d-flex justify-content-center align-items-center vh-100">
    <article class="form-container parent-Basic p-4 rounded shadow bg-light text-center" style="min-width: 400px; max-width: 500px;">
        <form method="POST" action="" id="accountForm">
            <h3 class="mb-4">Créer un compte bancaire</h3>

            <div class="mb-3">
                <select name="type" class="form-select" required>
                    <option value="">Sélectionnez le type de compte</option>
                    <option value="Épargne" <?= (isset($_POST['type']) && $_POST['type'] === 'Épargne') ? 'selected' : '' ?>>Épargne</option>
                    <option value="Courant" <?= (isset($_POST['type']) && $_POST['type'] === 'Courant') ? 'selected' : '' ?>>Courant</option>
                    <option value="Joint" <?= (isset($_POST['type']) && $_POST['type'] === 'Joint') ? 'selected' : '' ?>>Joint</option>
                    <option value="Professionnel" <?= (isset($_POST['type']) && $_POST['type'] === 'Professionnel') ? 'selected' : '' ?>>Professionnel</option>
                </select>
            </div>

            <div class="mb-3">
                <input type="number" 
                       name="solde" 
                       class="form-control" 
                       placeholder="Solde initial" 
                       step="0.01" 
                       min="1" 
                       value="<?= htmlspecialchars($_POST['solde'] ?? '') ?>"
                       required />
            </div>

            <div class="mb-3">
                <select name="devise" class="form-select" required id="deviseSelect">
                    <option value="">Sélectionnez la devise</option>
                    <option value="CDF" <?= (isset($_POST['devise']) && $_POST['devise'] === 'CDF') ? 'selected' : '' ?>>CDF (Franc Congolais)</option>
                    <option value="BIF" <?= (isset($_POST['devise']) && $_POST['devise'] === 'BIF') ? 'selected' : '' ?>>BIF (Franc Burundais)</option>
                    <option value="USD" <?= (isset($_POST['devise']) && $_POST['devise'] === 'USD') ? 'selected' : '' ?>>USD (Dollar US)</option>
                    <option value="EUR" <?= (isset($_POST['devise']) && $_POST['devise'] === 'EUR') ? 'selected' : '' ?>>EUR (Euro)</option>
                    <option value="UGX" <?= (isset($_POST['devise']) && $_POST['devise'] === 'UGX') ? 'selected' : '' ?>>UGX (Shilling Ougandais)</option>
                </select>
                <div class="currency-info" id="currencyInfo"></div>
            </div>

            <div class="mb-3">
                <input type="number" 
                       name="limite_retrait" 
                       class="form-control" 
                       placeholder="Limite de retrait quotidien" 
                       step="0.01" 
                       min="1" 
                       value="<?= htmlspecialchars($_POST['limite_retrait'] ?? '') ?>"
                       required />
                <small class="text-muted">La limite ne peut pas dépasser le solde initial</small>
            </div>

            <div class="mb-3">
                <select name="statut_compte" class="form-select" required>
                    <option value="">Statut du compte</option>
                    <option value="Activé" <?= (isset($_POST['statut_compte']) && $_POST['statut_compte'] === 'Activé') ? 'selected' : '' ?>>Activé</option>
                    <option value="Désactivé" <?= (isset($_POST['statut_compte']) && $_POST['statut_compte'] === 'Désactivé') ? 'selected' : '' ?>>Désactivé</option>
                </select>
            </div>

            <div class="mb-3">
                <select name="idGuichet" class="form-select" required id="guichetSelect">
                    <option value="">Sélectionnez un guichet automatique</option>
                    <?php foreach ($guichets as $guichet): ?>
                        <option value="<?= $guichet['idGuichet'] ?>" 
                                <?= (isset($_POST['idGuichet']) && $_POST['idGuichet'] == $guichet['idGuichet']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($guichet['localisation']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="guichet-info" id="guichetInfo">
                    <i class="bi bi-info-circle"></i> Ce guichet sera associé à votre compte
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-success w-100 mb-2">
                <i class="bi bi-plus-circle"></i> Créer le compte
            </button>
            <a href="home.php" class="btn btn-secondary w-100">
                <i class="bi bi-arrow-left"></i> Retour au tableau de bord
            </a>
        </form>
    </article>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('accountForm').addEventListener('submit', function(e) {
        const solde = parseFloat(document.querySelector('input[name="solde"]').value);
        const limite = parseFloat(document.querySelector('input[name="limite_retrait"]').value);
        const guichet = document.querySelector('select[name="idGuichet"]').value;
        
        if (limite > solde) {
            e.preventDefault();
            alert('La limite de retrait ne peut pas être supérieure au solde initial.');
            return false;
        }
        
        if (!guichet) {
            e.preventDefault();
            alert('Veuillez sélectionner un guichet automatique.');
            return false;
        }
    });

    const currencyInfo = {
        'CDF': 'Devise principale de la République Démocratique du Congo',
        'BIF': 'Devise officielle du Burundi',
        'USD': 'Devise internationale - Dollar américain',
        'EUR': 'Devise européenne - Euro',
        'UGX': 'Devise officielle de l\'Ouganda'
    };

    document.getElementById('deviseSelect').addEventListener('change', function() {
        const info = document.getElementById('currencyInfo');
        const selectedCurrency = this.value;
        info.textContent = currencyInfo[selectedCurrency] || '';
    });

    document.getElementById('guichetSelect').addEventListener('change', function() {
        const info = document.getElementById('guichetInfo');
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            info.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Guichet sélectionné: ' + selectedOption.text;
        } else {
            info.innerHTML = '<i class="bi bi-info-circle"></i> Ce guichet sera associé à votre compte';
        }
    });

    <?php if ($error && isset($_POST['devise'])): ?>
        document.getElementById('deviseSelect').dispatchEvent(new Event('change'));
    <?php endif; ?>
    
    <?php if ($error && isset($_POST['idGuichet'])): ?>
        document.getElementById('guichetSelect').dispatchEvent(new Event('change'));
    <?php endif; ?>
</script>
</body>
</html>