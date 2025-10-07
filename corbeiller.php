<?php
session_start();
$conn = new mysqli("localhost", "root", "", "guichet_automatique");

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
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
    $user = $result->fetch_assoc();
    $type = $user['type'];

    if ($type !== "admin") {
        header("Location: pages/home.php");
        exit();
    }
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return "il y a " . $diff . " sec";
    $minutes = floor($diff / 60);
    if ($minutes < 60) return "il y a " . $minutes . " min";
    $hours = floor($minutes / 60);
    if ($hours < 24) return "il y a " . $hours . " h";
    $days = floor($hours / 24);
    if ($days < 30) return "il y a " . $days . " j";
    $months = floor($days / 30);
    if ($months < 12) return "il y a " . $months . " mois";
    $years = floor($months / 12);
    return "il y a " . $years . " an(s)";
}

$success = "";
$error = "";

// Restaurer un élément
if (isset($_GET['restaurer'])) {
    $id = intval($_GET['restaurer']);
    $result = $conn->query("SELECT * FROM corbeille WHERE id = $id");
    
    if ($row = $result->fetch_assoc()) {
        $table = $row['table_source'];
        $data = json_decode($row['donnees'], true);
        $columns = implode(", ", array_keys($data));
        $values = implode("', '", array_map([$conn, 'real_escape_string'], array_values($data)));
        $id_original = intval($row['id_original']);

        if ($conn->query("INSERT INTO `$table` (id, $columns) VALUES ($id_original, '$values')")) {
            $conn->query("DELETE FROM corbeille WHERE id = $id");
            $success = "Élément restauré avec succès.";
        } else {
            $error = "Erreur lors de la restauration : " . $conn->error;
        }
    }
}

// Supprimer définitivement
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    if ($conn->query("DELETE FROM corbeille WHERE id = $id")) {
        $success = "Élément supprimé définitivement.";
    } else {
        $error = "Erreur lors de la suppression.";
    }
}

// Vider la corbeille
if (isset($_GET['vider'])) {
    if ($conn->query("DELETE FROM corbeille")) {
        $success = "Corbeille vidée avec succès.";
    } else {
        $error = "Erreur lors du vidage de la corbeille.";
    }
}

// Récupérer les statistiques
$statsQuery = "SELECT COUNT(*) as total, 
                      COUNT(DISTINCT table_source) as tables_distinctes,
                      MIN(date_action) as plus_ancien
               FROM corbeille";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Récupérer les éléments de la corbeille
$result = $conn->query("SELECT * FROM corbeille ORDER BY date_action DESC");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Corbeille - Gestion des Suppressions</title>
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
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }
        
        thead {
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: white;
        }
        
        thead th {
            border: none;
            font-weight: 600;
            padding: 15px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s ease;
        }
        
        tbody tr:hover {
            background-color: #f8f9ff;
        }
        
        tbody td {
            padding: 15px;
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .badge-table {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        
        .badge-users {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-guichet {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-historique {
            background-color: #fef3cd;
            color: #856404;
        }
        
        .badge-autre {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .time-badge {
            background-color: #f8f9fa;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #6c757d;
            display: inline-block;
        }
        
        .data-preview {
            max-width: 300px;
            max-height: 100px;
            overflow: auto;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-family: 'Courier New', monospace;
        }
        
        .empty-trash {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-trash i {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #667eea;
            font-size: 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #643a8a 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
        
        @media (max-width: 768px) {
            .sidebar { 
                width: 100% !important; 
                min-height: auto; 
            }
            .d-flex { 
                flex-direction: column; 
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
                <h2 class="mb-0 kaushan-script">Corbeille</h2>
                <p class="text-muted mb-0 moon-dance-regular" style="font-size: 25px !important;">Gestion des éléments supprimés</p>
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

        <!-- Statistiques -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-trash mb-2" style="font-size: 2rem;"></i>
                    <h3><?= number_format($stats['total'] ?? 0) ?></h3>
                    <p>Éléments dans la corbeille</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-table mb-2" style="font-size: 2rem;"></i>
                    <h3><?= number_format($stats['tables_distinctes'] ?? 0) ?></h3>
                    <p>Tables concernées</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-calendar-event mb-2" style="font-size: 2rem;"></i>
                    <h3><?= $stats['plus_ancien'] ? timeAgo($stats['plus_ancien']) : 'N/A' ?></h3>
                    <p>Élément le plus ancien</p>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            
            <?php if ($result && $result->num_rows > 0): ?>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#viderModal">
                    <i class="bi bi-trash-fill me-2"></i>Vider la corbeille
                </button>
            <?php endif; ?>
        </div>

        <!-- Tableau de la corbeille -->
        <div class="card">
            <div class="card-body">
                <h3 class="section-title">
                    <i class="bi bi-archive"></i>
                    Éléments supprimés
                </h3>

                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Table Source</th>
                                    <th>ID Original</th>
                                    <th>Données</th>
                                    <th>Action</th>
                                    <th>Date</th>
                                    <th>Opérations</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $badgeClass = 'badge-autre';
                                $tableSource = strtolower($row['table_source']);
                                if (strpos($tableSource, 'user') !== false) {
                                    $badgeClass = 'badge-users';
                                } elseif (strpos($tableSource, 'guichet') !== false) {
                                    $badgeClass = 'badge-guichet';
                                } elseif (strpos($tableSource, 'historique') !== false) {
                                    $badgeClass = 'badge-historique';
                                }
                            ?>
                                <tr>
                                    <td><strong>#<?= $row['id'] ?></strong></td>
                                    <td>
                                        <span class="badge-table <?= $badgeClass ?>">
                                            <?= htmlspecialchars($row['table_source']) ?>
                                        </span>
                                    </td>
                                    <td><?= $row['id_original'] ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#dataModal<?= $row['id'] ?>">
                                            <i class="bi bi-eye"></i> Voir
                                        </button>
                                    </td>
                                    <td><?= htmlspecialchars($row['type_action']) ?></td>
                                    <td>
                                        <span class="time-badge">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= timeAgo($row['date_action']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-success" 
                                                    data-bs-toggle="tooltip" 
                                                    title="Restaurer"
                                                    onclick="confirmerRestauration(<?= $row['id'] ?>)">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="tooltip" 
                                                    title="Supprimer définitivement"
                                                    onclick="confirmerSuppression(<?= $row['id'] ?>)">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal pour afficher les données -->
                                <div class="modal fade" id="dataModal<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-file-earmark-text me-2"></i>
                                                    Données de l'élément #<?= $row['id'] ?>
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <pre class="data-preview" style="max-height: 400px; max-width: 100%;"><?= json_encode(json_decode($row['donnees']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-trash">
                        <i class="bi bi-trash"></i>
                        <h4 class="text-muted">La corbeille est vide</h4>
                        <p class="text-muted">Aucun élément supprimé pour le moment</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour vider la corbeille -->
<div class="modal fade" id="viderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Confirmer le vidage
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir vider complètement la corbeille ?</p>
                <p class="text-danger"><strong>Cette action est irréversible et supprimera définitivement tous les éléments.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="corbeiller.php?vider=1" class="btn btn-danger">
                    <i class="bi bi-trash-fill me-2"></i>Vider la corbeille
                </a>
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
    
    // Initialiser les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function confirmerRestauration(id) {
    if (confirm('Êtes-vous sûr de vouloir restaurer cet élément ?')) {
        window.location.href = 'corbeiller.php?restaurer=' + id;
    }
}

function confirmerSuppression(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer définitivement cet élément ?\n\nCette action est irréversible !')) {
        window.location.href = 'corbeiller.php?supprimer=' + id;
    }
}
</script>

</body>
</html>