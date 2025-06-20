<?php
session_start();
require 'Backend/connexion/conn.php';

$error = "";
$success = "";

function generateUserId($length = 12) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function uploadProfileImage($file, $user_id) {
    $target_dir = "uploads/profiles/";

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $target_file = $target_dir . $user_id . "." . $imageFileType;

    // Vérifie si c'est une vraie image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ["success" => false, "message" => "Le fichier n'est pas une image."];
    }

    // Taille maximale : 5 Mo
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "L'image est trop volumineuse (max 5MB)."];
    }

    // Vérification du type MIME
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file["tmp_name"]);
    finfo_close($finfo);
    if (!in_array($mimeType, $allowedTypes)) {
        return ["success" => false, "message" => "Type de fichier non autorisé."];
    }

    // Formats autorisés
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        return ["success" => false, "message" => "Formats autorisés : JPG, JPEG, PNG, GIF."];
    }

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "path" => $target_file];
    } else {
        return ["success" => false, "message" => "Erreur lors du téléchargement de l'image."];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $type = trim($_POST['type'] ?? '');

    // Vérifications de base
    if (empty($email) || empty($password) || empty($nom) || empty($prenom) || empty($type)) {
        $error = "Tous les champs marqués d'un * sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'email n'est pas valide.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérification email existant
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            $stmt->close();

            $user_id = generateUserId();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $photo_profil = null;

            // Upload image si présente
            if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === 0) {
                $upload_result = uploadProfileImage($_FILES['photo_profil'], $user_id);
                if ($upload_result['success']) {
                    $photo_profil = $upload_result['path'];
                } else {
                    $error = $upload_result['message'];
                }
            }

            if (empty($error)) {
                // CORRECTION: Insertion dans users avec vérification des colonnes
                // Assurez-vous que ces colonnes existent dans votre table users
                $query = "INSERT INTO users (user_id, email, mot_de_passe, nom, prenom, type, photo_profil) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                
                // Vérification que la préparation a réussi
                if (!$stmt) {
                    $error = "Erreur de préparation de la requête: " . $conn->error;
                } else {
                    // CORRECTION: Binding des paramètres avec les bons types
                    $stmt->bind_param("sssssss", $user_id, $email, $hashed_password, $nom, $prenom, $type, $photo_profil);

                    if ($stmt->execute()) {
                        $stmt->close();

                        // Générer matricule
                        $prefix = ($type === 'admin') ? 'ADM' : (($type === 'client') ? 'CLI' : 'USR');
                        $matricule = $prefix . date("Y") . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                        $statut = "actif";

                        $query = "INSERT INTO personnels (id, type, matricule, statut) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($query);
                        
                        if (!$stmt) {
                            $error = "Erreur de préparation de la requête personnels: " . $conn->error;
                        } else {
                            $stmt->bind_param("ssss", $user_id, $type, $matricule, $statut);

                            if ($stmt->execute()) {
                                $success = "Inscription réussie. Votre matricule est : <strong>$matricule</strong>. Vous pouvez maintenant vous connecter.";
                            } else {
                                $error = "Erreur lors de l'enregistrement dans personnels : " . $stmt->error;
                                // Nettoyage de l'image en cas d'erreur
                                if (!empty($photo_profil) && file_exists($photo_profil)) {
                                    unlink($photo_profil);
                                }
                            }
                            $stmt->close();
                        }
                    } else {
                        $error = "Erreur lors de l'inscription : " . $stmt->error;
                        // Nettoyage de l'image en cas d'erreur
                        if (!empty($photo_profil) && file_exists($photo_profil)) {
                            unlink($photo_profil);
                        }
                    }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Guichet Automatique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/global.css">
    <style>
        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dee2e6;
            display: none;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .upload-area.dragover {
            border-color: #0d6efd;
            background-color: #e3f2fd;
        }
        .form-container {
            max-width: 600px;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
    <div class="form-container p-4 shadow rounded" style="background-color: white;">
        <h4 class="text-center mb-4">Inscription au Système</h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <!-- Photo de profil -->
            <div class="text-center mb-4">
                <img id="profilePreview" class="profile-preview" alt="Aperçu du profil">
                <div class="upload-area" onclick="document.getElementById('photo_profil').click()">
                    <i class="fas fa-camera fa-2x text-muted mb-2"></i>
                    <p class="mb-0 text-muted">Cliquez pour ajouter une photo de profil</p>
                    <small class="text-muted">(Optionnel - JPG, PNG, GIF - Max 5MB)</small>
                </div>
                <input type="file" id="photo_profil" name="photo_profil" accept="image/*" style="display: none;" onchange="previewImage(this)">
            </div>

            <!-- Informations personnelles -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nom *</label>
                    <input type="text" name="nom" class="form-control" placeholder="Votre nom" required value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Prénom *</label>
                    <input type="text" name="prenom" class="form-control" placeholder="Votre prénom" required value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>">
                </div>
            </div>

            <!-- Email et mot de passe -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" placeholder="votre@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mot de passe *</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 caractères" required>
                </div>
            </div>

            <!-- Type d'utilisateur -->
            <div class="mb-3">
                <label class="form-label">Type d'utilisateur *</label>
                <select name="type" class="form-select" required>
                    <option value="">-- Sélectionnez un type --</option>
                    <option value="admin" <?php echo (isset($_POST['type']) && $_POST['type'] == 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                    <option value="client" <?php echo (isset($_POST['type']) && $_POST['type'] == 'client') ? 'selected' : ''; ?>>Client</option>
                    <option value="utilisateur" <?php echo (isset($_POST['type']) && $_POST['type'] == 'utilisateur') ? 'selected' : ''; ?>>Utilisateur simple</option>
                </select>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="fas fa-user-plus me-2"></i>Ajouter cette personne
                </button>
            </div>
            
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

<script>
function previewImage(input) {
    const preview = document.getElementById('profilePreview');
    const uploadArea = document.querySelector('.upload-area');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            uploadArea.style.display = 'none';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Gestion du drag and drop
const uploadArea = document.querySelector('.upload-area');
const fileInput = document.getElementById('photo_profil');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    uploadArea.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    uploadArea.classList.add('dragover');
}

function unhighlight(e) {
    uploadArea.classList.remove('dragover');
}

uploadArea.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        fileInput.files = files;
        previewImage(fileInput);
    }
}

// Validation en temps réel
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input[required], select[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
    });
    
    function validateField(field) {
        const value = field.value.trim();
        
        // Supprimer les anciennes classes de validation
        field.classList.remove('is-valid', 'is-invalid');
        
        if (field.name === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(value)) {
                field.classList.add('is-valid');
            } else {
                field.classList.add('is-invalid');
            }
        } else if (field.name === 'password' && value) {
            if (value.length >= 6) {
                field.classList.add('is-valid');
            } else {
                field.classList.add('is-invalid');
            }
        } else if (field.required && value) {
            field.classList.add('is-valid');
        } else if (field.required && !value) {
            field.classList.add('is-invalid');
        }
    }
});
</script>

</body>
</html>