<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestion des Utilisateurs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .container {
      margin-top: 50px;
    }
    .btn-action {
      margin-right: 5px;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2 class="mb-4 text-primary">Liste des Utilisateurs</h2>

    <!-- Bouton d'ajout -->
    <div class="mb-3">
      <a href="ajouter_utilisateur.html" class="btn btn-success">+ Ajouter un utilisateur</a>
    </div>

    <!-- Tableau des utilisateurs -->
    <div class="table-responsive">
      <table class="table table-bordered table-hover table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Exemple de données statiques, à remplacer dynamiquement -->
          <tr>
            <td>1</td>
            <td>Ngurwiwa</td>
            <td>Peter</td>
            <td>peter@example.com</td>
            <td>Administrateur</td>
            <td>
              <a href="modifier_utilisateur.html?id=1" class="btn btn-sm btn-warning btn-action">Modifier</a>
              <a href="supprimer_utilisateur.php?id=1" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Supprimer cet utilisateur ?');">Supprimer</a>
            </td>
          </tr>
          <!-- Ajouter d'autres utilisateurs ici -->
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
