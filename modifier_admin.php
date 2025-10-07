<?php
session_start();

// Connexion à la base de données
$host = "localhost";
$user = "root";
$password = ""; 
$dbname = "guichet_automatique";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération et validation des données
    $admin_id = filter_input(INPUT_POST, 'admin_id', FILTER_VALIDATE_INT);
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matricule = trim($_POST['matricule'] ?? '');
    $statut_compte = $_POST['statut_compte'] ?? '';
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');
    
    // Validation des données
    if (!$admin_id) {
        $_SESSION['error'] = "ID administrateur invalide.";
        header("Location: admin_dashboard.php");
        exit();
    }
    
    if (empty($nom) || empty($prenom) || empty($email) || empty($matricule)) {
        $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
        header("Location: admin_dashboard.php");
        exit();
    }
    
    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format d'email invalide.";
        header("Location: admin_dashboard.php");
        exit();
    }
    
    // Validation du statut
    $statuts_valides = ['Activé', 'Désactivé', 'Bloqué'];
    if (!in_array($statut_compte, $statuts_valides)) {
        $_SESSION['error'] = "Statut invalide.";
        header("Location: admin_dashboard.php");
        exit();
    }
    
    try {
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Cet email est déjà utilisé par un autre utilisateur.";
            header("Location: admin_dashboard.php");
            exit();
        }
        $stmt->close();
        
        // Vérifier si le matricule existe déjà pour un autre personnel
        $stmt = $conn->prepare("SELECT id FROM personnels WHERE matricule = ? AND id != ?");
        $stmt->bind_param("si", $matricule, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Ce matricule est déjà utilisé par un autre personnel.";
            header("Location: admin_dashboard.php");
            exit();
        }
        $stmt->close();
        
        // Gestion de l'upload de photo
        $photo_path = null;
        if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo_profil'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            // Validation du type de fichier
            if (!in_array($file['type'], $allowed_types)) {
                $_SESSION['error'] = "Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.";
                header("Location: admin_dashboard.php");
                exit();
            }
            
            // Validation de la taille
            if ($file['size'] > $max_size) {
                $_SESSION['error'] = "Le fichier est trop volumineux. Taille maximale : 5MB.";
                header("Location: admin_dashboard.php");
                exit();
            }
            
            // Créer le dossier uploads s'il n'existe pas
            $upload_dir = 'uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Générer un nom unique pour le fichier
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
            $photo_path = $upload_dir . $new_filename;
            
            // Déplacer le fichier uploadé
            if (!move_uploaded_file($file['tmp_name'], $photo_path)) {
                $_SESSION['error'] = "Erreur lors de l'upload de la photo.";
                header("Location: admin_dashboard.php");
                exit();
            }
            
            // Supprimer l'ancienne photo si elle existe
            $stmt = $conn->prepare("SELECT photo_profil FROM users WHERE id = ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (!empty($row['photo_profil']) && file_exists($row['photo_profil'])) {
                    unlink($row['photo_profil']);
                }
            }
            $stmt->close();
        }
        
        // Début de la transaction
        $conn->begin_transaction();
        
        // Mise à jour de la table users
        if ($photo_path) {
            // Si une nouvelle photo a été uploadée
            if (!empty($mot_de_passe)) {
                // Avec photo et mot de passe
                $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, statut_compte = ?, photo_profil = ?, mot_de_passe = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $nom, $prenom, $email, $statut_compte, $photo_path, $hashed_password, $admin_id);
            } else {
                // Avec photo seulement
                $stmt = $conn->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, statut_compte = ?, photo_profil = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $nom, $prenom, $email, $statut_compte, $photo_path, $admin_id);
            }
        } else {
            // Sans nouvelle photo
            if (!empty($mot_de_passe)) {
                // Avec mot de passe seulement
                $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, statut_compte = ?, mot_de_passe = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $nom, $prenom, $email, $statut_compte, $hashed_password, $admin_id);
            } else {
                // Sans photo ni mot de passe
                $stmt = $conn->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, statut_compte = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nom, $prenom, $email, $statut_compte, $admin_id);
            }
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour des informations utilisateur.");
        }
        $stmt->close();
        
        // Mise à jour de la table personnels
        // Vérifier si un enregistrement existe dans personnels
        $stmt = $conn->prepare("SELECT id FROM personnels WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Mise à jour de l'enregistrement existant
            $stmt = $conn->prepare("UPDATE personnels SET matricule = ? WHERE id = ?");
            $stmt->bind_param("si", $matricule, $admin_id);
        } else {
            // Insertion d'un nouvel enregistrement
            $statut_personnel = 'Actif';
            $stmt = $conn->prepare("INSERT INTO personnels (id, matricule, statut) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $admin_id, $matricule, $statut_personnel);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour du matricule.");
        }
        $stmt->close();
        
        // Commit de la transaction
        $conn->commit();
        
        // Log de l'action
        $log_message = "Modification de l'administrateur : " . $prenom . " " . $nom . " (ID: " . $admin_id . ")";
        error_log($log_message);
        
        $_SESSION['success'] = "Les informations de l'administrateur ont été modifiées avec succès.";
        header("Location: admin_dashboard.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback en cas d'erreur
        $conn->rollback();
        
        // Supprimer la photo uploadée en cas d'erreur
        if (isset($photo_path) && file_exists($photo_path)) {
            unlink($photo_path);
        }
        
        error_log("Erreur modification admin: " . $e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors de la modification : " . $e->getMessage();
        header("Location: admin_dashboard.php");
        exit();
    }
    
} else {
    // Méthode non autorisée
    $_SESSION['error'] = "Méthode non autorisée.";
    header("Location: admin_dashboard.php");
    exit();
}

$conn->close();
?>