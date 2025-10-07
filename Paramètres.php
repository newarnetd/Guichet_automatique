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
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Vérifier le type d'utilisateur
$stmt = $conn->prepare("SELECT type FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $userType = $result->fetch_assoc();
    $type = $userType['type'];

    if ($type !== "admin") {
        header("Location: pages/home.php");
        exit();
    }
}

$error = "";
$success = "";

// Récupérer les infos utilisateur
$queryUser = "SELECT nom, prenom, email, photo_profil FROM users WHERE id = ?";
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
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $limite_retrait = trim($_POST['limite_retrait'] ?? '');
    $statut_compte = $_POST['statut_compte'] ?? '';
    $devise_utilisee = $_POST['devise_utilisee'] ?? '';
    $ancien_mot_de_passe = $_POST['ancien_mot_de_passe'] ?? '';
    $nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'] ?? '';
    $confirmer_mot_de_passe = $_POST['confirmer_mot_de_passe'] ?? '';

    if ($nom === '' || $prenom === '' || $email === '') {
        $error = "Nom, prénom et email sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif (!preg_match('/^\d{1,3}$/', $limite_retrait)) {
        $error = "La limite de retrait doit être un nombre entre 1 et 999.";
    } elseif (!in_array($statut_compte, ['Désactivé', 'Activé', 'Bloqué'])) {
        $error = "Statut du compte invalide.";
    } elseif (!in_array($devise_utilisee, ['EUR', 'USD', 'BIF', 'XOF'])) {
        $error = "Devise utilisée invalide.";
    } else {
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $checkEmail = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmtCheck = $conn->prepare($checkEmail);
        $stmtCheck->bind_param("si", $email, $user_id);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        
        if ($resultCheck->num_rows > 0) {
            $error = "Cet email est déjà utilisé par un autre utilisateur.";
        } else {
            // Gestion du changement de mot de passe
            $updatePassword = false;
            if (!empty($ancien_mot_de_passe) || !empty($nouveau_mot_de_passe) || !empty($confirmer_mot_de_passe)) {
                if (empty($ancien_mot_de_passe) || empty($nouveau_mot_de_passe) || empty($confirmer_mot_de_passe)) {
                    $error = "Tous les champs de mot de passe doivent être remplis.";
                } elseif ($nouveau_mot_de_passe !== $confirmer_mot_de_passe) {
                    $error = "Les nouveaux mots de passe ne correspondent pas.";
                } elseif (strlen($nouveau_mot_de_passe) < 6) {
                    $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
                } else {
                    // Vérifier l'ancien mot de passe
                    $queryPassword = "SELECT mot_de_passe FROM users WHERE id = ?";
                    $stmtPassword = $conn->prepare($queryPassword);
                    $stmtPassword->bind_param("i", $user_id);
                    $stmtPassword->execute();
                    $resultPassword = $stmtPassword->get_result();
                    $userPassword = $resultPassword->fetch_assoc();
                    
                    if (!password_verify($ancien_mot_de_passe, $userPassword['mot_de_passe'])) {
                        $error = "L'ancien mot de passe est incorrect.";
                    } else {
                        $updatePassword = true;
                    }
                }
            }
            
            if (empty($error)) {
                $conn->begin_transaction();
                
                try {
                    // Mise à jour des infos utilisateur
                    if ($updatePassword) {
                        $hashed_password = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
                        $updateUser = "UPDATE users SET nom = ?, prenom = ?, email = ?, mot_de_passe = ? WHERE id = ?";
                        $stmtUpdateUser = $conn->prepare($updateUser);
                        $stmtUpdateUser->bind_param("ssssi", $nom, $prenom, $email, $hashed_password, $user_id);
                    } else {
                        $updateUser = "UPDATE users SET nom = ?, prenom = ?, email = ? WHERE id = ?";
                        $stmtUpdateUser = $conn->prepare($updateUser);
                        $stmtUpdateUser->bind_param("sssi", $nom, $prenom, $email, $user_id);
                    }
                    
                    $stmtUpdateUser->execute();
                    $stmtUpdateUser->close();
                    
                    // Mise à jour du compte bancaire
                    $updateCompte = "UPDATE comptebancaire SET limite_retrait = ?, statut_compte = ?, devise = ? WHERE clientId = ?";
                    $stmtUpdateCompte = $conn->prepare($updateCompte);
                    $stmtUpdateCompte->bind_param("issi", $limite_retrait, $statut_compte, $devise_utilisee, $user_id);
                    $stmtUpdateCompte->execute();
                    $stmtUpdateCompte->close();
                    
                    $conn->commit();
                    
                    $success = "Informations mises à jour avec succès.";
                    $user['nom'] = $nom;
                    $user['prenom'] = $prenom;
                    $user['email'] = $email;
                    $compte['limite_retrait'] = $limite_retrait;
                    $compte['statut_compte'] = $statut_compte;
                    $compte['devise'] = $devise_utilisee;
                    
                    // Mettre à jour la session
                    $_SESSION['user_name'] = $prenom . ' ' . $nom;
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Erreur lors de la mise à jour : " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres du Compte</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="Fonts.css">
    <link rel="stylesheet" href="style.css">
    
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
        
        .settings-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 0;
            margin-top: 20px;
            overflow: hidden;
        }
        
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }
        
        .settings-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 700;
        }
        
        .settings-header h3 i {
            font-size: 2rem;
        }
        
        .settings-body {
            padding: 40px;
        }
        
        .section-divider {
            border-top: 2px solid #e9ecef;
            margin: 30px 0;
            position: relative;
        }
        
        .section-divider::before {
            content: attr(data-title);
            position: absolute;
            top: -12px;
            left: 20px;
            background: white;
            padding: 0 10px;
            color: #667eea;
            font-weight: 600;
            font-size: 0.9rem;
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
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .info-box i {
            color: #667eea;
            margin-right: 10px;
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 38px;
            color: #6c757d;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        @media (max-width: 768px) {
            .sidebar { 
                width: 100% !important; 
                min-height: auto; 
            }
            .d-flex { 
                flex-direction: column; 
            }
            .settings-body {
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
                <h2 class="mb-0 kaushan-script">Paramètres du Compte</h2>
                <p class="text-muted mb-0 moon-dance-regular" style="font-size: 25px !important;">Gérer vos informations personnelles et bancaires</p>
            </div>
            <div class="text-end">
                <small class="text-muted moon-dance-regular" style="font-size: 25px !important;">Connecté en tant que: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></small>
                <br>
                <small class="text-muted moon-dance-regular" style="font-size: 18px !important;"><?= date('d/m/Y H:i') ?></small>
            </div>
        </div>

        <!-- Messages d'alerte -->
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

        <!-- Formulaire de paramètres -->
        <div class="settings-card">
            <div class="settings-header">
                <h3>
                    <i class="bi bi-gear-fill"></i>
                    Configuration du Compte
                </h3>
                <p class="mb-0 mt-2" style="opacity: 0.9;">Modifiez vos informations personnelles et les paramètres de votre compte bancaire</p>
            </div>

            <div class="settings-body">
                <form action="" method="POST" novalidate>
                    <!-- Informations personnelles -->
                    <h5 class="text-secondary mb-3">
                        <i class="bi bi-person-circle me-2"></i>Informations Personnelles
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">
                                <i class="bi bi-person-fill"></i>
                                Prénom
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="prenom" 
                                   name="prenom" 
                                   required 
                                   value="<?= htmlspecialchars($user['prenom'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="nom" class="form-label">
                                <i class="bi bi-person-fill"></i>
                                Nom
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nom" 
                                   name="nom" 
                                   required 
                                   value="<?= htmlspecialchars($user['nom']) ?>">
                        </div>

                        <div class="col-md-12">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope-fill"></i>
                                Adresse Email
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   required 
                                   value="<?= htmlspecialchars($user['email']) ?>">
                        </div>
                    </div>

                    <div class="section-divider" data-title="PARAMÈTRES BANCAIRES"></div>

                    <!-- Paramètres du compte bancaire -->
                    <h5 class="text-secondary mb-3 mt-4">
                        <i class="bi bi-bank2 me-2"></i>Paramètres du Compte Bancaire
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="limite_retrait" class="form-label">
                                <i class="bi bi-cash-stack"></i>
                                Limite de retrait quotidienne
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       id="limite_retrait" 
                                       name="limite_retrait" 
                                       maxlength="3" 
                                       pattern="\d{1,3}" 
                                       title="Entrez un nombre de 1 à 3 chiffres" 
                                       required 
                                       value="<?= htmlspecialchars($compte['limite_retrait']) ?>">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="statut_compte" class="form-label">
                                <i class="bi bi-toggle-on"></i>
                                Statut du compte
                            </label>
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

                        <div class="col-md-4">
                            <label for="devise_utilisee" class="form-label">
                                <i class="bi bi-currency-exchange"></i>
                                Devise utilisée
                            </label>
                            <select class="form-select" id="devise_utilisee" name="devise_utilisee" required>
                                <?php
                                $options_devise = [
                                    'EUR' => '€ Euro',
                                    'USD' => '$ Dollar US',
                                    'BIF' => 'FBu Franc Burundais',
                                    'XOF' => 'CFA Franc CFA'
                                ];
                                foreach ($options_devise as $code => $label) {
                                    $sel = ($compte['devise'] === $code) ? 'selected' : '';
                                    echo "<option value=\"$code\" $sel>$label</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="section-divider" data-title="SÉCURITÉ"></div>

                    <!-- Changement de mot de passe -->
                    <h5 class="text-secondary mb-3 mt-4">
                        <i class="bi bi-shield-lock me-2"></i>Modifier le Mot de Passe
                    </h5>

                    <div class="info-box">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Laissez ces champs vides si vous ne souhaitez pas modifier votre mot de passe. Le nouveau mot de passe doit contenir au moins 6 caractères.</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="ancien_mot_de_passe" class="form-label">
                                <i class="bi bi-lock-fill"></i>
                                Ancien mot de passe
                            </label>
                            <div class="position-relative">
                                <input type="password" 
                                       class="form-control" 
                                       id="ancien_mot_de_passe" 
                                       name="ancien_mot_de_passe"
                                       autocomplete="current-password">
                                <i class="bi bi-eye password-toggle" onclick="togglePassword('ancien_mot_de_passe', this)"></i>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="nouveau_mot_de_passe" class="form-label">
                                <i class="bi bi-key-fill"></i>
                                Nouveau mot de passe
                            </label>
                            <div class="position-relative">
                                <input type="password" 
                                       class="form-control" 
                                       id="nouveau_mot_de_passe" 
                                       name="nouveau_mot_de_passe"
                                       autocomplete="new-password">
                                <i class="bi bi-eye password-toggle" onclick="togglePassword('nouveau_mot_de_passe', this)"></i>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="confirmer_mot_de_passe" class="form-label">
                                <i class="bi bi-check-circle-fill"></i>
                                Confirmer le mot de passe
                            </label>
                            <div class="position-relative">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirmer_mot_de_passe" 
                                       name="confirmer_mot_de_passe"
                                       autocomplete="new-password">
                                <i class="bi bi-eye password-toggle" onclick="togglePassword('confirmer_mot_de_passe', this)"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                            <i class="bi bi-arrow-left me-2"></i>
                            Retour
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Toggle password visibility
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Validation du formulaire
document.querySelector('form').addEventListener('submit', function(e) {
    const nouveauMdp = document.getElementById('nouveau_mot_de_passe').value;
    const confirmerMdp = document.getElementById('confirmer_mot_de_passe').value;
    const ancienMdp = document.getElementById('ancien_mot_de_passe').value;
    
    if ((nouveauMdp || confirmerMdp || ancienMdp) && (nouveauMdp !== confirmerMdp)) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas.');
    }
});
</script>

</body>
</html>