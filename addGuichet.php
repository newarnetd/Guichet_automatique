<?php
session_start();
require 'Backend/connexion/conn.php'; 
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT type FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $type = $user['type'];

    if ($type !== "admin") {
        header("Location: pages/home.php");
        exit();
    }
}

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
        // Vérifier si le nom du guichet existe déjà
        $checkQuery = "SELECT idGuichet FROM guichetautomatique WHERE nom = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $nom);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "Un guichet avec ce nom existe déjà.";
        } else {
            $query = "INSERT INTO guichetautomatique (localisation, nom, etat, soldedisponible) VALUES (?, ?, ?, ?)";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("sssd", $localisation, $nom, $etat, $soldedisponible);

                if ($stmt->execute()) {
                    $success = "Guichet ajouté avec succès.";
                    // Réinitialiser les champs après succès
                    $_POST = array();
                } else {
                    $error = "Erreur lors de l'ajout du guichet : " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Erreur de préparation de la requête : " . $conn->error;
            }
        }
        $checkStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Guichet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="Fonts.css">
    <link rel="stylesheet" href="style.css">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon_io/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="favicon_io/favicon-16x16.png" />
    
    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
        }
        
        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 40px;
            margin-top: 20px;
        }
        
        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .form-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 700;
        }
        
        .form-header h3 i {
            font-size: 2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: #667eea;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #764ba2;
            box-shadow: 0 0 0 0.2rem rgba(118, 75, 162, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #643a8a 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-outline-secondary {
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert i {
            font-size: 1.5rem;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .sidebar { 
                width: 100% !important; 
                min-height: auto; 
            }
            .d-flex { 
                flex-direction: column; 
            }
            .form-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <?php include('sidebar.php')?>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4" style="width: 80vw;">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0 kaushan-script">Gestion des Guichets</h2>
                <p class="text-muted mb-0 moon-dance-regular" style="font-size: 25px !important;">Ajouter un nouveau guichet automatique</p>
            </div>
            <div class="text-end">
                <small class="text-muted moon-dance-regular" style="font-size: 25px !important;">Connecté en tant que: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></small>
                <br>
                <small class="text-muted moon-dance-regular" style="font-size: 18px !important;"><?= date('d/m/Y H:i') ?></small>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span><?= htmlspecialchars($success) ?></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <div class="form-card">
            <div class="form-header">
                <h3>
                    <i class="bi bi-bank2"></i>
                    Informations du Guichet
                </h3>
                <p class="mb-0 mt-2" style="opacity: 0.9;">Remplissez tous les champs pour ajouter un nouveau guichet automatique</p>
            </div>

            <form action="" method="POST" id="guichetForm">
                <div class="row g-4">
                    <!-- Localisation -->
                    <div class="col-md-6">
                        <label for="localisation" class="form-label required-field">
                            <i class="bi bi-geo-alt-fill"></i>
                            Localisation
                        </label>
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
                                <option value="Gitega Ville" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Gitega Ville') ? 'selected' : '' ?>>Gitega Ville</option>
                                <option value="Ngozi Ville" <?= (isset($_POST['localisation']) && $_POST['localisation'] == 'Ngozi Ville') ? 'selected' : '' ?>>Ngozi Ville</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Nom du guichet -->
                    <div class="col-md-6">
                        <label for="nom" class="form-label required-field">
                            <i class="bi bi-tags-fill"></i>
                            Nom du guichet
                        </label>
                        <input type="text" 
                               id="nom" 
                               name="nom" 
                               class="form-control" 
                               placeholder="Ex: ATM Centre-Ville 01"
                               value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>" 
                               required />
                    </div>

                    <!-- État -->
                    <div class="col-md-6">
                        <label for="etat" class="form-label required-field">
                            <i class="bi bi-toggle-on"></i>
                            État du guichet
                        </label>
                        <select id="etat" name="etat" class="form-select" required>
                            <option value="">-- Sélectionner l'état --</option>
                            <option value="Ouvert" <?= (isset($_POST['etat']) && $_POST['etat'] == 'Ouvert') ? 'selected' : '' ?>>
                                <i class="bi bi-check-circle"></i> Ouvert
                            </option>
                            <option value="Fermé" <?= (isset($_POST['etat']) && $_POST['etat'] == 'Fermé') ? 'selected' : '' ?>>
                                Fermé
                            </option>
                            <option value="En maintenance" <?= (isset($_POST['etat']) && $_POST['etat'] == 'En maintenance') ? 'selected' : '' ?>>
                                En maintenance
                            </option>
                        </select>
                    </div>

                    <!-- Solde disponible -->
                    <div class="col-md-6">
                        <label for="soldedisponible" class="form-label required-field">
                            <i class="bi bi-cash-stack"></i>
                            Solde disponible
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-currency-dollar"></i>
                            </span>
                            <input type="number" 
                                   id="soldedisponible" 
                                   name="soldedisponible" 
                                   class="form-control" 
                                   min="0" 
                                   step="0.01" 
                                   placeholder="0.00"
                                   value="<?= isset($_POST['soldedisponible']) ? htmlspecialchars($_POST['soldedisponible']) : '' ?>" 
                                   required />
                        </div>
                        <small class="text-muted">Montant en devise locale</small>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="d-flex justify-content-end gap-3 mt-5">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>
                        Réinitialiser
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Ajouter le guichet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Validation du formulaire
    const form = document.getElementById('guichetForm');
    form.addEventListener('submit', function(e) {
        const solde = document.getElementById('soldedisponible').value;
        if (solde < 0) {
            e.preventDefault();
            alert('Le solde disponible doit être un nombre positif.');
        }
    });
});
</script>

</body>
</html>