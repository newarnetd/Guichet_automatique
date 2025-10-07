<?php
session_start();
require 'Backend/connexion/conn.php';

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

    if ($_SESSION['type'] === "admin") {
        header("Location: admin.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guichet automatique</title>
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
</head>
<body>
    <nav class="navbar">
        <div class="max-width">
            <div class="logo kaushan-script"><a style="color:rgb(255, 255, 255)" href="#">Guichet Automatique</span></a></div>
           <ul class="menu">
                    <li>
                        <a class="menu-btn" href="pages/historique.php" role="tab" title="Voir l'historique de vos transactions">
                            Historique
                        </a>
                    </li>
                    <li>
                        <a href="pages/retrait.php" class="menu-btn" title="Effectuer un retrait">
                            Retrait
                        </a>
                    </li>
                    <li>
                        <a class="menu-btn" href="pages/depot.php" role="tab" title="Faire un dépôt">
                            Dépôt
                        </a>
                    </li>
                    <li>
                        <a class="menu-btn" href="Paramètres.php" role="tab" title="Gérer vos paramètres">
                            Paramètres
                        </a>
                    </li>
                    <li>
                        <a class="menu-btn" href="pages/logout.php" role="tab" title="Se déconnecter de votre compte">
                            Se Déconnecter
                        </a>
                    </li>
                </ul>

            <div class="menu-btn">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>
    <section class="home" id="home">
        <div class="max-width">
           <div class="home-content">
    <div class="text-2">Méga projet de fin de cycle</div>
    <div class="text-3"><span class="typing"></span></div>
    <a href="index.php" title="Accéder à l'espace d'administration">
        Se connecter comme Admin
    </a>
    <a href="pages/guichet.php" class="All-Guichet" title="Voir la liste des guichets disponibles">
        Consulter les guichets
    </a>
</div>

        </div>
        <p class="decription moon-dance-regular">Dans le cadre de mon cursus académique</p>
        <p class="decription right moon-dance-regular">Jean-luc Kashindi Nestor </p>
    </section>

    <section class="about" id="about">
        <div class="max-width">
            <h2 class="title viga-regular">Aimeriez-vous en savoir plus sur cette plateforme&nbsp;?</h2>
            <div class="about-content">
                <div class="column left"></div>
               <div class="column right">
    <div class="text">Une révolution à Bujumbura</div>
    <p>Cette plateforme de guichet automatique a été spécialement conçue pour faciliter l’accès à divers services à Bujumbura. Adaptée aux besoins des citoyens et des institutions, elle permet une gestion rapide, autonome et sécurisée des démarches. Un espace a été aménagé pour permettre la location et l’utilisation de cette plateforme dans différents quartiers de la ville.</p>
    <a href="addCompteBanque.php" style="background:#000;color:#fff;border:none" title="Créer un nouveau compte bancaire">
    Créer un compte bancaire
</a>

</div>

            </div>
        </div>
    </section>

   

    <section class="contact" id="contact">
    <div class="max-width">
        <h2 class="title viga-regular">Une fierté au sein de la communauté burundaise</h2>
        <div class="contact-content">
            <div class="column left">
                <div class="text">Merci de nous contacter</div>
                <p>Nous vous remercions chaleureusement de nous avoir contactés via notre plateforme. Votre initiative est grandement appréciée et nous sommes impatients de vous offrir notre meilleur service.</p>
                <div class="icons">
                    <div class="row">
                        <i class="fas fa-user"></i>
                        <div class="info">
                            <div class="head viga-regular">Plateforme de Guichet Automatique</div>
                            <div class="sub-title">Service numérique à Bujumbura</div>
                        </div>
                    </div>
                    <div class="row">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="info">
                            <div class="head">Adresse</div>
                            <div class="sub-title">Gihosha, 4ᵉ rue, numéro 5</div>
                        </div>
                    </div>
                   <div class="row">
    <i class="fas fa-envelope"></i>
    <div class="info">
        <div class="head">Email</div>
        <div class="sub-title">
            <a href="mailto:contact.guichet@gmail.com">contact.guichet@gmail.com</a>
        </div>
    </div>
</div>

                </div>
            </div>
        </div>
    </div>
</section>

    <footer>
        <p class="moon-dance-regular">Jean-luc Kashindi Nestor</p>
    
    <div style="margin-top: 10px;">
        <a href="https://www.facebook.com/ChristineSafi" target="_blank" style="margin: 0 10px; color:rgb(255, 255, 255);">
            <i class="fab fa-facebook fa-lg"></i>
        </a>
        <a href="https://www.twitter.com/ChristineSafi" target="_blank" style="margin: 0 10px; color:rgb(255, 255, 255);">
            <i class="fab fa-twitter fa-lg"></i>
        </a>
        <a href="https://www.linkedin.com/in/ChristineSafi" target="_blank" style="margin: 0 10px; color:rgb(255, 255, 255);">
            <i class="fab fa-linkedin fa-lg"></i>
        </a>
        <a href="https://www.instagram.com/ChristineSafi" target="_blank" style="margin: 0 10px; color:rgb(255, 255, 255);">
            <i class="fab fa-instagram fa-lg"></i>
        </a>
    </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
