<?php
session_start(); 
require 'Backend/connexion/conn.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$idPersonnel = $_SESSION['user_id'];

// Vérifier le type d'utilisateur
$stmt = $conn->prepare("SELECT type FROM users WHERE id = ?");
$stmt->bind_param("i", $idPersonnel);
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

function tempsRelatif($datetime) {
    $now = new DateTime();
    $date = new DateTime($datetime);
    $diff = $now->diff($date);

    if ($diff->y > 0) return "il y a " . $diff->y . " an" . ($diff->y > 1 ? "s" : "");
    if ($diff->m > 0) return "il y a " . $diff->m . " mois";
    if ($diff->d > 0) return "il y a " . $diff->d . " jour" . ($diff->d > 1 ? "s" : "");
    if ($diff->h > 0) return "il y a " . $diff->h . " heure" . ($diff->h > 1 ? "s" : "");
    if ($diff->i > 0) return "il y a " . $diff->i . " minute" . ($diff->i > 1 ? "s" : "");
    if ($diff->s > 0) return "il y a " . $diff->s . " seconde" . ($diff->s > 1 ? "s" : "");
    return "à l'instant";
}

$query = "
    SELECT h.*, 
           g.nom AS guichet_nom, 
           p.nom AS personnel_nom, p.prenom AS personnel_prenom,
           c.nom AS client_nom, c.prenom AS client_prenom
    FROM historique h
    LEFT JOIN guichetautomatique g ON h.idGuichet = g.idGuichet
    LEFT JOIN users p ON h.idPersonnel = p.id
    LEFT JOIN users c ON h.idClient = c.id
    WHERE h.idPersonnel = ?
    ORDER BY h.dateHeure DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idPersonnel);
$stmt->execute();
$result = $stmt->get_result();

// Récupérer les statistiques
$statsQuery = "
    SELECT 
        COUNT(*) as total_actions,
        COUNT(DISTINCT DATE(dateHeure)) as jours_actifs,
        COUNT(DISTINCT idGuichet) as guichets_utilises
    FROM historique 
    WHERE idPersonnel = ?
";
$stmtStats = $conn->prepare($statsQuery);
$stmtStats->bind_param("i", $idPersonnel);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();
$stmtStats->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Historique des actions</title>
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
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f8f9fa;
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

    table {
        margin-bottom: 0;
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

    .badge-event {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 0.8rem;
    }

    .badge-retrait {
        background-color: #fef3cd;
        color: #856404;
    }

    .badge-depot {
        background-color: #d4edda;
        color: #155724;
    }

    .badge-consultation {
        background-color: #d1ecf1;
        color: #0c5460;
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
                <h2 class="mb-0 kaushan-script">Historique des Actions</h2>
                <p class="text-muted mb-0 moon-dance-regular" style="font-size: 25px !important;">Consultation de vos activités</p>
            </div>
            <div class="text-end">
                <small class="text-muted moon-dance-regular" style="font-size: 25px !important;">Connecté en tant que: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></small>
                <br>
                <small class="text-muted moon-dance-regular" style="font-size: 18px !important;"><?= date('d/m/Y H:i') ?></small>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-activity mb-2" style="font-size: 2rem;"></i>
                    <h3><?= number_format($stats['total_actions'] ?? 0) ?></h3>
                    <p class="kaushan-script">Actions totales</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-calendar-check mb-2" style="font-size: 2rem;"></i>
                    <h3><?= number_format($stats['jours_actifs'] ?? 0) ?></h3>
                    <p class="kaushan-script">Jours actifs</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-bank mb-2" style="font-size: 2rem;"></i>
                    <h3><?= number_format($stats['guichets_utilises'] ?? 0) ?></h3>
                    <p class="kaushan-script">Guichets utilisés</p>
                </div>
            </div>
        </div>

        <!-- Tableau de l'historique -->
        <div class="card">
            <div class="card-body">
                <h3 class="section-title kaushan-script">
                    <i class="bi bi-clock-history"></i>
                    Détails de l'historique
                </h3>

                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date & Heure</th>
                                    <th>Type d'Événement</th>
                                    <th>Message</th>
                                    <th>Guichet</th>
                                    <th>Client</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $clientFullName = trim(($row['client_nom'] ?? '') . ' ' . ($row['client_prenom'] ?? ''));
                                
                                // Déterminer la classe du badge selon le type d'événement
                                $badgeClass = 'badge-autre';
                                $typeEvent = strtolower($row['typeEvenement']);
                                if (strpos($typeEvent, 'retrait') !== false) {
                                    $badgeClass = 'badge-retrait';
                                } elseif (strpos($typeEvent, 'dépôt') !== false || strpos($typeEvent, 'depot') !== false) {
                                    $badgeClass = 'badge-depot';
                                } elseif (strpos($typeEvent, 'consultation') !== false) {
                                    $badgeClass = 'badge-consultation';
                                }
                            ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($row['idHistorique']) ?></strong></td>
                                    <td>
                                        <span class="time-badge">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= htmlspecialchars(tempsRelatif($row['dateHeure'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-event <?= $badgeClass ?>">
                                            <?= htmlspecialchars($row['typeEvenement']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['message']) ?></td>
                                    <td>
                                        <i class="bi bi-bank2 text-primary me-1"></i>
                                        <?= htmlspecialchars($row['guichet_nom'] ?? 'Inconnu') ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-person text-success me-1"></i>
                                        <?= $clientFullName !== '' ? htmlspecialchars($clientFullName) : 'Inconnu' ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        Aucune action enregistrée pour le moment.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initialiser les tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>