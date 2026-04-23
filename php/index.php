<?php
header("Content-Type: text/html; charset=UTF-8");
// index.php — Tableau de bord principal
require_once 'connexion.php';
$pdo = getConnexion();

// Stats globales
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM PAYS)     AS nb_pays,
        (SELECT COUNT(*) FROM MALADIE)  AS nb_maladies,
        (SELECT COUNT(*) FROM MESURE)   AS nb_mesures,
        (SELECT COUNT(*) FROM REGION_OMS) AS nb_regions
")->fetch();

// Top 3 maladies les plus déclarées
$top_maladies = $pdo->query("
    SELECT ma.nom, COUNT(m.id_mesure) AS nb, ROUND(AVG(m.valeur),2) AS moy
    FROM MESURE m
    JOIN MALADIE ma ON ma.id_maladie = m.id_maladie
    GROUP BY ma.id_maladie, ma.nom
    ORDER BY nb DESC
    LIMIT 3
")->fetchAll();

// Top 10 pays TB (dernière année dispo)
$top_pays = $pdo->query("
    SELECT p.nom AS pays, r.nom_region AS region, ROUND(AVG(m.valeur),2) AS val
    FROM MESURE m
    JOIN PAYS p ON p.id_pays = m.id_pays
    JOIN REGION_OMS r ON r.id_region = p.id_region
    JOIN INDICATEUR i ON i.id_indicateur = m.id_indicateur
    WHERE i.code_indicateur = 'MDG_0000000020'
      AND m.annee = (SELECT MAX(annee) FROM MESURE)
    GROUP BY p.id_pays, p.nom, r.nom_region
    ORDER BY val DESC
    LIMIT 10
")->fetchAll();

$max_val = $top_pays ? $top_pays[0]['val'] : 1;

// Répartition par région
$regions = $pdo->query("
    SELECT r.nom_region, COUNT(DISTINCT p.id_pays) AS nb_pays, COUNT(m.id_mesure) AS nb_mesures
    FROM REGION_OMS r
    JOIN PAYS p ON p.id_region = r.id_region
    LEFT JOIN MESURE m ON m.id_pays = p.id_pays
    GROUP BY r.id_region, r.nom_region
    ORDER BY nb_mesures DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surveillance Maladies — Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h1>Tableau de bord</h1>
        <p>Surveillance épidémiologique mondiale — Source : WHO Global Health Observatory</p>
    </div>

    <!-- Stats globales -->
    <div class="cards-grid">
        <div class="card">
            <div class="card-label">Pays surveillés</div>
            <div class="card-value"><?= number_format($stats['nb_pays']) ?></div>
            <div class="card-sub"><?= $stats['nb_regions'] ?> régions OMS</div>
        </div>
        <div class="card">
            <div class="card-label">Maladies</div>
            <div class="card-value"><?= $stats['nb_maladies'] ?></div>
            <div class="card-sub">Infectieuses surveillées</div>
        </div>
        <div class="card">
            <div class="card-label">Mesures enregistrées</div>
            <div class="card-value"><?= number_format($stats['nb_mesures']) ?></div>
            <div class="card-sub">Données WHO GHO</div>
        </div>
        <div class="card">
            <div class="card-label">Indicateurs</div>
            <div class="card-value">6</div>
            <div class="card-sub">TB · VIH · Paludisme · Santé</div>
        </div>
    </div>

    <!-- Top 3 maladies -->
    <h2 class="section-title">Top 3 maladies les plus déclarées</h2>
    <div class="cards-grid" style="grid-template-columns: repeat(3,1fr); margin-bottom:2.5rem;">
        <?php foreach ($top_maladies as $i => $m): ?>
        <div class="card">
            <div class="card-label">#<?= $i+1 ?></div>
            <div class="card-value" style="font-size:1.4rem;"><?= htmlspecialchars($m['nom']) ?></div>
            <div class="card-sub"><?= number_format($m['nb']) ?> mesures · moy. <?= $m['moy'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
        <!-- Top 10 pays incidence TB -->
        <div>
            <h2 class="section-title">Top 10 — Incidence TB (dernière année)</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Pays</th><th>Région</th><th>Incidence /100K</th></tr></thead>
                    <tbody>
                    <?php foreach ($top_pays as $p): ?>
                    <tr>
                        <td><a href="pays.php?iso=<?= urlencode($p['pays']) ?>" style="color:var(--accent);text-decoration:none;"><?= htmlspecialchars($p['pays']) ?></a></td>
                        <td><?= htmlspecialchars($p['region']) ?></td>
                        <td>
                            <div class="stat-bar-wrap">
                                <span><?= $p['val'] ?></span>
                                <div class="stat-bar-bg">
                                    <div class="stat-bar-fill" style="width:<?= round($p['val']/$max_val*100) ?>%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Répartition régionale -->
        <div>
            <h2 class="section-title">Répartition par région OMS</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Région</th><th>Pays</th><th>Mesures</th></tr></thead>
                    <tbody>
                    <?php foreach ($regions as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nom_region']) ?></td>
                        <td><?= $r['nb_pays'] ?></td>
                        <td><?= number_format($r['nb_mesures']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
