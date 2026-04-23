-- R4 — Pays exhaustifs
-- Technique : NOT EXISTS
-- Pays ayant déclaré des données pour TOUTES les maladies
USE surveillance_maladies;

SELECT
    p.nom        AS pays,
    p.code_iso,
    r.nom_region AS region_oms
FROM PAYS p
JOIN REGION_OMS r ON r.id_region = p.id_region
WHERE NOT EXISTS (
    SELECT 1
    FROM MALADIE ma
    WHERE NOT EXISTS (
        SELECT 1
        FROM MESURE m
        WHERE m.id_pays    = p.id_pays
          AND m.id_maladie = ma.id_maladie
    )
)
ORDER BY r.nom_region, p.nom;
