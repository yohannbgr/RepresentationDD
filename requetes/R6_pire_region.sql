-- R6 — Pire région (disparité max - min)
-- Technique : Jointure + MAX sur sous-requête
-- Région OMS avec la plus forte disparité d'incidence entre ses pays
USE surveillance_maladies;

SET @maladie_nom = 'Tuberculose';

SELECT
    r.nom_region                                          AS region_oms,
    ROUND(MAX(agg.val_moy), 2)                            AS valeur_max_pays,
    ROUND(MIN(agg.val_moy), 2)                            AS valeur_min_pays,
    ROUND(MAX(agg.val_moy) - MIN(agg.val_moy), 2)        AS disparite
FROM (
    SELECT
        p.id_region,
        p.id_pays,
        AVG(m.valeur) AS val_moy
    FROM MESURE m
    JOIN PAYS    p  ON p.id_pays     = m.id_pays
    JOIN MALADIE ma ON ma.id_maladie = m.id_maladie
    WHERE ma.nom = @maladie_nom
    GROUP BY p.id_region, p.id_pays
) agg
JOIN REGION_OMS r ON r.id_region = agg.id_region
GROUP BY r.id_region, r.nom_region
ORDER BY disparite DESC
LIMIT 1;
