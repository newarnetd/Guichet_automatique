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

    <!-- Messages d'alerte -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Boutons d'action -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="admin.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour au tableau de bord
        </a>
        <a href="ajouter_utilisateur.php" class="btn btn-success">
            <i class="bi bi-person-plus me-2"></i>Ajouter un utilisateur
        </a>
    </div>

    <!-- Liste des administrateurs -->
    <div class="row">
        <?php if (empty($admins)): ?>
            <div class="col-12">
                <div class="admin-card text-center">
                    <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                    <h5 class="text-muted">Aucun administrateur trouvé</h5>
                    <p class="text-muted">Commencez par ajouter des administrateurs au système.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($admins as $admin): ?>
                <div class="col-lg-6 col-xl-4">
                    <div class="admin-card">
                        <div class="d-flex align-items-center mb-3">
                            <!-- Photo de profil -->
                            <div class="me-3">
                                <?php if (!empty($admin['photo_profil']) && file_exists($admin['photo_profil'])): ?>
                                    <img src="<?php echo htmlspecialchars($admin['photo_profil']); ?>" 
                                         alt="Photo de <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?>"
                                         class="profile-image"
                                         data-bs-toggle="modal" 
                                         data-bs-target="#imageModal"
                                         onclick="showImageModal('<?php echo htmlspecialchars($admin['photo_profil']); ?>', '<?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?>', '<?php echo htmlspecialchars($admin['email']); ?>', '<?php echo htmlspecialchars($admin['matricule']); ?>', '<?php echo htmlspecialchars($admin['statut_compte']); ?>')">
                                <?php else: ?>
                                    <div class="default-avatar"
                                         data-bs-toggle="modal" 
                                         data-bs-target="#imageModal"
                                         onclick="showImageModal('', '<?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?>', '<?php echo htmlspecialchars($admin['email']); ?>', '<?php echo htmlspecialchars($admin['matricule']); ?>', '<?php echo htmlspecialchars($admin['statut_compte']); ?>')">
                                        <?php echo strtoupper(substr($admin['prenom'], 0, 1) . substr($admin['nom'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Informations de base -->
                            <div class="admin-info">
                                <div class="admin-name">
                                    <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?>
                                </div>
                                <div class="admin-email">
                                    <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($admin['email']); ?>
                                </div>
                                <div class="admin-matricule">
                                    <i class="bi bi-card-text me-1"></i>Mat: <?php echo htmlspecialchars($admin['matricule']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Statut et actions -->
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="status-badge <?php 
                                echo $admin['statut_compte'] === 'Activé' ? 'status-active' : 
                                    ($admin['statut_compte'] === 'Désactivé' ? 'status-inactive' : 'status-blocked'); 
                            ?>">
                                <?php echo htmlspecialchars($admin['statut_compte']); ?>
                            </span>
                            
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="tooltip" title="Voir les détails"
                                        onclick="showImageModal('<?php echo htmlspecialchars($admin['photo_profil']); ?>', '<?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?>', '<?php echo htmlspecialchars($admin['email']); ?>', '<?php echo htmlspecialchars($admin['matricule']); ?>', '<?php echo htmlspecialchars($admin['statut_compte']); ?>')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal pour l'affichage de l'image et des détails -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">
                    <i class="bi bi-person-circle me-2"></i>Profil Administrateur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body image-modal">
                <!-- Image de profil -->
                <div id="profileImageContainer" class="mb-4">
                    <img id="modalProfileImage" class="modal-profile-image" style="display: none;">
                    <div id="modalDefaultAvatar" class="default-avatar mx-auto" style="width: 150px; height: 150px; font-size: 3rem; display: none;"></div>
                </div>
                
                <!-- Détails de l'administrateur -->
                <div class="admin-details">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Nom complet</h6>
                            <p id="modalAdminName" class="fw-bold"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Adresse email</h6>
                            <p id="modalAdminEmail"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Matricule</h6>
                            <p id="modalAdminMatricule" class="text-primary fw-bold"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Statut du compte</h6>
                            <span id="modalAdminStatus" class="status-badge"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
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