# Suivi des Carrières (Career Host V2)

> **Système d'Information des Ressources Humaines (SIRH) & Dossier Numérique Unique de l'Agent de la Fonction Publique.**  
> *Déployé pour la modernisation administrative — Référence institutionnelle : Ministère du Commerce et de l'Industrie (République du Tchad).*

---

## Sommaire

- [Présentation générale](#-présentation-générale)
- [Vision & Principes directeurs](#-vision--principes-directeurs)
- [Fonctionnalités clés (Modules)](#-fonctionnalités-clés-modules)
- [Architecture technique](#-architecture-technique)
- [Design System & Identité Visuelle](#-design-system--identité-visuelle)
- [Installation & Démarrage rapide](#-installation--démarrage-rapide)
- [Maturité & Audit qualité](#-maturité--audit-qualité)
- [Sécurité & Multi-Tenancy](#-sécurité--multi-tenancy)
- [Roadmap & Prochaines étapes](#-roadmap--prochaines-étapes)
- [Documentation & Références](#-documentation--références)

---

## Présentation générale

**Suivi des Carrières** (dénomination technique antérieure : *PRIMATECH / Career Host*) est une plateforme SIRH modulaire de gestion intégrée des carrières, des actes légaux et de la vie administrative au sein des structures publiques et étatiques.

L'application permet d'abandonner les processus papier et registres physiques pour doter l'administration d'un **Dossier Numérique Unique** garantissant la légalité, l'étanchéité multi-organisationnelle et la traçabilité complète de l'agent public, du recrutement à la retraite.

---

## Vision & Principes directeurs

1. **Légalité administrative & Traçabilité :** Tout mouvement de carrière ou décision RH est indexé sur un acte juridique officiel (arrêté, décision, note de service).
2. **Cloisonnement Multi-Tenant fail-closed :** Aucune fuite d'information inter-institutions n'est tolérée (verrouillage dès la couche SQL Doctrine et Voters Symfony).
3. **Zéro Donnée Fictive (No Mock) :** Chaque tableau de bord, solde ou statistique reflète l'état exact et auditable des bases de données de production.
4. **Ergonomie "The Modern Chancery" :** Un équilibre rigoureux entre la solennité de l'État (armoiries, typographie Poppins, couleurs institutionnelles) et l'efficacité moderne d'un outil numérique accessible sur desktop et mobile.

---

## Fonctionnalités clés (Modules)

Le système couvre l'ensemble du cycle de vie administratif, structuré en 4 grands domaines :