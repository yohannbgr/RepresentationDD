-- R8 — Top indicateur / maladie
-- Technique : Jointures multiples + LIMIT
-- Pour chaque maladie, pays avec la valeur maximale sur toutes les années
-- Optimisé avec GROUP BY + sous-requête agrégée (évite la corrélation ligne par ligne)
USE surveillance_maladies;

SELECT
    ma.nom                   AS maladie,
    i.libelle                AS indicateur,
    p.nom                    AS pays_record,
    r.nom_region             AS region_oms,
    ROUND(agg.val_max, 2)    AS valeur_max,
    m.annee                  AS annee_record
FROM (
    SELECT id_maladie, id_indicateur, MAX(valeur) AS val_max
    FROM MESURE
    GROUP BY id_maladie, id_indicateur
) agg
JOIN MESURE     m  ON m.id_maladie    = agg.id_maladie
                  AND m.id_indicateur = agg.id_indicateur
                  AND m.valeur        = agg.val_max
JOIN PAYS       p  ON p.id_pays       = m.id_pays
JOIN REGION_OMS r  ON r.id_region     = p.id_region
JOIN MALADIE    ma ON ma.id_maladie   = m.id_maladie
JOIN INDICATEUR i  ON i.id_indicateur = m.id_indicateur
GROUP BY ma.id_maladie, ma.nom, i.id_indicateur, i.libelle,
         p.nom, r.nom_region, agg.val_max, m.annee
ORDER BY ma.nom, agg.val_max DESC;
