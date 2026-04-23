-- R7 — Pays sans données récentes
-- Technique : LEFT JOIN + HAVING
-- Pays n'ayant aucune mesure depuis N années
USE surveillance_maladies;

SET @n_annees = 3;

SELECT
    p.nom        AS pays,
    p.code_iso,
    r.nom_region AS region_oms,
    MAX(m.annee) AS derniere_annee_disponible
FROM PAYS p
JOIN REGION_OMS r ON r.id_region = p.id_region
LEFT JOIN MESURE m ON m.id_pays = p.id_pays
GROUP BY p.id_pays, p.nom, p.code_iso, r.nom_region
HAVING derniere_annee_disponible IS NULL
    OR derniere_annee_disponible < (YEAR(CURDATE()) - @n_annees)
ORDER BY derniere_annee_disponible ASC, p.nom;
