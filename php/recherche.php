<?php
header("Content-Type: text/html; charset=UTF-8");
require_once 'connexion.php';
$pdo = getConnexion();

$regions  = $pdo->query("SELECT id_region, nom_region FROM REGION_OMS ORDER BY nom_region")->fetchAll();
$maladies = $pdo->query("SELECT id_maladie, nom FROM MALADIE ORDER BY nom")->fetchAll();
$indicateurs = $pdo->query("SELECT id_indicateur, libelle, code_indicateur FROM INDICATEUR ORDER BY libelle")->fetchAll();

$filtre_region  = $_GET['region']    ?? '';
$filtre_maladie = $_GET['maladie']   ?? '';
$filtre_ind     = $_GET['indicateur'] ?? '';
$filtre_an1     = $_GET['annee1']    ?? '';
$filtre_an2     = $_GET['annee2']    ?? '';
$filtre_pays    = $_GET['pays']      ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$par_page = 20;

$has_search = $filtre_region || $filtre_maladie || $filtre_ind || $filtre_an1 || $filtre_an2 || $filtre_pays;

$where = ["1=1"];
$params = [];
if ($filtre_region)  { $where[] = "p.id_region = ?";    $params[] = $filtre_region; }
if ($filtre_maladie) { $where[] = "m.id_maladie = ?";   $params[] = $filtre_maladie; }
if ($filtre_ind)     { $where[] = "m.id_indicateur = ?";$params[] = $filtre_ind; }
if ($filtre_an1)     { $where[] = "m.annee >= ?";        $params[] = $filtre_an1; }
if ($filtre_an2)     { $where[] = "m.annee <= ?";        $params[] = $filtre_an2; }
if ($filtre_pays)    { $where[] = "p.nom LIKE ?";        $params[] = "%$filtre_pays%"; }
$whereSQL = implode(' AND ', $where);

$total = 0; $resultats = []; $nb_pages = 1;
if ($has_search) {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM MESURE m JOIN PAYS p ON p.id_pays = m.id_pays WHERE $whereSQL");
    $cnt->execute($params);
    $total = $cnt->fetchColumn();
    $nb_pages = max(1, ceil($total / $par_page));
    $offset = ($page - 1) * $par_page;

    $stmt = $pdo->prepare("
        SELECT p.nom AS pays, p.code_iso, r.nom_region, ma.nom AS maladie,
               i.libelle AS indicateur, i.unite, m.annee, ROUND(m.valeur,2) AS valeur
        FROM MESURE m
        JOIN PAYS       p  ON p.id_pays       = m.id_pays
        JOIN REGION_OMS r  ON r.id_region     = p.id_region
        JOIN MALADIE    ma ON ma.id_maladie   = m.id_maladie
        JOIN INDICATEUR i  ON i.id_indicateur = m.id_indicateur
        WHERE $whereSQL
        ORDER BY m.annee DESC, p.nom
        LIMIT $par_page OFFSET $offset
    ");
    $stmt->execute($params);
    $resultats = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Recherche avancée</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h1>Recherche avancée</h1>
        <p>Filtrez les mesures par région, maladie, indicateur et période</p>
    </div>
    <div class="form-card">
        <form method="get">
            <div class="form-grid">
                <div class="form-group">
                    <label>Pays</label>
                    <input type="text" name="pays" value="<?= htmlspecialchars($filtre_pays) ?>" placeholder="Nom du pays…">
                </div>
                <div class="form-group">
                    <label>Région OMS</label>
                    <select name="region">
                        <option value="">Toutes</option>
                        <?php foreach ($regions as $r): ?>
                        <option value="<?= $r['id_region'] ?>" <?= $filtre_region==$r['id_region']?'selected':'' ?>><?= htmlspecialchars($r['nom_region']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Maladie</label>
                    <select name="maladie">
                        <option value="">Toutes</option>
                        <?php foreach ($maladies as $m): ?>
                        <option value="<?= $m['id_maladie'] ?>" <?= $filtre_maladie==$m['id_maladie']?'selected':'' ?>><?= htmlspecialchars($m['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Indicateur</label>
                    <select name="indicateur">
                        <option value="">Tous</option>
                        <?php foreach ($indicateurs as $i): ?>
                        <option value="<?= $i['id_indicateur'] ?>" <?= $filtre_ind==$i['id_indicateur']?'selected':'' ?>><?= htmlspecialchars($i['libelle']) ?></option>
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
            </div>
            <div style="margin-top:1rem;display:flex;gap:0.75rem;">
                <button type="submit" class="btn btn-primary">🔍 Rechercher</button>
                <a href="recherche.php" class="btn btn-secondary">Réinitialiser</a>
            </div>
        </form>
    </div>

    <?php if ($has_search): ?>
        <?php if ($resultats): ?>
        <h2 class="section-title"><?= number_format($total) ?> résultat<?= $total>1?'s':'' ?> trouvé<?= $total>1?'s':'' ?></h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Pays</th><th>ISO</th><th>Région</th><th>Maladie</th><th>Indicateur</th><th>Année</th><th>Valeur</th><th>Unité</th></tr></thead>
                <tbody>
                <?php foreach ($resultats as $row): ?>
                <tr>
                    <td><a href="pays.php?iso=<?= urlencode($row['pays']) ?>" style="color:var(--accent);text-decoration:none"><?= htmlspecialchars($row['pays']) ?></a></td>
                    <td style="color:var(--text-muted)"><?= $row['code_iso'] ?></td>
                    <td><?= htmlspecialchars($row['nom_region']) ?></td>
                    <td><?= htmlspecialchars($row['maladie']) ?></td>
                    <td><?= htmlspecialchars($row['indicateur']) ?></td>
                    <td><strong><?= $row['annee'] ?></strong></td>
                    <td style="color:var(--accent)"><strong><?= $row['valeur'] ?></strong></td>
                    <td style="color:var(--text-muted)"><?= htmlspecialchars($row['unite'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php include 'pagination.php'; ?>
        <?php else: ?>
        <div class="alert alert-empty">Aucun résultat pour ces critères. Élargissez vos filtres.</div>
        <?php endif; ?>
    <?php else: ?>
    <div class="alert alert-info">Sélectionnez au moins un filtre pour lancer la recherche.</div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
</body></html>
