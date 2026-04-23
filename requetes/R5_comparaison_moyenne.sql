-- R5 — Comparaison à la moyenne mondiale
-- Technique : Sous-requête scalaire
-- Pays dont la valeur d'un indicateur dépasse la moyenne mondiale
USE surveillance_maladies;

SET @code_indicateur = 'MDG_0000000020';
SET @annee           = 2023;

SELECT
    p.nom                    AS pays,
    r.nom_region             AS region_oms,
    ROUND(m.valeur, 2)       AS valeur_pays,
    ROUND(
        (SELECT AVG(m2.valeur)
         FROM MESURE m2
         JOIN INDICATEUR i2 ON i2.id_indicateur = m2.id_indicateur
         WHERE i2.code_indicateur = @code_indicateur
           AND m2.annee = @annee), 2
    )                        AS moyenne_mondiale
FROM MESURE m
JOIN PAYS       p  ON p.id_pays       = m.id_pays
JOIN REGION_OMS r  ON r.id_region     = p.id_region
JOIN INDICATEUR i  ON i.id_indicateur = m.id_indicateur
WHERE i.code_indicateur = @code_indicateur
  AND m.annee = @annee
  AND m.valeur > (
      SELECT AVG(m2.valeur)
      FROM MESURE m2
      JOIN INDICATEUR i2 ON i2.id_indicateur = m2.id_indicateur
      WHERE i2.code_indicateur = @code_indicateur
        AND m2.annee = @annee
  )
ORDER BY m.valeur DESC;
