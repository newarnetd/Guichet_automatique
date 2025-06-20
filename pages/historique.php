<?php
session_start(); 
require '../Backend/connexion/conn.php'; 

if (!isset($_SESSION['user_id'])) {
    exit("Accès non autorisé");
}

$idPersonnel = $_SESSION['user_id'];

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

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Historique des actions</title>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f7fa;
        padding: 20px;
        color: #333;
        justify-content: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    h2 {
        text-align: center;
        margin-bottom: 30px;
        color: #222;
        font-weight: 700;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
    }

    thead {
        background: linear-gradient(90deg, #4e54c8, #8f94fb);
        color: #fff;
    }

    thead th {
        padding: 15px 20px;
        font-weight: 700;
        text-align: left;
        user-select: none;
    }

    tbody tr {
        border-bottom: 1px solid #eee;
        transition: background-color 0.3s ease;
    }

    tbody tr:nth-child(even) {
        background: #f9fafc;
    }

    tbody tr:hover {
        background-color: #dbe4ff;
        cursor: default;
    }

    tbody td {
        padding: 15px 20px;
        font-size: 0.95rem;
    }

    tbody td:first-child {
        font-weight: 600;
        color: #4e54c8;
    }

    tbody td:nth-child(2) {
        font-style: italic;
        color: #666;
        width: 130px;
    }

    @media (max-width: 768px) {
        table, thead, tbody, th, td, tr {
            display: block;
        }
        thead tr {
            display: none;
        }
        tbody tr {
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            background: #fff;
            padding: 15px;
        }
        tbody td {
            padding: 8px 10px;
            text-align: right;
            position: relative;
            font-size: 0.9rem;
        }
        tbody td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 700;
            color: #4e54c8;
            text-transform: uppercase;
            font-size: 0.75rem;
        }
        tbody td:first-child {
            color: #4e54c8;
        }
        tbody td:nth-child(2) {
            font-style: italic;
            color: #666;
            width: auto;
        }
    }
</style>

</head>
<body>

<h2>Historique de vos actions</h2>

<table>
    <thead>
        <tr>
            <th>IdHistorique</th>
            <th>Date & Heure</th>
            <th>Type d'Événement</th>
            <th>Message</th>
            <th>Guichet</th>
            <th>Personnel</th>
            <th>Client</th>
        </tr>
    </thead>
    <tbody>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $personnelFullName = trim(($row['personnel_nom'] ?? '') . ' ' . ($row['personnel_prenom'] ?? ''));
            $clientFullName = trim(($row['client_nom'] ?? '') . ' ' . ($row['client_prenom'] ?? ''));
            echo "<tr>";
            echo "<td data-label='IdHistorique'>" . htmlspecialchars($row['idHistorique']) . "</td>";
            echo "<td data-label='Date & Heure'>" . htmlspecialchars(tempsRelatif($row['dateHeure'])) . "</td>";
            echo "<td data-label='Type d\'Événement'>" . htmlspecialchars($row['typeEvenement']) . "</td>";
            echo "<td data-label='Message'>" . htmlspecialchars($row['message']) . "</td>";
            echo "<td data-label='Guichet'>" . htmlspecialchars($row['guichet_nom'] ?? 'Inconnu') . "</td>";
            echo "<td data-label='Personnel'>" . ($personnelFullName !== '' ? htmlspecialchars($personnelFullName) : 'Inconnu') . "</td>";
            echo "<td data-label='Client'>" . ($clientFullName !== '' ? htmlspecialchars($clientFullName) : 'Inconnu') . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center'>Aucune donnée trouvée pour votre historique.</td></tr>";
    }
    $stmt->close();
    ?>
    </tbody>
</table>

</body>
</html>
