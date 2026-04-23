-- R3 — Maladies en progression
-- Technique : Sous-requête + HAVING
-- Maladies dont la valeur a augmenté dans plus de 10 pays simultanément
USE surveillance_maladies;

SELECT
    ma.nom                        AS maladie,
    COUNT(DISTINCT m1.id_pays)    AS nb_pays_en_hausse
FROM MESURE m1
JOIN MESURE  m2 ON m2.id_pays       = m1.id_pays
               AND m2.id_maladie    = m1.id_maladie
               AND m2.id_indicateur = m1.id_indicateur
               AND m2.annee         = m1.annee + 1
JOIN MALADIE ma ON ma.id_maladie    = m1.id_maladie
WHERE m1.annee = (SELECT MAX(annee) - 1 FROM MESURE)
  AND m2.valeur > m1.valeur
GROUP BY ma.id_maladie, ma.nom
HAVING nb_pays_en_hausse > 10
ORDER BY nb_pays_en_hausse DESC;
