<?php
$css = "pageClub.css";
include "$racine/src/vues/entete.php";
?>

<div class="conteneur page">
    <div class="carte carte-formulaire">
        <div class="formulaire-entete">
            <span class="ballon ballon-moyen"></span>
            <h1>Espace club</h1>
            <p>Connectez-vous pour gérer les infos de vos matchs.</p>
        </div>

        <?php if ($erreur != "") { ?>
            <div class="alerte alerte-erreur"><?php echo $erreur; ?></div>
        <?php } ?>

        <form method="POST" action="index.php?action=login_club">
            <div class="champ">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="contact@monclub.fr" required>
            </div>

            <div class="champ">
                <label for="mdp">Mot de passe</label>
                <input type="password" id="mdp" name="mdp" required>
            </div>

            <button type="submit" class="bouton bouton-large">Se connecter</button>
        </form>

        <p class="formulaire-pied">Pas encore de compte ? <a href="index.php?action=inscription_club">Demander un compte club</a></p>
    </div>
</div>

<?php include "$racine/src/vues/pied.php"; ?>
