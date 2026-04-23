# Projet BDD - Surveillance des Maladies Infectieuses

## Description

Base de données MySQL pour le suivi et l'analyse des maladies infectieuses dans le monde. Ce projet utilise des données du **Global Health Observatory (GHO)** de l'Organisation Mondiale de la Santé (OMS) pour analyser la prévalence et l'incidence de maladies prioritaires.

### Maladies couvertes
- **VIH/SIDA** - Prévalence du VIH
- **Paludisme** - Incidence du paludisme
- **Tuberculose** - Incidence et mortalité (dont parmi les décès liés au VIH)

### Indicateurs de santé publique
- Nombre de médecins pour 10 000 habitants
- Nombre d'infirmiers pour 10 000 habitants

---

## Structure de la Base de Données

### Tables principales

| Table | Description |
|-------|-------------|
| `REGION_OMS` | Régions géographiques de l'OMS |
| `PAYS` | Pays et codes ISO, associés à une région |
| `MALADIE` | Maladies infectieuses suivies |
| `INDICATEUR` | Types d'indicateurs mesurés |
| `SOURCE_DONNEES` | Sources des données |
| `MESURE` | Valeurs mesurées par pays, année, maladie et indicateur |

---

## Installation

### Prérequis
- MySQL

### Exécution du script

```bash
mysql -u root < surveillance_maladies_full.sql
```

### Vérification
```bash
mysql -u root -e "USE surveillance_maladies; SHOW TABLES;"
```

---

## Fichiers de données

- `data_HIV_prevalance_V.csv` - Prévalence du VIH par pays et année
- `data_Malaria_V.csv` - Incidence du paludisme
- `data_tuberculos_among_HIV_deaths_100K.csv` - Tuberculose parmi les décès liés au VIH
- `Tuberculosis_incidence_per_100000.csv` - Incidence de la tuberculose
- `Medical_doctor_per_10000.csv` - Densité médicale
- `Nurses_per_10000.csv` - Densité d'infirmiers

---

## Requêtes disponibles

Les requêtes SQL sont organisées dans le dossier `requetes/` :

| Fichier | Objectif |
|---------|----------|
| `R1_classement_pays.sql` | Classement des pays par charge de maladie |
| `R2_evolution_regionale.sql` | Évolution des maladies par région |
| `R3_maladies_progression.sql` | Progression des maladies dans le temps |
| `R4_pays_exhaustifs.sql` | Pays avec données complètes |
| `R5_comparaison_moyenne.sql` | Comparaison avec moyennes régionales |
| `R6_pire_region.sql` | Régions les plus affectées |
| `R7_pays_sans_donnees.sql` | Pays avec données manquantes |
| `R8_top_indicateur.sql` | Classement par indicateur |
| `BONUS_vulnerabilite.sql` | Analyse de vulnérabilité sanitaire |

---

## Scripts SQL
- `surveillance_maladies_full.sql` - **Script MySQL complet (à utiliser)**


## Licence

Ce projet utilise des données publiques de l'OMS.


