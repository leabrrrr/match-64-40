<?php
$css = "pageMatch.css";
include "$racine/src/vues/entete.php";
?>

<section class="bandeau-match">
    <div class="conteneur">
        <a class="retour" href="index.php">← Tous les matchs</a>

        <?php if ($match['competition'] != '') { ?>
            <p class="competition-match">
                <?php echo logo($match['logo_competition'], $match['competition'], 'logo-division'); ?>
                <?php echo htmlspecialchars($match['competition']); ?>
                <?php if ($match['niveau'] != '') { ?>
                    <span class="<?php echo classeNiveau($match['niveau']); ?>"><?php echo htmlspecialchars($match['niveau']); ?></span>
                <?php } ?>
            </p>
        <?php } ?>

        <div class="affiche">
            <div class="affiche-equipe">
                <?php echo logo($match['logo_domicile'], $match['equipe_domicile'], 'logo-affiche'); ?>
                <span class="affiche-nom"><?php echo htmlspecialchars($match['equipe_domicile']); ?></span>
                <span class="affiche-role">Domicile</span>
            </div>
            <div class="affiche-vs">VS</div>
            <div class="affiche-equipe">
                <?php echo logo($match['logo_exterieur'], $match['equipe_exterieur'], 'logo-affiche'); ?>
                <span class="affiche-nom"><?php echo htmlspecialchars($match['equipe_exterieur']); ?></span>
                <span class="affiche-role">Extérieur</span>
            </div>
        </div>

        <div class="pastilles">
            <span class="pastille"><?php echo dateLongue($match['date_match']); ?></span>
            <span class="pastille pastille-accent"><?php echo heure($match['heure_match']); ?></span>
            <span class="pastille"><?php echo htmlspecialchars($match['lieu']); ?></span>
        </div>
    </div>
</section>

<div class="conteneur page grille-detail">

    <!-- Infos pratiques du match -->
    <section class="carte">
        <h2>Infos pratiques</h2>

        <?php if ($details) { ?>
            <div class="chiffres">
                <div class="chiffre">
                    <span class="chiffre-libelle">Prix d'entrée</span>
                    <span class="chiffre-valeur">
                        <?php echo $details['prix_entree'] !== null ? prix($details['prix_entree']) : "—"; ?>
                    </span>
                </div>
                <div class="chiffre">
                    <span class="chiffre-libelle">Ouverture des portes</span>
                    <span class="chiffre-valeur">
                        <?php echo $details['horaire_ouvertures_portes'] !== null ? heure($details['horaire_ouvertures_portes']) : "—"; ?>
                    </span>
                </div>
            </div>

            <ul class="services">
                <li>
                    <span>Buvette</span>
                    <?php if ($details['buvette']) { ?>
                        <span class="badge badge-vert">Oui</span>
                    <?php } else { ?>
                        <span class="badge badge-gris">Non</span>
                    <?php } ?>
                </li>
                <li>
                    <span>Parking</span>
                    <?php if ($details['parking']) { ?>
                        <span class="badge badge-vert">Oui</span>
                    <?php } else { ?>
                        <span class="badge badge-gris">Non</span>
                    <?php } ?>
                </li>
            </ul>

            <?php if ($details['description_organisation'] != '') { ?>
                <h3>Organisation</h3>
                <p class="texte"><?php echo nl2br(htmlspecialchars($details['description_organisation'])); ?></p>
            <?php } ?>

            <?php if ($details['coordonnees_gps'] != '') { ?>
                <h3>Accès</h3>
                <p class="texte">Coordonnées GPS : <?php echo htmlspecialchars($details['coordonnees_gps']); ?></p>
                <a class="bouton" target="_blank"
                   href="https://www.google.com/maps?q=<?php echo urlencode($details['coordonnees_gps']); ?>">Ouvrir dans Google Maps</a>
            <?php } ?>

            <?php if ($details['contact_telephone'] != '' || $details['contact_mail'] != '') { ?>
                <h3>Contact</h3>
                <p class="texte">
                    <?php echo htmlspecialchars($details['contact_telephone']); ?><br>
                    <a href="mailto:<?php echo htmlspecialchars($details['contact_mail']); ?>"><?php echo htmlspecialchars($details['contact_mail']); ?></a>
                </p>
            <?php } ?>

        <?php } else { ?>
            <div class="vide-petit">
                Le club n'a pas encore renseigné les infos pratiques de ce match. Revenez un peu plus tard !
            </div>
        <?php } ?>
    </section>

    <!-- Infos du club organisateur -->
    <?php if (!$club) { ?>
        <aside class="carte carte-club">
            <p class="surtitre-carte">Club organisateur</p>
            <?php if ($match['departement'] != '64' && $match['departement'] != '40') { ?>
                <!-- Match joué hors du 64/40 : le club qui reçoit n'est pas un club du site -->
                <p class="texte">Ce match se joue hors du 64/40 : les infos pratiques sont à voir auprès du club qui reçoit.</p>
            <?php } else { ?>
                <p class="texte">Ce club n'est pas encore inscrit sur le site : les infos pratiques ne sont pas disponibles.</p>
                <a href="index.php?action=inscription_club">Vous êtes ce club ? Créez votre compte</a>
            <?php } ?>
        </aside>
    <?php } else { ?>
        <aside class="carte carte-club">
            <p class="surtitre-carte">Club organisateur</p>
            <div class="club-entete">
                <!-- Logo FFBB du club qui reçoit (ou ses initiales) -->
                <?php echo logo($match['logo_domicile'], $club['nom'] ?? '', 'logo logo-club-carte'); ?>
                <h2><?php echo htmlspecialchars($club['nom'] ?? ''); ?></h2>
            </div>

            <?php if ($club['description'] != '') { ?>
                <p class="texte"><?php echo htmlspecialchars($club['description'] ?? ''); ?></p>
            <?php } ?>

            <dl class="coordonnees">
                <dt>Adresse</dt>
                <dd><?php echo htmlspecialchars($club['adresse'] ?? ''); ?></dd>
                <dt>Téléphone</dt>
                <dd><?php echo htmlspecialchars($club['telephone'] ?? ''); ?></dd>
                <dt>Email</dt>
                <dd><a href="mailto:<?php echo htmlspecialchars($club['email'] ?? ''); ?>"><?php echo htmlspecialchars($club['email'] ?? ''); ?></a></dd>
            </dl>
        </aside>
    <?php } ?>

</div>

<!-- Classement de la poule + calendrier de l'équipe qui reçoit -->
<?php if (!empty($classement) || !empty($matchsEquipe)) { ?>
<div class="conteneur grille-poule">

    <?php if (!empty($classement)) { ?>
        <section class="carte carte-tableau">
            <h2>Classement<?php if ($match['nom_poule'] != '') { echo " · " . htmlspecialchars($match['nom_poule']); } ?></h2>
            <div class="tableau-scroll">
                <table class="classement">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Équipe</th>
                            <th title="Matchs joués">J</th>
                            <th title="Gagnés">G</th>
                            <th title="Perdus">P</th>
                            <th title="Différence de points">+/-</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classement as $ligne) { ?>
                            <?php
                            // On surligne les deux équipes du match
                            $classeLigne = '';
                            if (memeEquipe($ligne['equipe'], $match['equipe_domicile']) || memeEquipe($ligne['equipe'], $match['equipe_exterieur'])) {
                                $classeLigne = 'ligne-match';
                            }
                            ?>
                            <tr class="<?php echo $classeLigne; ?>">
                                <td class="position"><span><?php echo $ligne['position']; ?></span></td>
                                <td>
                                    <div class="equipe-classement">
                                        <?php echo logo($ligne['logo'], $ligne['equipe'], 'logo-club'); ?>
                                        <?php echo htmlspecialchars($ligne['equipe']); ?>
                                    </div>
                                </td>
                                <td><?php echo $ligne['joues']; ?></td>
                                <td><?php echo $ligne['gagnes']; ?></td>
                                <td><?php echo $ligne['perdus']; ?></td>
                                <td>
                                    <?php if ($ligne['difference'] > 0) { ?>
                                        <span class="diff-plus">+<?php echo $ligne['difference']; ?></span>
                                    <?php } else if ($ligne['difference'] < 0) { ?>
                                        <span class="diff-moins"><?php echo $ligne['difference']; ?></span>
                                    <?php } else { ?>
                                        0
                                    <?php } ?>
                                </td>
                                <td class="points"><?php echo $ligne['points']; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($matchsEquipe)) { ?>
        <section class="carte carte-tableau">
            <h2>Tous les matchs de <?php echo htmlspecialchars($match['equipe_domicile']); ?></h2>
            <div class="tableau-scroll">
                <table class="calendrier">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Adversaire</th>
                            <th class="cellule-action">Résultat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matchsEquipe as $rencontre) { ?>
                            <?php
                            // L'équipe joue-t-elle à domicile ? Qui est l'adversaire ?
                            $aDomicile = ($rencontre['domicile'] == $match['equipe_domicile']);
                            $adversaire = $aDomicile ? $rencontre['exterieur'] : $rencontre['domicile'];
                            $joue = ($rencontre['score_domicile'] !== null && $rencontre['score_exterieur'] !== null);
                            if ($joue) {
                                $notreScore = $aDomicile ? $rencontre['score_domicile'] : $rencontre['score_exterieur'];
                                $leurScore = $aDomicile ? $rencontre['score_exterieur'] : $rencontre['score_domicile'];
                            }
                            // Couleur de la ligne : victoire (vert), défaite (rouge), à venir (neutre)
                            $classeLigne = 'ligne-a-venir';
                            if ($joue) {
                                $classeLigne = ($notreScore > $leurScore) ? 'ligne-victoire' : 'ligne-defaite';
                            }
                            // Le match qu'on est en train de regarder
                            if ($rencontre['date'] == $match['date_match'] && $adversaire == $match['equipe_exterieur']) {
                                $classeLigne = 'ligne-match';
                            }
                            ?>
                            <?php
                            // Lien vers la page du match, s'il est dans notre base
                            // (sauf pour le match qu'on regarde déjà)
                            if ($rencontre['id_match'] == $match['id_match']) {
                                $rencontre['id_match'] = null;
                            }
                            if ($rencontre['id_match'] !== null) {
                                $classeLigne .= ' ligne-cliquable';
                            }
                            ?>
                            <tr class="<?php echo $classeLigne; ?>">
                                <td class="cellule-date">
                                    <strong><?php echo $rencontre['date'] != '' ? date('d/m', strtotime($rencontre['date'])) : '--/--'; ?></strong>
                                    <span>J<?php echo htmlspecialchars($rencontre['journee']); ?></span>
                                </td>
                                <td>
                                    <span class="lieu-match <?php echo $aDomicile ? 'lieu-dom' : 'lieu-ext'; ?>"><?php echo $aDomicile ? 'Dom.' : 'Ext.'; ?></span>
                                    <?php if ($rencontre['id_match'] !== null) { ?>
                                        <a class="lien-rencontre" href="index.php?action=detail_match&id=<?php echo $rencontre['id_match']; ?>"><?php echo htmlspecialchars($adversaire); ?></a>
                                    <?php } else { ?>
                                        <?php echo htmlspecialchars($adversaire); ?>
                                    <?php } ?>
                                </td>
                                <td class="cellule-action">
                                    <?php if ($joue) { ?>
                                        <span class="score"><?php echo $notreScore; ?> - <?php echo $leurScore; ?></span>
                                        <?php if ($notreScore > $leurScore) { ?>
                                            <span class="badge badge-vert">V</span>
                                        <?php } else { ?>
                                            <span class="badge badge-rouge">D</span>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <span class="a-venir"><?php echo heure($rencontre['heure']); ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php } ?>

</div>
<?php } ?>

<?php include "$racine/src/vues/pied.php"; ?>
