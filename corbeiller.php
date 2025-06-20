<?php
session_start();
$conn = new mysqli("localhost", "root", "", "guichet_automatique");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
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

if (!isset($_SESSION['user_id'])) {
    die("Accès refusé. Veuillez vous connecter.");
}
$user_id = $_SESSION['user_id'];

if (isset($_GET['restaurer'])) {
    $id = intval($_GET['restaurer']);
    $result = $conn->query("SELECT * FROM corbeille WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        $table = $row['table_source'];
        $data = json_decode($row['donnees'], true);
        $columns = implode(", ", array_keys($data));
        $values = implode("', '", array_map([$conn, 'real_escape_string'], array_values($data)));
        $id_original = intval($row['id_original']);

        $conn->query("INSERT INTO `$table` (id, $columns) VALUES ($id_original, '$values')");
        $conn->query("DELETE FROM corbeille WHERE id = $id");
    }
}

if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    $result = $conn->query("SELECT * FROM corbeille WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        $conn->query("DELETE FROM corbeille WHERE id = $id");
    }
}

if (isset($_GET['vider'])) {
    $conn->query("DELETE FROM corbeille");
}
$result = $conn->query("SELECT * FROM corbeille ORDER BY date_action DESC");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Corbeille</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon_io/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="favicon_io/favicon-16x16.png" />
</head>
<body class="bg-light">

<div class="container mt-5">
    <h1 class="mb-4 text-primary">🗑️ Corbeille</h1>

    <div class="mb-3">
        <a href="admin.php" class="btn btn-outline-primary me-2">Retour à l'accueil</a>
        <a href="corbeiller.php?vider=1" class="btn btn-danger"
           onclick="return confirm('Vider la corbeille ?');">Vider la corbeille</a>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Table</th>
                    <th>ID Original</th>
                    <th>Données</th>
                    <th>Action</th>
                    <th>Date</th>
                    <th>Opérations</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['table_source']) ?></td>
                        <td><?= $row['id_original'] ?></td>
                        <td><pre class="mb-0"><?= json_encode(json_decode($row['donnees']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre></td>
                        <td><?= $row['type_action'] ?></td>
                        <td><?= timeAgo($row['date_action']) ?></td>
                        <td>
                            <a href="corbeiller.php?restaurer=<?= $row['id'] ?>"
                               class="btn btn-success btn-sm mb-1"
                               onclick="return confirm('Restaurer cet élément ?')">♻️ Restaurer</a>
                            <a href="corbeiller.php?supprimer=<?= $row['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Supprimer définitivement ?')">🗑️ Supprimer</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">La corbeille est vide.</div>
    <?php endif; ?>
</div>

</body>
</html>
