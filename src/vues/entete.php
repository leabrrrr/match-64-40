<?php
include_once "$racine/src/vues/outils.php";

// Pour mettre en surbrillance le lien de la page actuelle dans le menu
$actionCourante = isset($_GET['action']) ? $_GET['action'] : 'accueil';
$periodeCourante = isset($_GET['periode']) ? $_GET['periode'] : 'weekend';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public/img/favicon.png">
    <title><?php echo htmlspecialchars($titre); ?> | MATCH 64/40</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- Polices Google : Big Shoulders (très gros titres), Chakra Petch (titres), Inter (texte) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Chakra+Petch:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="public/css/header.css">
    <link rel="stylesheet" href="public/css/footer.css">
    <!-- $css est défini dans chaque vue avant d'inclure l'entête -->
    <link rel="stylesheet" href="public/css/<?php echo $css; ?>">
</head>
<body>

<header class="entete">
    <div class="conteneur entete-contenu">
        <a class="marque" href="index.php">
            <span class="ballon"></span>
            <span>Match <strong>64/40</strong></span>
        </a>
        <nav class="menu">
            <a href="index.php"
               class="<?php if ($actionCourante == 'accueil' && $periodeCourante == 'weekend') { echo 'actif'; } ?>">Ce week-end</a>
            <a href="index.php?action=accueil&periode=tous"
               class="<?php if ($periodeCourante == 'tous') { echo 'actif'; } ?>">Tous les matchs</a>
            <a href="index.php?action=accueil&periode=exterieur"
               class="<?php if ($periodeCourante == 'exterieur') { echo 'actif'; } ?>">À l'extérieur</a>

            <?php if (estAdmin()) { ?>
                <a href="index.php?action=admin"
                   class="<?php if ($actionCourante == 'admin') { echo 'actif'; } ?>">Administration</a>
                <a href="index.php?action=deconnexion" class="bouton bouton-contour bouton-petit">Déconnexion</a>
            <?php } else if (estConnecte()) { ?>
                <a href="index.php?action=dashboard_club"
                   class="<?php if ($actionCourante == 'dashboard_club') { echo 'actif'; } ?>">Mon club</a>
                <a href="index.php?action=deconnexion" class="bouton bouton-contour bouton-petit">Déconnexion</a>
            <?php } else { ?>
                <a href="index.php?action=login_club" class="bouton bouton-petit">Espace club</a>
            <?php } ?>
        </nav>
    </div>
</header>

<main>
