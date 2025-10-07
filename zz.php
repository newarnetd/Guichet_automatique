<?php
session_start();
require 'Backend/connexion/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'Admin') {
     // header("Location: index.php");
    //exit();
}

$error = "";
$success = "";

$query = "SELECT u.user_id, u.nom, u.prenom, u.email, u.statut_compte, u.photo_profil, p.matricule, p.statut 
          FROM users u 
          LEFT JOIN personnels p ON u.user_id = p.id 
          WHERE u.type = 'Admin' 
          ORDER BY u.nom, u.prenom";

$result = $conn->query($query);
$admins = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Administrateurs - Guichet Automatique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon_io/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="favicon_io/favicon-16x16.png" />
    <style>
        body {
            background-color: #f4f6f9;
        }
        
        .main-container {
            margin-top: 30px;
            margin-bottom: 50px;
        }
        
        .admin-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.12);
        }
        
        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
            cursor: pointer;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }
        
        .profile-image:hover {
            transform: scale(1.05);
            border-color: #198754;
        }
        
        .default-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .default-avatar:hover {
            transform: scale(1.05);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 20px;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-blocked {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .admin-info {
            flex: 1;
        }
        
        .admin-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .admin-email {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }
        
        .admin-matricule {
            color: #495057;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .image-modal .modal-body {
            text-align: center;
            padding: 30px;
        }
        
        .modal-profile-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .admin-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<div class="container main-container">
    <!-- En-tête -->
    <div class="header-section">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2"><i class="bi bi-people-fill me-2"></i>Liste des Administrateurs</h2>
                <p class="mb-0 opacity-90">Gestion et visualisation des comptes administrateurs</p>
            </div>
            <div class="stats-card">
                <h4 class="text-primary mb-1"><?php echo count($admins); ?></h4>
                <small class="text-muted">Admin(s) total</small>
            </div>
        </div>
    </div>

    

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialiser les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    function showImageModal(photoPath, fullName, email, matricule, status) {
        // Mettre à jour les informations textuelles
        document.getElementById('modalAdminName').textContent = fullName;
        document.getElementById('modalAdminEmail').textContent = email;
        document.getElementById('modalAdminMatricule').textContent = matricule;
        
        // Mettre à jour le statut avec la bonne classe CSS
        const statusElement = document.getElementById('modalAdminStatus');
        statusElement.textContent = status;
        statusElement.className = 'status-badge ' + 
            (status === 'Activé' ? 'status-active' : 
             status === 'Désactivé' ? 'status-inactive' : 'status-blocked');
        
        // Gérer l'affichage de l'image
        const modalImage = document.getElementById('modalProfileImage');
        const modalDefaultAvatar = document.getElementById('modalDefaultAvatar');
        
        if (photoPath && photoPath.trim() !== '') {
            modalImage.src = photoPath;
            modalImage.style.display = 'block';
            modalDefaultAvatar.style.display = 'none';
        } else {
            modalImage.style.display = 'none';
            modalDefaultAvatar.style.display = 'flex';
            modalDefaultAvatar.textContent = fullName.split(' ').map(n => n[0]).join('').toUpperCase();
        }
        
        // Afficher le modal
        const modal = new bootstrap.Modal(document.getElementById('imageModal'));
        modal.show();
    }
</script>

</body>
</html>