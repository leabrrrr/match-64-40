<?php
$css = "pageClub.css";
include "$racine/src/vues/entete.php";

// Valeurs déjà enregistrées, ou vide si rien n'est encore rempli
$prix = $details ? $details['prix_entree'] : '';
$portes = ($details && $details['horaire_ouvertures_portes'] !== null) ? substr($details['horaire_ouvertures_portes'], 0, 5) : '';
$description = $details ? $details['description_organisation'] : '';
$gps = $details ? $details['coordonnees_gps'] : '';
$telephone = $details ? $details['contact_telephone'] : '';
$mail = $details ? $details['contact_mail'] : '';
$buvette = $details ? $details['buvette'] : 0;
$parking = $details ? $details['parking'] : 0;

// L'admin revient à la page admin, le club à son tableau de bord
$pageRetour = estAdmin() ? "admin" : "dashboard_club";
?>

<div class="conteneur page">
    <div class="carte carte-formulaire carte-large">
        <a class="retour-sombre" href="index.php?action=<?php echo $pageRetour; ?>">← Retour</a>
        <div class="formulaire-entete formulaire-entete-gauche">
            <p class="surtitre-sombre"><?php echo dateLongue($match['date_match']); ?> · <?php echo heure($match['heure_match']); ?></p>
            <h1><?php echo htmlspecialchars($match['equipe_domicile']); ?> <span class="vs">vs</span> <?php echo htmlspecialchars($match['equipe_exterieur']); ?></h1>
            <p>Ces informations seront visibles par le public sur la page du match.</p>
        </div>

        <?php if ($message != "") { ?>
            <div class="alerte alerte-succes"><?php echo $message; ?></div>
        <?php } ?>

        <form method="POST" action="index.php?action=modifier_match&id=<?php echo $match['id_match']; ?>">
            <h3 class="groupe-titre">Accueil du public</h3>
            <div class="grille-2">
                <div class="champ">
                    <label for="prix">Prix d'entrée (€)</label>
                    <input type="number" id="prix" name="prix_entree" step="0.01" min="0" value="<?php echo $prix; ?>">
                    <span class="aide">Mettez 0 si l'entrée est gratuite</span>
                </div>
                <div class="champ">
                    <label for="portes">Ouverture des portes</label>
                    <input type="time" id="portes" name="horaire_ouvertures_portes" value="<?php echo $portes; ?>">
                </div>
            </div>

            <div class="cases">
                <label class="case">
                    <input type="checkbox" name="buvette" <?php if ($buvette) { echo "checked"; } ?>>
                    <span>Buvette sur place</span>
                </label>
                <label class="case">
                    <input type="checkbox" name="parking" <?php if ($parking) { echo "checked"; } ?>>
                    <span>Parking disponible</span>
                </label>
            </div>

            <div class="champ">
                <label for="description">Description de l'organisation</label>
                <textarea id="description" name="description_organisation" maxlength="200" rows="3"
                          placeholder="Ex : restauration sur place, animation à la mi-temps..."><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <h3 class="groupe-titre">Accès et contact</h3>
            <div class="champ">
                <label for="gps">Coordonnées GPS</label>
                <input type="text" id="gps" name="coordonnees_gps" maxlength="50" placeholder="43.8906, -0.4997" value="<?php echo htmlspecialchars($gps); ?>">
                <span class="aide">Dans Google Maps : clic droit sur la salle, puis cliquez sur les coordonnées pour les copier</span>
            </div>

            <div class="grille-2">
                <div class="champ">
                    <label for="tel">Téléphone de contact</label>
                    <input type="text" id="tel" name="contact_telephone" maxlength="50" value="<?php echo htmlspecialchars($telephone); ?>">
                </div>
                <div class="champ">
                    <label for="mail">Email de contact</label>
                    <input type="email" id="mail" name="contact_mail" maxlength="50" value="<?php echo htmlspecialchars($mail); ?>">
                </div>
            </div>

            <div class="actions-formulaire">
                <button type="submit" class="bouton">Enregistrer</button>
                <a class="bouton bouton-contour-sombre" href="index.php?action=detail_match&id=<?php echo $match['id_match']; ?>">Voir la page publique</a>
            </div>
        </form>
    </div>
</div>

<?php include "$racine/src/vues/pied.php"; ?>
