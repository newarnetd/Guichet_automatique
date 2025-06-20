<?php
session_start();

$host = "localhost";
$user = "u331909252_CkfJY";
$password = "P#MK|0Phg4"; 
$dbname = "u331909252_t5z3E";
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
        SELECT u.id, u.nom, u.prenom, u.email, u.type,u.photo_profil, u.statut_compte, COALESCE(cb.solde, 0) as solde
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Tableau de Bord</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar p-3" style="width: 250px;">
        <div class="text-center mb-4">
            <h4 class="text-white mb-0">
                <i class="bi bi-shield-check me-2"></i>Admin Panel
            </h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="admin.php">
                    <i class="bi bi-speedometer2 me-2"></i>Tableau de Bord
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="add.php">
                    <i class="bi bi-people-fill me-2"></i>Utilisateurs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pages/historique.php">
                    <i class="bi bi-clock-history me-2"></i>Historique
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="addGuichet.php">
                    <i class="bi bi-shop-window me-2"></i>Guichets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="Paramètres.php">
                    <i class="bi bi-sliders me-2"></i>Paramètres
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="corbeiller.php">
                    <i class="bi bi-trash me-2"></i>Corbeille
                </a>
            </li>
            <hr class="text-white-50">
            <li class="nav-item">
                <a class="nav-link" href="pages/logout.php" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');">
                    <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Tableau de Bord</h2>
                <p class="text-muted mb-0">Gestion du système bancaire</p>
            </div>
            <div class="text-end">
                <small class="text-muted">Connecté en tant que: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></small>
                <br>
                <small class="text-muted"><?= date('d/m/Y H:i') ?></small>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions mb-4">
            <h5 class="mb-3"><i class="bi bi-lightning-fill me-2"></i>Actions Rapides</h5>
            <div class="row g-2">
                <div class="col-md-3">
                    <a href="add.php" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus-fill me-1"></i> Nouvel Utilisateur
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="addGuichet.php" class="btn btn-success w-100">
                        <i class="bi bi-shop-window me-1"></i> Nouveau Guichet
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="pages/historique.php" class="btn btn-info w-100">
                        <i class="bi bi-clock-history me-1"></i> Voir Historique
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="corbeiller.php" class="btn btn-warning w-100">
                        <i class="bi bi-trash-fill me-1"></i> Corbeille
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Recent Activities -->
        <?php if (isset($activitiesResult) && $activitiesResult->num_rows > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">
                    <i class="bi bi-activity me-2"></i>Activités Récentes
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Événement</th>
                                <th>Message</th>
                                <th>Client</th>
                                <th>Guichet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($activity = $activitiesResult->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <small class="text-muted"><?= tempsRelatif($activity['dateHeure']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?= htmlspecialchars($activity['typeEvenement']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($activity['message']) ?></td>
                                <td><?= htmlspecialchars(trim($activity['client_nom'] . ' ' . $activity['client_prenom'])) ?></td>
                                <td><?= htmlspecialchars($activity['guichet_nom'] ?? 'Non spécifié') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Users Table -->
        <?php if (isset($usersResult) && $usersResult->num_rows > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">
                    <i class="bi bi-people-fill me-2"></i>Clients du Système
                    <span class="badge bg-secondary ms-2"><?= $usersResult->num_rows ?></span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom Complet</th>
                                <th>Email</th>
                                <th>Solde</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $usersResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars(trim($user['nom'] . ' ' . $user['prenom'])) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td class="fw-bold"><?= formatCurrency($user['solde']) ?></td>
                                <td><?= getStatusBadge($user['statut_compte']) ?></td>
                                <td>
                                    <div class="btn-group-actions">
                                        <a href="modifier.php?id=<?= urlencode($user['id']) ?>" 
                                           class="btn btn-sm btn-outline-warning" 
                                           title="Modifier">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="supprimer.php?id=<?= urlencode($user['id']) ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Supprimer"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Admins Table -->
        <?php if (isset($adminsResult) && $adminsResult->num_rows > 0): ?>
        <div class="card">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">
                    <i class="bi bi-shield-fill-check me-2"></i>Administrateurs du Système
                    <span class="badge bg-danger ms-2"><?= $adminsResult->num_rows ?></span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom Complet</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Profil</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($admin = $adminsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($admin['id']) ?></td>
                                <td><?= htmlspecialchars(trim($admin['nom'] . ' ' . $admin['prenom'])) ?></td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td>
                                    <span class="badge bg-danger"><?= htmlspecialchars($admin['type']) ?></span>
                                </td>
                                <td><?= getStatusBadge($admin['statut_compte']) ?></td>
                                <td title="Cliquez pour Afficher cet Image en taille réelle">
  <?php
    $photoPath = $admin['photo_profil'];
    if (!empty($admin['photo_profil']) && file_exists($photoPath)) {
  ?>
    <a href="<?php echo $photoPath; ?>">
      <div style="
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-image: url('<?php echo $photoPath; ?>');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
      ">
      </div>
    </a>
  <?php
    } else {
      echo '<span style="font-size:12px;color:gray;">Aucune image</span>';
    }
  ?>
</td>


                                <td>
                                    <div class="btn-group-actions">
                                        <a href="modifier.php?id=<?= urlencode($admin['id']) ?>" 
                                           class="btn btn-sm btn-outline-warning" 
                                           title="Modifier">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                        <a href="supprimer.php?id=<?= urlencode($admin['id']) ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Supprimer"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="Vous ne pouvez pas vous supprimer">
                                            <i class="bi bi-shield-x"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
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
});

// Confirm before navigation to destructive actions
document.querySelectorAll('a[href*="supprimer"]').forEach(link => {
    link.addEventListener('click', function(e) {
        if (!confirm('Cette action est irréversible. Continuer ?')) {
            e.preventDefault();
        }
    });
});
</script>

</body>
</html>