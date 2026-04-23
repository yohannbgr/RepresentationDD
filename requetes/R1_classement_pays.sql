-- R1 — Classement des pays
-- Technique : Agrégation + ORDER BY
-- Top 10 pays par taux d'incidence moyen (5 dernières années)
USE surveillance_maladies;

SET @maladie_nom = 'Tuberculose';

SELECT
    p.nom                        AS pays,
    r.nom_region                 AS region_oms,
    ROUND(AVG(m.valeur), 2)      AS incidence_moyenne,
    COUNT(m.id_mesure)           AS nb_mesures
FROM MESURE m
JOIN PAYS       p  ON p.id_pays       = m.id_pays
JOIN REGION_OMS r  ON r.id_region     = p.id_region
JOIN MALADIE    ma ON ma.id_maladie   = m.id_maladie
JOIN INDICATEUR i  ON i.id_indicateur = m.id_indicateur
WHERE ma.nom = @maladie_nom
  AND i.code_indicateur = 'MDG_0000000020'
  AND m.annee >= (SELECT MAX(annee) - 4 FROM MESURE)
GROUP BY p.id_pays, p.nom, r.nom_region
ORDER BY incidence_moyenne DESC
LIMIT 10;
