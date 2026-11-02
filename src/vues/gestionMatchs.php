<?php
$css = "admin.css";
include "$racine/src/vues/entete.php";
?>

<div class="conteneur page">
    <a class="retour-sombre" href="index.php?action=admin">← Retour à l'administration</a>

    <section class="carte carte-formulaire-admin">
        <h1>Ajouter un match</h1>
        <p class="texte-aide">Pour un match qui n'est pas dans l'API FFBB (match amical, tournoi...).</p>

        <?php if (isset($message)) { ?>
            <div class="alerte alerte-succes"><?php echo $message; ?></div>
        <?php } ?>

        <form method="POST" action="index.php?action=gestion_matchs">
            <div class="grille-2">
                <div class="champ">
                    <label for="dom">Équipe à domicile</label>
                    <input type="text" id="dom" name="equipe_domicile" maxlength="100" required>
                </div>
                <div class="champ">
                    <label for="ext">Équipe à l'extérieur</label>
                    <input type="text" id="ext" name="equipe_exterieur" maxlength="100" required>
                </div>
                <div class="champ">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date_match" required>
                </div>
                <div class="champ">
                    <label for="heure">Heure</label>
                    <input type="time" id="heure" name="heure_match" required>
                </div>
                <div class="champ">
                    <label for="lieu">Lieu</label>
                    <input type="text" id="lieu" name="lieu" maxlength="100" required>
                </div>
                <div class="champ">
                    <label for="departement">Département de la salle</label>
                    <select id="departement" name="departement">
                        <option value="64">64 - Pyrénées-Atlantiques</option>
                        <option value="40">40 - Landes</option>
                        <option value="">Hors 64/40</option>
                    </select>
                </div>
                <div class="champ">
                    <label for="club">Club organisateur</label>
                    <select id="club" name="id_club" required>
                        <?php foreach($clubs as $club) { ?>
                            <option value="<?php echo $club['id_club']; ?>"><?php echo htmlspecialchars($club['nom'] ?? ''); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="bouton">Ajouter le match</button>
        </form>
    </section>
</div>

<?php include "$racine/src/vues/pied.php"; ?>
