-- BONUS — Indice composite de vulnérabilité sanitaire
-- Technique : Agrégation pondérée multi-indicateurs
-- TB (40%) + VIH (35%) + manque de médecins (25%)
-- Score 0-100 : plus élevé = plus vulnérable
USE surveillance_maladies;

SELECT
    p.nom                                  AS pays,
    r.nom_region                           AS region_oms,
    ROUND(
        COALESCE(tb.score_tb,   0) * 0.40
      + COALESCE(hiv.score_hiv, 0) * 0.35
      + COALESCE(doc.score_doc, 0) * 0.25
    , 2)                                   AS indice_vulnerabilite,
    ROUND(COALESCE(tb.val,  0), 2)         AS incidence_tb,
    ROUND(COALESCE(hiv.val, 0), 2)         AS prevalence_vih,
    ROUND(COALESCE(doc.val, 0), 2)         AS medecins_p10000
FROM PAYS p
JOIN REGION_OMS r ON r.id_region = p.id_region
LEFT JOIN (
    SELECT id_pays, AVG(valeur) AS val,
           100 * AVG(valeur) / NULLIF((
               SELECT MAX(s.valeur) FROM MESURE s
               JOIN INDICATEUR i ON i.id_indicateur = s.id_indicateur
               WHERE i.code_indicateur = 'MDG_0000000020'), 0) AS score_tb
    FROM MESURE m JOIN INDICATEUR i ON i.id_indicateur = m.id_indicateur
    WHERE i.code_indicateur = 'MDG_0000000020' AND m.annee >= 2019
    GROUP BY id_pays
) tb ON tb.id_pays = p.id_pays
LEFT JOIN (
    SELECT id_pays, AVG(valeur) AS val,
           100 * AVG(valeur) / NULLIF((
               SELECT MAX(s.valeur) FROM MESURE s
               JOIN INDICATEUR i ON i.id_indicateur = s.id_indicateur
               WHERE i.code_indicateur = 'MDG_0000000029'), 0) AS score_hiv
    FROM MESURE m JOIN INDICATEUR i ON i.id_indicateur = m.id_indicateur
    WHERE i.code_indicateur = 'MDG_0000000029' AND m.annee >= 2019
    GROUP BY id_pays
) hiv ON hiv.id_pays = p.id_pays
LEFT JOIN (
    SELECT id_pays, AVG(valeur) AS val,
           100 * (1 - AVG(valeur) / NULLIF((
               SELECT MAX(s.valeur) FROM MESURE s
               JOIN INDICATEUR i ON i.id_indicateur = s.id_indicateur
               WHERE i.code_indicateur = 'HWF_0001'), 0)) AS score_doc
    FROM MESURE m JOIN INDICATEUR i ON i.id_indicateur = m.id_indicateur
    WHERE i.code_indicateur = 'HWF_0001' AND m.annee >= 2019
    GROUP BY id_pays
) doc ON doc.id_pays = p.id_pays
WHERE COALESCE(tb.val, 0) + COALESCE(hiv.val, 0) + COALESCE(doc.val, 0) > 0
ORDER BY indice_vulnerabilite DESC
LIMIT 30;
