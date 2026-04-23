-- R2 — Évolution régionale
-- Technique : Jointure + GROUP BY
-- Somme des valeurs par région OMS et par maladie entre deux années
USE surveillance_maladies;

SET @annee_debut = 2015;
SET @annee_fin   = 2024;

SELECT
    r.nom_region                  AS region_oms,
    ma.nom                        AS maladie,
    ROUND(SUM(m.valeur), 2)       AS total_valeur,
    COUNT(DISTINCT p.id_pays)     AS nb_pays_declares
FROM MESURE m
JOIN PAYS       p  ON p.id_pays     = m.id_pays
JOIN REGION_OMS r  ON r.id_region   = p.id_region
JOIN MALADIE    ma ON ma.id_maladie = m.id_maladie
WHERE m.annee BETWEEN @annee_debut AND @annee_fin
GROUP BY r.id_region, r.nom_region, ma.id_maladie, ma.nom
ORDER BY r.nom_region, total_valeur DESC;
