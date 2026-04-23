<?php
header("Content-Type: text/html; charset=UTF-8");
require_once 'connexion.php';
$pdo = getConnexion();

$requete = $_GET['r'] ?? 'R1';

// Données dynamiques pour les selects
$maladies    = $pdo->query("SELECT id_maladie, nom FROM MALADIE ORDER BY nom")->fetchAll();
$indicateurs = $pdo->query("SELECT id_indicateur, libelle, code_indicateur FROM INDICATEUR ORDER BY libelle")->fetchAll();
$annees      = $pdo->query("SELECT DISTINCT annee FROM MESURE ORDER BY annee DESC")->fetchAll(PDO::FETCH_COLUMN);

// Paramètres utilisateur
$p_maladie = $_GET['maladie'] ?? $maladies[0]['id_maladie'] ?? 1;
$p_ind     = $_GET['indicateur'] ?? $indicateurs[0]['id_indicateur'] ?? 1;
$p_an1     = $_GET['annee1'] ?? 2015;
$p_an2     = $_GET['annee2'] ?? 2024;
$p_annee   = $_GET['annee'] ?? 2023;
$p_n       = max(1, (int)($_GET['n_annees'] ?? 3));

$resultats = [];
$titre = '';
$technique = '';
$colonnes = [];

switch ($requete) {
    case 'R1':
        $titre = 'R1 — Top 10 pays par incidence moyenne';
        $technique = 'Agrégation + ORDER BY';
        $stmt = $pdo->prepare("
            SELECT p.nom AS pays, r.nom_region AS region_oms,
                   ROUND(AVG(m.valeur),2) AS incidence_moyenne, COUNT(m.id_mesure) AS nb_mesures
            FROM MESURE m
            JOIN PAYS p ON p.id_pays = m.id_pays
            JOIN REGION_OMS r ON r.id_region = p.id_region
            JOIN MALADIE ma ON ma.id_maladie = m.id_maladie
            JOIN INDICATEUR i ON i.id_indicateur = m.id_indicateur
            WHERE ma.id_maladie = ? AND m.annee >= (SELECT MAX(annee)-4 FROM MESURE)
            GROUP BY p.id_pays, p.nom, r.nom_region
            ORDER BY incidence_moyenne DESC LIMIT 10
        ");
        $stmt->execute([$p_maladie]);
        $colonnes = ['Pays','Région OMS','Incidence moyenne','Nb mesures'];
        break;

    case 'R2':
        $titre = 'R2 — Évolution régionale';
        $technique = 'Jointure + GROUP BY';
        $stmt = $pdo->prepare("
            SELECT r.nom_region AS region_oms, ma.nom AS maladie,
                   ROUND(SUM(m.valeur),2) AS total_valeur, COUNT(DISTINCT p.id_pays) AS nb_pays
            FROM MESURE m
            JOIN PAYS p ON p.id_pays = m.id_pays
            JOIN REGION_OMS r ON r.id_region = p.id_region
            JOIN MALADIE ma ON ma.id_maladie = m.id_maladie
            WHERE m.annee BETWEEN ? AND ?
            GROUP BY r.id_region, r.nom_region, ma.id_maladie, ma.nom
            ORDER BY r.nom_region, total_valeur DESC
        ");
        $stmt->execute([$p_an1, $p_an2]);
        $colonnes = ['Région OMS','Maladie','Total valeur','Nb pays'];
        break;

    case 'R3':
        $titre = 'R3 — Maladies en progression (>10 pays)';
        $technique = 'Sous-requête + HAVING';
        $stmt = $pdo->query("
            SELECT ma.nom AS maladie, COUNT(DISTINCT m1.id_pays) AS nb_pays_en_hausse
            FROM MESURE m1
            JOIN MESURE m2 ON m2.id_pays=m1.id_pays AND m2.id_maladie=m1.id_maladie
                          AND m2.id_indicateur=m1.id_indicateur AND m2.annee=m1.annee+1
            JOIN MALADIE ma ON ma.id_maladie=m1.id_maladie
            WHERE m1.annee=(SELECT MAX(annee)-1 FROM MESURE) AND m2.valeur>m1.valeur
            GROUP BY ma.id_maladie, ma.nom HAVING nb_pays_en_hausse>10
            ORDER BY nb_pays_en_hausse DESC
        ");
        $colonnes = ['Maladie','Nb pays en hausse'];
        break;

    case 'R4':
        $titre = 'R4 — Pays avec données pour toutes les maladies';
        $technique = 'NOT EXISTS';
        $stmt = $pdo->query("
            SELECT p.nom AS pays, p.code_iso, r.nom_region AS region_oms
            FROM PAYS p JOIN REGION_OMS r ON r.id_region=p.id_region
            WHERE NOT EXISTS (
                SELECT 1 FROM MALADIE ma WHERE NOT EXISTS (
                    SELECT 1 FROM MESURE m WHERE m.id_pays=p.id_pays AND m.id_maladie=ma.id_maladie
                )
            ) ORDER BY r.nom_region, p.nom
        ");
        $colonnes = ['Pays','ISO','Région OMS'];
        break;

    case 'R5':
        $titre = 'R5 — Pays au-dessus de la moyenne mondiale';
        $technique = 'Sous-requête scalaire';
        $stmt = $pdo->prepare("
            SELECT p.nom AS pays, r.nom_region AS region_oms,
                   ROUND(m.valeur,2) AS valeur_pays,
                   ROUND((SELECT AVG(m2.valeur) FROM MESURE m2
                          JOIN INDICATEUR i2 ON i2.id_indicateur=m2.id_indicateur
                          WHERE m2.id_indicateur=? AND m2.annee=?),2) AS moyenne_mondiale
            FROM MESURE m
            JOIN PAYS p ON p.id_pays=m.id_pays
            JOIN REGION_OMS r ON r.id_region=p.id_region
            WHERE m.id_indicateur=? AND m.annee=?
              AND m.valeur>(SELECT AVG(m2.valeur) FROM MESURE m2 WHERE m2.id_indicateur=? AND m2.annee=?)
            ORDER BY m.valeur DESC
        ");
        $stmt->execute([$p_ind, $p_annee, $p_ind, $p_annee, $p_ind, $p_annee]);
        $colonnes = ['Pays','Région OMS','Valeur pays','Moyenne mondiale'];
        break;

    case 'R6':
        $titre = 'R6 — Région avec la plus forte disparité';
        $technique = 'Jointure + MAX sur sous-requête';
        $stmt = $pdo->prepare("
            SELECT r.nom_region AS region_oms,
                   ROUND(MAX(agg.val_moy),2) AS valeur_max_pays,
                   ROUND(MIN(agg.val_moy),2) AS valeur_min_pays,
                   ROUND(MAX(agg.val_moy)-MIN(agg.val_moy),2) AS disparite
            FROM (SELECT p.id_region, p.id_pays, AVG(m.valeur) AS val_moy
                  FROM MESURE m JOIN PAYS p ON p.id_pays=m.id_pays
                  WHERE m.id_maladie=? GROUP BY p.id_region, p.id_pays) agg
            JOIN REGION_OMS r ON r.id_region=agg.id_region
            GROUP BY r.id_region, r.nom_region ORDER BY disparite DESC LIMIT 1
        ");
        $stmt->execute([$p_maladie]);
        $colonnes = ['Région OMS','Max pays','Min pays','Disparité'];
        break;

    case 'R7':
        $titre = 'R7 — Pays sans données depuis N années';
        $technique = 'LEFT JOIN + HAVING';
        $stmt = $pdo->prepare("
            SELECT p.nom AS pays, p.code_iso, r.nom_region AS region_oms,
                   MAX(m.annee) AS derniere_annee
            FROM PAYS p JOIN REGION_OMS r ON r.id_region=p.id_region
            LEFT JOIN MESURE m ON m.id_pays=p.id_pays
            GROUP BY p.id_pays, p.nom, p.code_iso, r.nom_region
            HAVING derniere_annee IS NULL OR derniere_annee < (YEAR(CURDATE()) - ?)
            ORDER BY derniere_annee ASC, p.nom
        ");
        $stmt->execute([$p_n]);
        $colonnes = ['Pays','ISO','Région OMS','Dernière année'];
        break;

    case 'R8':
        $titre = 'R8 — Record mondial par maladie et indicateur';
        $technique = 'Jointures multiples + agrégation';
        $stmt = $pdo->query("
            SELECT ma.nom AS maladie, i.libelle AS indicateur,
                   p.nom AS pays_record, r.nom_region AS region_oms,
                   ROUND(agg.val_max,2) AS valeur_max, m.annee AS annee_record
            FROM (SELECT id_maladie, id_indicateur, MAX(valeur) AS val_max
                  FROM MESURE GROUP BY id_maladie, id_indicateur) agg
            JOIN MESURE m ON m.id_maladie=agg.id_maladie AND m.id_indicateur=agg.id_indicateur AND m.valeur=agg.val_max
            JOIN PAYS p ON p.id_pays=m.id_pays
            JOIN REGION_OMS r ON r.id_region=p.id_region
            JOIN MALADIE ma ON ma.id_maladie=m.id_maladie
            JOIN INDICATEUR i ON i.id_indicateur=m.id_indicateur
            GROUP BY ma.id_maladie,ma.nom,i.id_indicateur,i.libelle,p.nom,r.nom_region,agg.val_max,m.annee
            ORDER BY ma.nom, agg.val_max DESC
        ");
        $colonnes = ['Maladie','Indicateur','Pays record','Région','Valeur max','Année'];
        break;

    case 'BONUS':
        $titre = 'BONUS — Indice composite de vulnérabilité sanitaire';
        $technique = 'Agrégation pondérée multi-indicateurs (TB 40% + VIH 35% + Médecins 25%)';
        $stmt = $pdo->query("
            SELECT p.nom AS pays, r.nom_region AS region_oms,
                   ROUND(COALESCE(tb.score_tb,0)*0.40 + COALESCE(hiv.score_hiv,0)*0.35 + COALESCE(doc.score_doc,0)*0.25,2) AS indice_vulnerabilite,
                   ROUND(COALESCE(tb.val,0),2) AS incidence_tb,
                   ROUND(COALESCE(hiv.val,0),2) AS prevalence_vih,
                   ROUND(COALESCE(doc.val,0),2) AS medecins_p10000
            FROM PAYS p JOIN REGION_OMS r ON r.id_region=p.id_region
            LEFT JOIN (SELECT id_pays, AVG(valeur) AS val,
                100*AVG(valeur)/NULLIF((SELECT MAX(s.valeur) FROM MESURE s JOIN INDICATEUR i ON i.id_indicateur=s.id_indicateur WHERE i.code_indicateur='MDG_0000000020'),0) AS score_tb
                FROM MESURE m JOIN INDICATEUR i ON i.id_indicateur=m.id_indicateur
                WHERE i.code_indicateur='MDG_0000000020' AND m.annee>=2019 GROUP BY id_pays) tb ON tb.id_pays=p.id_pays
            LEFT JOIN (SELECT id_pays, AVG(valeur) AS val,
                100*AVG(valeur)/NULLIF((SELECT MAX(s.valeur) FROM MESURE s JOIN INDICATEUR i ON i.id_indicateur=s.id_indicateur WHERE i.code_indicateur='MDG_0000000029'),0) AS score_hiv
                FROM MESURE m JOIN INDICATEUR i ON i.id_indicateur=m.id_indicateur
                WHERE i.code_indicateur='MDG_0000000029' AND m.annee>=2019 GROUP BY id_pays) hiv ON hiv.id_pays=p.id_pays
            LEFT JOIN (SELECT id_pays, AVG(valeur) AS val,
                100*(1-AVG(valeur)/NULLIF((SELECT MAX(s.valeur) FROM MESURE s JOIN INDICATEUR i ON i.id_indicateur=s.id_indicateur WHERE i.code_indicateur='HWF_0001'),0)) AS score_doc
                FROM MESURE m JOIN INDICATEUR i ON i.id_indicateur=m.id_indicateur
                WHERE i.code_indicateur='HWF_0001' AND m.annee>=2019 GROUP BY id_pays) doc ON doc.id_pays=p.id_pays
            WHERE COALESCE(tb.val,0)+COALESCE(hiv.val,0)+COALESCE(doc.val,0)>0
            ORDER BY indice_vulnerabilite DESC LIMIT 30
        ");
        $colonnes = ['Pays','Région','Indice (0-100)','Incidence TB','Prévalence VIH','Médecins/10K'];
        break;
}

if (isset($stmt)) $resultats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Requêtes SQL — Surveillance</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <div class="page-header">
        <h1>Requêtes SQL</h1>
        <p>Les 8 requêtes obligatoires + bonus — paramètres modifiables</p>
    </div>

    <!-- Tabs navigation -->
    <div class="tabs">
        <?php foreach (['R1','R2','R3','R4','R5','R6','R7','R8','BONUS'] as $r): ?>
        <a href="?r=<?= $r ?>" class="tab <?= $requete==$r?'active':'' ?>"><?= $r ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Info requête -->
    <div class="requete-info">
        <strong><?= htmlspecialchars($titre) ?></strong> &nbsp;·&nbsp; Technique : <?= htmlspecialchars($technique) ?>
    </div>

    <!-- Paramètres selon la requête -->
    <div class="form-card">
        <form method="get">
            <input type="hidden" name="r" value="<?= $requete ?>">
            <div class="form-grid">
            <?php if (in_array($requete, ['R1','R6'])): ?>
                <div class="form-group">
                    <label>Maladie</label>
                    <select name="maladie">
                        <?php foreach ($maladies as $m): ?>
                        <option value="<?= $m['id_maladie'] ?>" <?= $p_maladie==$m['id_maladie']?'selected':'' ?>><?= htmlspecialchars($m['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if (in_array($requete, ['R2'])): ?>
                <div class="form-group">
                    <label>Année début</label>
                    <input type="number" name="annee1" min="1990" max="2026" value="<?= $p_an1 ?>">
                </div>
                <div class="form-group">
                    <label>Année fin</label>
                    <input type="number" name="annee2" min="1990" max="2026" value="<?= $p_an2 ?>">
                </div>
            <?php endif; ?>
            <?php if ($requete === 'R5'): ?>
                <div class="form-group">
                    <label>Indicateur</label>
                    <select name="indicateur">
                        <?php foreach ($indicateurs as $i): ?>
                        <option value="<?= $i['id_indicateur'] ?>" <?= $p_ind==$i['id_indicateur']?'selected':'' ?>><?= htmlspecialchars($i['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Année</label>
                    <select name="annee">
                        <?php foreach ($annees as $a): ?>
                        <option value="<?= $a ?>" <?= $p_annee==$a?'selected':'' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if ($requete === 'R7'): ?>
                <div class="form-group">
                    <label>Depuis N années</label>
                    <input type="number" name="n_annees" min="1" max="30" value="<?= $p_n ?>">
                </div>
            <?php endif; ?>
            <?php if (!in_array($requete, ['R3','R4','R8','BONUS'])): ?>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Exécuter</button>
                </div>
            <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Résultats -->
    <?php if ($resultats): ?>
    <h2 class="section-title"><?= count($resultats) ?> résultat<?= count($resultats)>1?'s':'' ?></h2>
    <div class="table-wrap">
        <table>
            <thead><tr><?php foreach ($colonnes as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($resultats as $i => $row): ?>
            <tr>
                <?php foreach (array_values($row) as $j => $val): ?>
                <td><?php
                    if ($j === 0) echo '<strong>' . htmlspecialchars((string)$val) . '</strong>';
                    elseif (is_numeric($val) && $j > 0) echo '<span style="color:var(--accent)">' . htmlspecialchars((string)$val) . '</span>';
                    else echo htmlspecialchars((string)($val ?? '—'));
                ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-empty">Aucun résultat pour ces paramètres.</div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
</body></html>