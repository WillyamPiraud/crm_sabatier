# ERP / CRM – Sabatier Formation

## Présentation

Ce dépôt correspond à l’environnement de préproduction du projet ERP / CRM Sabatier Formation, basé sur Dolibarr.

Cet environnement est destiné au développement, au paramétrage métier et aux tests fonctionnels avant le déploiement sur l’infrastructure finale du client.

---

## Informations générales

- **Projet** : ERP / CRM Sabatier Formation
- **Type** : Environnement de préproduction
- **Auteur** : Willyam PIRAUD

---

## Stack technique

| Élément         | Version         |
| --------------- | --------------- |
| Dolibarr        | 22.0.2          |
| PHP             | 8.2.29          |
| Serveur Web     | Apache          |
| Base de données | MySQL / MariaDB |
| OS              | Linux           |

---

## Lancement de l’environnement

L’environnement est basé sur Docker.

### Démarrage

```bash
docker-compose up -d
```

## Fiche client

La fiche client a été enrichie afin de centraliser les informations essentielles au suivi des clients Sabatier Formation.

Les ajouts permettent notamment de :

- qualifier le type de client,
- identifier facilement l’interlocuteur côté client,
- distinguer les informations de facturation,
- appliquer un barème tarifaire,
- savoir rapidement si un client est actif ou non.

Ces informations complètent les données natives de Dolibarr et facilitent le suivi commercial, administratif et financier au quotidien.
