<?php
$css = "admin.css";
include "$racine/src/vues/entete.php";

// Compter les clubs en attente de validation
$enAttente = 0;
foreach ($clubs as $club) {
    if (!$club['valide']) {
        $enAttente++;
    }
}
?>

<div class="conteneur page">

    <div class="titre-page">
        <div>
            <p class="surtitre-sombre">Administration</p>
            <h1>Tableau de bord</h1>
        </div>
        <div class="actions-titre">
            <a class="bouton" href="index.php?action=sync_ffbb">Synchroniser la FFBB</a>
            <a class="bouton bouton-contour" href="index.php?action=gestion_matchs">Ajouter un match</a>
        </div>
    </div>

    <?php if ($message != "") { ?>
        <div class="alerte alerte-succes"><?php echo $message; ?></div>
    <?php } ?>

    <div class="stats">
        <div class="stat">
            <span class="stat-valeur"><?php echo count($clubs) - $enAttente; ?></span>
            <span class="stat-libelle">Clubs validés</span>
        </div>
        <div class="stat <?php if ($enAttente > 0) { echo 'stat-alerte'; } ?>">
            <span class="stat-valeur"><?php echo $enAttente; ?></span>
            <span class="stat-libelle">Demandes en attente</span>
        </div>
        <div class="stat">
            <span class="stat-valeur"><?php echo count($matchs); ?></span>
            <span class="stat-libelle">Matchs enregistrés</span>
        </div>
    </div>

    <!-- Comptes clubs -->
    <section class="carte carte-tableau">
        <h2>Comptes clubs</h2>
        <div class="tableau-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Club</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>État</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($clubs as $club) { ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($club['nom'] ?? ''); ?></strong></td>
                            <td><?php echo htmlspecialchars($club['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($club['telephone'] ?? ''); ?></td>
                            <td>
                                <?php if ($club['valide']) { ?>
                                    <span class="badge badge-vert">Validé</span>
                                <?php } else { ?>
                                    <span class="badge badge-attente">En attente</span>
                                <?php } ?>
                            </td>
                            <td class="cellule-action">
                                <?php if (!$club['valide']) { ?>
                                    <a class="bouton bouton-petit" href="index.php?action=admin&valider=<?php echo $club['id_club']; ?>">Valider</a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Tous les matchs -->
    <section class="carte carte-tableau">
        <h2>Matchs à venir</h2>
        <?php if (!empty($matchs)) { ?>
            <div class="tableau-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Match</th>
                            <th>Lieu</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($matchs as $match) { ?>
                            <tr>
                                <td class="cellule-date">
                                    <strong><?php echo date('d/m/Y', strtotime($match['date_match'])); ?></strong>
                                    <span><?php echo heure($match['heure_match']); ?></span>
                                </td>
                                <td class="cellule-match">
                                    <?php echo htmlspecialchars($match['equipe_domicile']); ?>
                                    <span class="vs">vs</span>
                                    <?php echo htmlspecialchars($match['equipe_exterieur']); ?>
                                    <span class="sous-texte"><?php echo htmlspecialchars($match['competition'] ?? ''); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($match['lieu']); ?></td>
                                <td class="cellule-action">
                                    <a class="lien-action" href="index.php?action=detail_match&id=<?php echo $match['id_match']; ?>">Voir</a>
                                    <a class="lien-action" href="index.php?action=modifier_match&id=<?php echo $match['id_match']; ?>">Modifier</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="vide-petit">Aucun match. Lancez une synchronisation FFBB.</div>
        <?php } ?>
    </section>

    <!-- Création d'un club -->
    <section class="carte">
        <h2>Créer un compte club</h2>
        <p class="texte-aide">Le compte créé ici est validé directement.</p>
        <form method="POST" action="index.php?action=admin">
            <div class="grille-2">
                <div class="champ">
                    <label for="nom">Nom du club (comme sur la FFBB)</label>
                    <input type="text" id="nom" name="nom" maxlength="100" required>
                </div>
                <div class="champ">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="50" required>
                </div>
                <div class="champ">
                    <label for="mdp">Mot de passe</label>
                    <input type="password" id="mdp" name="mdp" required>
                </div>
                <div class="champ">
                    <label for="telephone">Téléphone</label>
                    <input type="text" id="telephone" name="telephone" maxlength="50">
                </div>
            </div>
            <div class="champ">
                <label for="adresse">Adresse</label>
                <input type="text" id="adresse" name="adresse" maxlength="200">
            </div>
            <div class="champ">
                <label for="description">Présentation</label>
                <textarea id="description" name="description" maxlength="200" rows="2"></textarea>
            </div>
            <button type="submit" class="bouton">Créer le club</button>
        </form>
    </section>

</div>

<?php include "$racine/src/vues/pied.php"; ?>
