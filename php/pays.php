<?php
header("Content-Type: text/html; charset=UTF-8");
// pays.php — Liste et fiche détaillée d'un pays
require_once 'connexion.php';
$pdo = getConnexion();

$nom_pays  = $_GET['iso']    ?? null;
$filtre_mal = $_GET['maladie'] ?? '';
$filtre_an1 = $_GET['annee1'] ?? '';
$filtre_an2 = $_GET['annee2'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$par_page = 20;

// Liste des maladies pour le filtre
$maladies = $pdo->query("SELECT id_maladie, nom FROM MALADIE ORDER BY nom")->fetchAll();

if ($nom_pays) {
    // Fiche détaillée
    $stmt = $pdo->prepare("SELECT p.*, r.nom_region FROM PAYS p JOIN REGION_OMS r ON r.id_region = p.id_region WHERE p.nom = ?");
    $stmt->execute([$nom_pays]);
    $pays = $stmt->fetch();

    if (!$pays) {
        echo '<div class="container"><div class="alert alert-warning">Pays introuvable.</div></div>';
        exit;
    }

    // Mesures avec filtres
    $where = ["m.id_pays = ?"];
    $params = [$pays['id_pays']];
    if ($filtre_mal) { $where[] = "m.id_maladie = ?"; $params[] = $filtre_mal; }
    if ($filtre_an1) { $where[] = "m.annee >= ?";    $params[] = $filtre_an1; }
    if ($filtre_an2) { $where[] = "m.annee <= ?";    $params[] = $filtre_an2; }

    $whereSQL = implode(' AND ', $where);

    // Count total
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM MESURE m WHERE $whereSQL");
    $cnt->execute($params);
    $total = $cnt->fetchColumn();
    $nb_pages = max(1, ceil($total / $par_page));
    $offset   = ($page - 1) * $par_page;

    // Résultats
    $stmt2 = $pdo->prepare("
        SELECT m.annee, ma.nom AS maladie, i.libelle AS indicateur, i.unite, ROUND(m.valeur,2) AS valeur
        FROM MESURE m
        JOIN MALADIE    ma ON ma.id_maladie   = m.id_maladie
        JOIN INDICATEUR i  ON i.id_indicateur = m.id_indicateur
        WHERE $whereSQL
        ORDER BY m.annee DESC, ma.nom
        LIMIT $par_page OFFSET $offset
    ");
    $stmt2->execute($params);
    $mesures = $stmt2->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Fiche — <?= htmlspecialchars($pays['nom']) ?></title><link rel="stylesheet" href="css/style.css"></head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <div style="margin-bottom:1rem;"><a href="pays.php" class="btn btn-secondary btn-sm">← Retour à la liste</a></div>
    <div class="page-header">
        <h1><?= htmlspecialchars($pays['nom']) ?> <span style="font-size:1rem;color:var(--text-muted)"><?= $pays['code_iso'] ?></span></h1>
        <p><?= htmlspecialchars($pays['nom_region']) ?> · <?= htmlspecialchars($pays['continent']) ?></p>
    </div>

    <!-- Filtres -->
    <div class="form-card">
        <form method="get">
            <input type="hidden" name="iso" value="<?= htmlspecialchars($nom_pays) ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Maladie</label>
                    <select name="maladie">
                        <option value="">Toutes</option>
                        <?php foreach ($maladies as $mal): ?>
                        <option value="<?= $mal['id_maladie'] ?>" <?= $filtre_mal==$mal['id_maladie']?'selected':'' ?>><?= htmlspecialchars($mal['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Année début</label>
                    <input type="number" name="annee1" min="1990" max="2026" value="<?= htmlspecialchars($filtre_an1) ?>" placeholder="1990">
                </div>
                <div class="form-group">
                    <label>Année fin</label>
                    <input type="number" name="annee2" min="1990" max="2026" value="<?= htmlspecialchars($filtre_an2) ?>" placeholder="2026">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </div>
            </div>
        </form>
    </div>

    <h2 class="section-title">Mesures (<?= $total ?> résultats)</h2>
    <?php if ($mesures): ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Année</th><th>Maladie</th><th>Indicateur</th><th>Valeur</th><th>Unité</th></tr></thead>
            <tbody>
            <?php foreach ($mesures as $row): ?>
            <tr>
                <td><strong><?= $row['annee'] ?></strong></td>
                <td><?= htmlspecialchars($row['maladie']) ?></td>
                <td><?= htmlspecialchars($row['indicateur']) ?></td>
                <td><strong style="color:var(--accent)"><?= $row['valeur'] ?></strong></td>
                <td style="color:var(--text-muted)"><?= htmlspecialchars($row['unite'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include 'pagination.php'; ?>
    <?php else: ?>
    <div class="alert alert-empty">Aucune mesure trouvée pour ces critères.</div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
</body></html>
<?php
header("Content-Type: text/html; charset=UTF-8");
} else {
    // Liste des pays
    $search = $_GET['q'] ?? '';
    $region = $_GET['region'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $par_page = 20;

    $regions_list = $pdo->query("SELECT id_region, nom_region FROM REGION_OMS ORDER BY nom_region")->fetchAll();

    $where = ["1=1"];
    $params = [];
    if ($search) { $where[] = "p.nom LIKE ?"; $params[] = "%$search%"; }
    if ($region)  { $where[] = "p.id_region = ?"; $params[] = $region; }
    $whereSQL = implode(' AND ', $where);

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM PAYS p WHERE $whereSQL");
    $cnt->execute($params);
    $total = $cnt->fetchColumn();
    $nb_pages = max(1, ceil($total / $par_page));
    $offset = ($page - 1) * $par_page;

    $stmt = $pdo->prepare("
        SELECT p.nom, p.code_iso, p.continent, r.nom_region,
               COUNT(m.id_mesure) AS nb_mesures
        FROM PAYS p
        JOIN REGION_OMS r ON r.id_region = p.id_region
        LEFT JOIN MESURE m ON m.id_pays = p.id_pays
        WHERE $whereSQL
        GROUP BY p.id_pays, p.nom, p.code_iso, p.continent, r.nom_region
        ORDER BY p.nom
        LIMIT $par_page OFFSET $offset
    ");
    $stmt->execute($params);
    $pays_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Pays — Surveillance</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h1>Pays</h1>
        <p><?= $total ?> pays dans la base de données</p>
    </div>
    <div class="form-card">
        <form method="get">
            <div class="form-grid">
                <div class="form-group">
                    <label>Rechercher un pays</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Ex: France, Nigeria…">
                </div>
                <div class="form-group">
                    <label>Région OMS</label>
                    <select name="region">
                        <option value="">Toutes les régions</option>
                        <?php foreach ($regions_list as $r): ?>
                        <option value="<?= $r['id_region'] ?>" <?= $region==$r['id_region']?'selected':'' ?>><?= htmlspecialchars($r['nom_region']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Pays</th><th>ISO</th><th>Continent</th><th>Région OMS</th><th>Mesures</th><th>Détail</th></tr></thead>
            <tbody>
            <?php foreach ($pays_list as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['nom']) ?></strong></td>
                <td style="color:var(--text-muted)"><?= $p['code_iso'] ?></td>
                <td><?= htmlspecialchars($p['continent']) ?></td>
                <td><?= htmlspecialchars($p['nom_region']) ?></td>
                <td><?= number_format($p['nb_mesures']) ?></td>
                <td><a href="pays.php?iso=<?= urlencode($p['nom']) ?>" class="btn btn-secondary btn-sm">Voir</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include 'pagination.php'; ?>
</div>
<?php include 'footer.php'; ?>
</body></html>
<?php } ?>
