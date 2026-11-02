<?php
$css = "pageClub.css";
include "$racine/src/vues/entete.php";
?>

<div class="conteneur page">
    <div class="carte carte-formulaire carte-large">
        <div class="formulaire-entete">
            <h1>Demande de compte club</h1>
            <p>Votre demande sera validée par l'administrateur avant de pouvoir vous connecter.</p>
        </div>

        <?php if ($erreur != "") { ?>
            <div class="alerte alerte-erreur"><?php echo $erreur; ?></div>
        <?php } ?>

        <?php if ($message != "") { ?>
            <div class="alerte alerte-succes"><?php echo $message; ?></div>
            <a class="bouton" href="index.php">Retour à l'accueil</a>
        <?php } else { ?>
            <form method="POST" action="index.php?action=inscription_club">
                <div class="champ">
                    <label for="nom">Nom du club</label>
                    <input type="text" id="nom" name="nom" maxlength="100" required>
                    <span class="aide">Écrivez-le comme sur le site de la FFBB, sinon vos matchs ne seront pas importés.</span>
                </div>

                <div class="grille-2">
                    <div class="champ">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="50" required>
                    </div>
                    <div class="champ">
                        <label for="mdp">Mot de passe</label>
                        <input type="password" id="mdp" name="mdp" required>
                        <span class="aide">6 caractères minimum</span>
                    </div>
                </div>

                <div class="grille-2">
                    <div class="champ">
                        <label for="adresse">Adresse</label>
                        <input type="text" id="adresse" name="adresse" maxlength="200">
                    </div>
                    <div class="champ">
                        <label for="telephone">Téléphone</label>
                        <input type="text" id="telephone" name="telephone" maxlength="50">
                    </div>
                </div>

                <div class="champ">
                    <label for="description">Présentation du club</label>
                    <textarea id="description" name="description" maxlength="200" rows="3"></textarea>
                </div>

                <button type="submit" class="bouton bouton-large">Envoyer la demande</button>
            </form>
        <?php } ?>
    </div>
</div>

<?php include "$racine/src/vues/pied.php"; ?>
