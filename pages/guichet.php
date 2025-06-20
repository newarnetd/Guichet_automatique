<?php
session_start();
require '../Backend/connexion/conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../");
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

    if ($type === "Admin") {
        header("Location: ../admin.php");
        exit();
    } 
} else {
    session_destroy();
    header("Location: ../");
    exit();
}
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Guichet Automatique</title>

    <link rel="apple-touch-icon" sizes="180x180" href="../favicon_io/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon_io/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon_io/favicon-16x16.png" />
    <link rel="manifest" href="/site.webmanifest" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1100px;
            margin-top: 40px;
            margin-bottom: 40px;
        }

        h2.section-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }

        /* Card container */
        .card-section {
            background: white;
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 6px 18px rgb(0 0 0 / 0.1);
            margin-bottom: 40px;
            transition: box-shadow 0.3s ease;
        }

        .card-section:hover {
            box-shadow: 0 10px 25px rgb(0 0 0 / 0.15);
        }

        /* Table styles inside cards */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 15px;
        }

        thead tr {
            background-color: #004085;
            color: #fff;
            border-radius: 10px;
        }

        thead th {
            padding: 15px 20px;
            font-weight: 600;
            border: none;
            text-align: left;
        }

        tbody tr {
            background-color: #e9f0fb;
            border-radius: 10px;
            transition: background-color 0.3s ease;
        }

        tbody tr:hover {
            background-color: #d0dffc;
            cursor: pointer;
        }

        tbody td {
            padding: 15px 20px;
            border: none;
            vertical-align: middle;
            color: #2c3e50;
            font-weight: 500;
        }

        /* Boutons modernisés */
        .btn-modern {
            border-radius: 25px;
            font-weight: 600;
            padding: 8px 18px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-success-modern {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgb(40 167 69 / 0.6);
        }

        .btn-success-modern:hover {
            background: linear-gradient(135deg, #218838 0%, #19692c 100%);
            box-shadow: 0 6px 18px rgb(25 104 44 / 0.8);
        }
    </style>
</head>

<body>
    <div class="container">
        <section class="card-section">
            <h2 class="section-title">Guichets en Services</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Guichet</th>
                        <th>Nom</th>
                        <th>Localisation</th>
                        <th>État</th>
                        <?php if ($type === 'Client'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM guichetautomatique";
                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                        while ($guichet = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($guichet['idGuichet']) . '</td>';
                            echo '<td>' . htmlspecialchars($guichet['nom']) . '</td>';
                            echo '<td>' . htmlspecialchars($guichet['localisation']) . '</td>';
                            echo '<td>' . htmlspecialchars($guichet['etat']) . '</td>';
                            if ($type === 'Client') {
                                echo '<td><a href="depot.php?guichet=' . $guichet['idGuichet'] . '" class="btn btn-success-modern btn-modern"><i class="fas fa-exchange-alt"></i> Faire un virement</a></td>';
                            }
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="' . ($type === 'Client' ? 5 : 4) . '" style="text-align:center; font-weight:600; padding: 20px;">Aucun guichet trouvé.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </section>


    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
