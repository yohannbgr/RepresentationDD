<?php
header("Content-Type: text/html; charset=UTF-8");
require_once 'connexion.php';
$pdo = getConnexion();

$id_mal = $_GET['id'] ?? null;

if ($id_mal) {
    $stmt = $pdo->prepare("SELECT * FROM MALADIE WHERE id_maladie = ?");
    $stmt->execute([$id_mal]);
    $maladie = $stmt->fetch();
    if (!$maladie) { header('Location: maladie.php'); exit; }

    // Top 10 pays pour cette maladie
    $top = $pdo->prepare("
        SELECT p.nom, r.nom_region, ROUND(AVG(m.valeur),2) AS moy, MAX(m.annee) AS derniere_annee
        FROM MESURE m
        JOIN PAYS p ON p.id_pays = m.id_pays
        JOIN REGION_OMS r ON r.id_region = p.id_region
        WHERE m.id_maladie = ?
        GROUP BY p.id_pays, p.nom, r.nom_region
        ORDER BY moy DESC
        LIMIT 10
    ");
    $top->execute([$id_mal]);
    $top_pays = $top->fetchAll();
    $max_val = $top_pays ? $top_pays[0]['moy'] : 1;

    // Évolution par année
    $evo = $pdo->prepare("
        SELECT m.annee, ROUND(AVG(m.valeur),2) AS moy_mondiale, COUNT(DISTINCT m.id_pays) AS nb_pays
        FROM MESURE m WHERE m.id_maladie = ?
        GROUP BY m.annee ORDER BY m.annee
    ");
    $evo->execute([$id_mal]);
    $evolution = $evo->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title><?= htmlspecialchars($maladie['nom']) ?></title><link rel="stylesheet" href="css/style.css"></head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <div style="margin-bottom:1rem;"><a href="maladie.php" class="btn btn-secondary btn-sm">← Retour</a></div>
    <div class="page-header">
        <h1><?= htmlspecialchars($maladie['nom']) ?></h1>
        <p><?= htmlspecialchars($maladie['categorie']) ?> · Agent : <?= htmlspecialchars($maladie['agent_pathogene'] ?? 'N/A') ?></p>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
        <div>
            <h2 class="section-title">Top 10 pays (valeur moyenne)</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Pays</th><th>Région</th><th>Valeur moy.</th></tr></thead>
                    <tbody>
                    <?php foreach ($top_pays as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nom']) ?></td>
                        <td><?= htmlspecialchars($p['nom_region']) ?></td>
                        <td>
                            <div class="stat-bar-wrap">
                                <span><?= $p['moy'] ?></span>
                                <div class="stat-bar-bg"><div class="stat-bar-fill" style="width:<?= round($p['moy']/$max_val*100) ?>%"></div></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div>
            <h2 class="section-title">Évolution mondiale (moyenne)</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Année</th><th>Moyenne mondiale</th><th>Nb pays</th></tr></thead>
                    <tbody>
                    <?php foreach ($evolution as $e): ?>
                    <tr>
                        <td><strong><?= $e['annee'] ?></strong></td>
                        <td style="color:var(--accent)"><?= $e['moy_mondiale'] ?></td>
                        <td><?= $e['nb_pays'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
</body></html>
<?php
header("Content-Type: text/html; charset=UTF-8");
} else {
    $maladies = $pdo->query("
        SELECT ma.*, COUNT(m.id_mesure) AS nb_mesures, COUNT(DISTINCT m.id_pays) AS nb_pays
        FROM MALADIE ma
        LEFT JOIN MESURE m ON m.id_maladie = ma.id_maladie
        GROUP BY ma.id_maladie
        ORDER BY nb_mesures DESC
    ")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Maladies — Surveillance</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h1>Maladies</h1>
        <p><?= count($maladies) ?> maladies surveillées</p>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Maladie</th><th>Catégorie</th><th>Agent pathogène</th><th>Pays</th><th>Mesures</th><th>Détail</th></tr></thead>
            <tbody>
            <?php foreach ($maladies as $m): ?>
            <tr>
                <td><strong><?= htmlspecialchars($m['nom']) ?></strong></td>
                <td><?= htmlspecialchars($m['categorie']) ?></td>
                <td style="color:var(--text-muted)"><?= htmlspecialchars($m['agent_pathogene'] ?? '') ?></td>
                <td><?= $m['nb_pays'] ?></td>
                <td><?= number_format($m['nb_mesures']) ?></td>
                <td><a href="maladie.php?id=<?= $m['id_maladie'] ?>" class="btn btn-secondary btn-sm">Voir</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>
</body></html>
<?php } ?>
