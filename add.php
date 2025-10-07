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
try {
    
    $usersQuery = "
        SELECT u.id, u.nom, u.prenom, u.email, u.statut_compte, COALESCE(cb.solde, 0) as solde
        FROM users u
        LEFT JOIN comptebancaire cb ON cb.clientId = u.id
        WHERE u.type != 'Admin'
        ORDER BY u.nom ASC
    ";
    $usersResult = $conn->query($usersQuery);
    
    if (!$usersResult) {
        throw new Exception("Erreur requête utilisateurs: " . $conn->error);
    }
    
    $adminsQuery = "
        SELECT u.id, u.nom, u.prenom, u.email, u.type, u.photo_profil, u.statut_compte, COALESCE(cb.solde, 0) as solde
        FROM users u
        LEFT JOIN comptebancaire cb ON cb.clientId = u.id
        WHERE u.type = 'Admin'
        ORDER BY u.nom ASC
    ";
    $adminsResult = $conn->query($adminsQuery);
    
    if (!$adminsResult) {
        throw new Exception("Erreur requête admins: " . $conn->error);
    }

    
    $activitiesQuery = "
        SELECT h.*, 
               u.nom AS client_nom, 
               u.prenom AS client_prenom,
               g.nom AS guichet_nom
        FROM historique h
        JOIN users u ON h.idClient = u.id
        LEFT JOIN guichetautomatique g ON h.idGuichet = g.idGuichet
        ORDER BY h.dateHeure DESC
        LIMIT 20
    ";
    $activitiesResult = $conn->query($activitiesQuery);
    
    
    if (!$activitiesResult) {
        $activitiesResult = null;
        error_log("Activities query failed: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = $e->getMessage(); 
}


function tempsRelatif($datetime) {
    $now = new DateTime();
    $date = new DateTime($datetime);
    $diff = $now->diff($date);

    if ($diff->y > 0) return "il y a " . $diff->y . " an" . ($diff->y > 1 ? "s" : "");
    if ($diff->m > 0) return "il y a " . $diff->m . " mois";
    if ($diff->d > 0) return "il y a " . $diff->d . " jour" . ($diff->d > 1 ? "s" : "");
    if ($diff->h > 0) return "il y a " . $diff->h . " heure" . ($diff->h > 1 ? "s" : "");
    if ($diff->i > 0) return "il y a " . $diff->i . " minute" . ($diff->i > 1 ? "s" : "");
    return "à l'instant";
}


function formatCurrency($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}


function getStatusBadge($status) {
    return $status === 'actif' 
        ? '<span class="badge bg-success">Actif</span>'
        : '<span class="badge bg-secondary">Inactif</span>';
}


if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'Admin') {
     // header("Location: index.php");
    //exit();
}

$error = "";
$success = "";

$query = "SELECT u.id as user_id, u.nom, u.prenom, u.email, u.statut_compte, u.photo_profil, p.matricule, p.statut 
          FROM users u 
          LEFT JOIN personnels p ON u.id = p.id 
          WHERE u.type = 'Admin' 
          ORDER BY u.nom, u.prenom";

$result = $conn->query($query);
$admins = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Tableau de Bord</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="Fonts.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/typed.js/2.0.11/typed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css"/>
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
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
        }
        .btn-group-actions {
            display: flex;
            gap: 5px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        @media (max-width: 768px) {
            .sidebar { width: 100% !important; min-height: auto; }
            .d-flex { flex-direction: column; }
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
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }
        
        .profile-image:hover {
            transform: scale(1.05);
            border-color: #667eea;
        }
        
        .default-avatar {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
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

        /* Styles pour les modales */
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
            border: none;
        }

        .modal-header {
            border-bottom: none;
            padding: 1.5rem;
        }

        .modal-body {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .modal-footer {
            border-top: none;
            padding: 1.5rem;
        }

        .modal-profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #f8f9fa;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .modal-default-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .detail-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .detail-card:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        .form-control:focus, .form-select:focus {
            border-color: #764ba2;
            box-shadow: 0 0 0 0.2rem rgba(118, 75, 162, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #643a8a 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .btn-success {
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .photo-upload-container {
            position: relative;
            display: inline-block;
        }

        .photo-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #667eea;
            border: 3px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .photo-upload-btn:hover {
            background-color: #764ba2;
            transform: scale(1.1);
        }

        .photo-upload-btn i {
            color: white;
            font-size: 1rem;
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
                <h2 class="mb-0 kaushan-script">Liste des Administrateurs</h2>
                <p class="text-muted mb-0 moon-dance-regular" style="font-size: 25px !important;">Gestion et visualisation des comptes administrateurs</p>
            </div>
            <div class="text-end">
                <small class="text-muted moon-dance-regular" style="font-size: 25px !important;">Connecté en tant que: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></small>
                <br>
                <small class="text-muted moon-dance-regular" style="font-size: 18px !important;"><?= date('d/m/Y H:i') ?></small>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="ajouter_utilisateur.php" class="btn stats-card">
                <i class="bi bi-person-plus me-2"></i>Ajouter un utilisateur
            </a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

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

        <!-- Liste des administrateurs -->
        <div class="admin-card">
            <h3 class="section-title">Administrateurs</h3>
            <?php if (empty($admins)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <i class="bi bi-inbox me-2"></i>Aucun administrateur trouvé. Commencez par ajouter des administrateurs au système.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Photo</th>
                                <th scope="col">Nom</th>
                                <th scope="col">Email</th>
                                <th scope="col">Matricule</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <!-- Photo de profil -->
                                    <td>
                                        <?php if (!empty($admin['photo_profil']) && file_exists($admin['photo_profil'])): ?>
                                            <img src="<?php echo htmlspecialchars($admin['photo_profil']); ?>" 
                                                 alt="Photo de <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?>"
                                                 class="profile-image"
                                                 onclick="showDetailsModal('<?php echo htmlspecialchars($admin['photo_profil']); ?>', '<?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?>', '<?php echo htmlspecialchars($admin['email']); ?>', '<?php echo htmlspecialchars($admin['matricule']); ?>', '<?php echo htmlspecialchars($admin['statut_compte']); ?>', '<?php echo $admin['user_id']; ?>')">
                                        <?php else: ?>
                                            <div class="default-avatar"
                                                 onclick="showDetailsModal('', '<?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?>', '<?php echo htmlspecialchars($admin['email']); ?>', '<?php echo htmlspecialchars($admin['matricule']); ?>', '<?php echo htmlspecialchars($admin['statut_compte']); ?>', '<?php echo $admin['user_id']; ?>')">
                                                <?php echo strtoupper(substr($admin['prenom'], 0, 1) . substr($admin['nom'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Nom complet -->
                                    <td><?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></td>
                                    <!-- Email -->
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <!-- Matricule -->
                                    <td><?php echo htmlspecialchars($admin['matricule']); ?></td>
                                    <!-- Statut -->
                                   
                                    <!-- Actions -->
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="tooltip" title="Voir les détails"
                                                    onclick="showDetailsModal('<?php echo htmlspecialchars($admin['photo_profil']); ?>', '<?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?>', '<?php echo htmlspecialchars($admin['email']); ?>', '<?php echo htmlspecialchars($admin['matricule']); ?>', '<?php echo htmlspecialchars($admin['statut_compte']); ?>', '<?php echo $admin['user_id']; ?>')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    data-bs-toggle="tooltip" title="Modifier"
                                                    onclick="showEditModal('<?php echo htmlspecialchars($admin['photo_profil']); ?>', '<?php echo htmlspecialchars($admin['prenom']); ?>', '<?php echo htmlspecialchars($admin['nom']); ?>', '<?php echo htmlspecialchars($admin['email']); ?>', '<?php echo htmlspecialchars($admin['matricule']); ?>', '<?php echo htmlspecialchars($admin['statut_compte']); ?>', '<?php echo $admin['user_id']; ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal pour afficher les détails d'un administrateur -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title" id="detailsModalLabel">
                    <i class="bi bi-person-circle me-2"></i>Détails de l'Administrateur
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Photo de profil -->
                <div class="text-center mb-4">
                    <img id="detailsProfileImage" class="modal-profile-image" style="display: none;">
                    <div id="detailsDefaultAvatar" class="modal-default-avatar mx-auto" style="display: none;"></div>
                </div>
                
                <!-- Informations détaillées -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-card p-3">
                            <small class="text-muted d-block mb-1"><i class="bi bi-person-fill me-2"></i>Nom complet</small>
                            <strong id="detailsAdminName" class="fs-6"></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-card p-3">
                            <small class="text-muted d-block mb-1"><i class="bi bi-envelope-fill me-2"></i>Email</small>
                            <strong id="detailsAdminEmail" class="fs-6"></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-card p-3">
                            <small class="text-muted d-block mb-1"><i class="bi bi-card-text me-2"></i>Matricule</small>
                            <strong id="detailsAdminMatricule" class="fs-6 text-primary"></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-card p-3">
                            <small class="text-muted d-block mb-1"><i class="bi bi-check-circle-fill me-2"></i>Statut</small>
                            <span id="detailsAdminStatus" class="badge"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary" onclick="openEditFromDetails()">
                    <i class="bi bi-pencil-fill me-2"></i>Modifier
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour éditer un administrateur -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Modifier l'Administrateur
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAdminForm" method="POST" action="modifier_admin.php" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" id="editAdminId" name="admin_id">
                    
                    <!-- Photo de profil -->
                    <div class="text-center mb-4">
                        <div class="photo-upload-container">
                            <img id="editProfileImagePreview" class="modal-profile-image" style="display: none;">
                            <div id="editDefaultAvatarPreview" class="modal-default-avatar mx-auto" style="display: none;"></div>
                            <label for="editPhotoInput" class="photo-upload-btn">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                            <input type="file" id="editPhotoInput" name="photo_profil" class="d-none" accept="image/*" onchange="previewImage(this)">
                        </div>
                        <small class="text-muted d-block mt-3">Cliquez sur l'icône appareil photo pour changer la photo</small>
                    </div>

                    <!-- Formulaire d'édition -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editNom" class="form-label"><i class="bi bi-person me-2"></i>Nom</label>
                            <input type="text" class="form-control" id="editNom" name="nom" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editPrenom" class="form-label"><i class="bi bi-person me-2"></i>Prénom</label>
                            <input type="text" class="form-control" id="editPrenom" name="prenom" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editEmail" class="form-label"><i class="bi bi-envelope me-2"></i>Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editMatricule" class="form-label"><i class="bi bi-card-text me-2"></i>Matricule</label>
                            <input type="text" class="form-control" id="editMatricule" name="matricule" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStatut" class="form-label"><i class="bi bi-toggle-on me-2"></i>Statut du compte</label>
                            <select class="form-select" id="editStatut" name="statut_compte" required>
                                <option value="Activé">Activé</option>
                                <option value="Désactivé">Désactivé</option>
                                <option value="Bloqué">Bloqué</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editMotDePasse" class="form-label"><i class="bi bi-lock me-2"></i>Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="editMotDePasse" name="mot_de_passe" placeholder="Laisser vide pour ne pas changer">
                            <small class="text-muted">Laisser vide si vous ne voulez pas modifier le mot de passe</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle-fill me-2"></i>Enregistrer les modifications
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Variable globale pour stocker les données de l'admin sélectionné
let currentAdminData = {};

// Fonction pour afficher les détails d'un administrateur
function showDetailsModal(photoPath, nom, email, matricule, statut, userId) {
    currentAdminData = {
        userId: userId,
        photoPath: photoPath,
        nom: nom,
        email: email,
        matricule: matricule,
        statut: statut
    };

    // Afficher la photo ou l'avatar
    const profileImage = document.getElementById('detailsProfileImage');
    const defaultAvatar = document.getElementById('detailsDefaultAvatar');
    
    if (photoPath && photoPath.trim() !== '') {
        profileImage.src = photoPath;
        profileImage.style.display = 'block';
        defaultAvatar.style.display = 'none';
    } else {
        const nameParts = nom.split(' ');
        const initials = nameParts.length >= 2 ? 
            nameParts[0].charAt(0).toUpperCase() + nameParts[nameParts.length - 1].charAt(0).toUpperCase() :
            nom.substring(0, 2).toUpperCase();
        defaultAvatar.textContent = initials;
        defaultAvatar.style.display = 'flex';
        profileImage.style.display = 'none';
    }

    // Remplir les informations
    document.getElementById('detailsAdminName').textContent = nom;
    document.getElementById('detailsAdminEmail').textContent = email;
    document.getElementById('detailsAdminMatricule').textContent = matricule || 'Non défini';
    
    // Badge de statut
    const statusBadge = document.getElementById('detailsAdminStatus');
    statusBadge.textContent = statut;
    statusBadge.className = 'badge ' + (
        statut === 'Activé' ? 'bg-success' :
        statut === 'Désactivé' ? 'bg-secondary' : 'bg-warning text-dark'
    );

    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

// Fonction pour ouvrir le modal d'édition directement
function showEditModal(photoPath, prenom, nom, email, matricule, statut, userId) {
    currentAdminData = {
        userId: userId,
        photoPath: photoPath,
        nom: prenom + ' ' + nom,
        prenom: prenom,
        nomSeul: nom,
        email: email,
        matricule: matricule,
        statut: statut
    };
    
    openEditModal(prenom, nom);
}

// Fonction pour ouvrir le modal d'édition depuis le modal de détails
function openEditFromDetails() {
    // Fermer le modal de détails
    const detailsModal = bootstrap.Modal.getInstance(document.getElementById('detailsModal'));
    detailsModal.hide();

    // Extraire prénom et nom
    const nameParts = currentAdminData.nom.split(' ');
    const prenom = nameParts[0];
    const nom = nameParts.slice(1).join(' ') || '';
    
    // Petit délai pour permettre la fermeture du premier modal
    setTimeout(() => {
        openEditModal(prenom, nom);
    }, 300);
}

// Fonction pour ouvrir le modal d'édition
function openEditModal(prenom, nom) {
    // Remplir le formulaire d'édition
    document.getElementById('editAdminId').value = currentAdminData.userId;
    document.getElementById('editNom').value = nom || currentAdminData.nomSeul || '';
    document.getElementById('editPrenom').value = prenom || currentAdminData.prenom || '';
    document.getElementById('editEmail').value = currentAdminData.email;
    document.getElementById('editMatricule').value = currentAdminData.matricule || '';
    document.getElementById('editStatut').value = currentAdminData.statut;
    document.getElementById('editMotDePasse').value = '';

    // Afficher la photo actuelle
    const editImage = document.getElementById('editProfileImagePreview');
    const editAvatar = document.getElementById('editDefaultAvatarPreview');
    
    if (currentAdminData.photoPath && currentAdminData.photoPath.trim() !== '') {
        editImage.src = currentAdminData.photoPath;
        editImage.style.display = 'block';
        editAvatar.style.display = 'none';
    } else {
        const initials = (prenom || '').charAt(0).toUpperCase() + (nom || '').charAt(0).toUpperCase();
        editAvatar.textContent = initials || 'AD';
        editAvatar.style.display = 'flex';
        editImage.style.display = 'none';
    }

    // Ouvrir le modal d'édition
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

// Fonction pour prévisualiser l'image sélectionnée
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const editImage = document.getElementById('editProfileImagePreview');
            const editAvatar = document.getElementById('editDefaultAvatarPreview');
            editImage.src = e.target.result;
            editImage.style.display = 'block';
            editAvatar.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Masquer automatiquement les alertes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Confirm before navigation to destructive actions
document.querySelectorAll('a[href*="supprimer"]').forEach(link => {
    link.addEventListener('click', function(e) {
        if (!confirm('Cette action est irréversible. Continuer ?')) {
            e.preventDefault();
        }
    });
});

// Gestion des messages
document.addEventListener("DOMContentLoaded", () => {
    const messages = document.querySelectorAll(".message-text");

    messages.forEach(msg => {
        msg.addEventListener("click", function() {
            const isShort = this.textContent.endsWith('...');
            const full = this.getAttribute('data-full');
            const short = this.getAttribute('data-short');

            if (isShort) {
                this.textContent = full; 
            } else {
                this.textContent = short;
            }
        });
    });
});
</script>

</body>
</html>