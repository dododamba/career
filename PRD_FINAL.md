# PRD FINAL

## PRIMATECH / CAREER HOST V2

### Plateforme intégrée de gestion des ressources humaines, des carrières et de la vie administrative

**Version :** 2.0
**Date :** Septembre 2026
**Statut :** PRD cible — base de développement du MVP
**Produit existant :** PRIMATECH / Career Host
**Type :** Application Web SIRH modulaire
**Backend existant :** Symfony 7.x / PHP 8.4 / Doctrine ORM
**Base cible :** PostgreSQL
**Frontend cible :** Twig + Tailwind CSS / composants interactifs légers
**Architecture cible :** Monolithe modulaire évolutif
**Périmètre initial :** SIRH, dossier agent, organisation, actes, congés, absences, missions, affectations, présence et visiteurs

---

# 1. Résumé exécutif

PRIMATECH / Career Host est une plateforme initialement conçue pour centraliser la gestion administrative des employés, les structures organisationnelles, les présences, les congés et les visiteurs.

L'audit du produit existant montre toutefois que le système se trouve encore dans un état de prototype avancé : le modèle Doctrine comporte des incohérences bloquantes, certaines interfaces provoquent des erreurs HTTP 500, le contrôle d'accès ne garantit pas l'isolation des institutions, certaines statistiques utilisent des données fictives et aucun dispositif de tests automatisés n'est présent.

Le produit cible V2 doit donc poursuivre deux objectifs simultanés :

1. **Industrialiser et sécuriser le produit existant ;**
2. **Étendre le périmètre fonctionnel vers un véritable SIRH de gestion de la vie administrative et de la carrière.**

La V2 ne doit donc pas être considérée comme une simple évolution graphique ou fonctionnelle.

Elle constitue une **refonte fonctionnelle et technique progressive du socle existant**.

---

# 2. Vision produit

## 2.1 Vision

PRIMATECH doit devenir une plateforme permettant à une organisation de disposer d'un **dossier numérique unique pour chaque agent**, de gérer son parcours professionnel et administratif, et de dématérialiser les principaux processus RH.

Le système doit permettre de répondre à des questions telles que :

* Qui est cet agent ?
* Dans quelle structure travaille-t-il ?
* Quel est son poste actuel ?
* Quelle est son historique de carrière ?
* Quels actes administratifs lui sont rattachés ?
* Où était-il affecté précédemment ?
* Est-il actuellement présent, absent, en congé ou en mission ?
* Quel est son solde de congés ?
* Qui doit valider sa demande ?
* Quel est l'état de traitement de son dossier ?
* Quels documents justifient sa situation administrative ?
* Qui a modifié ou validé une information ?

---

# 3. Positionnement du produit

PRIMATECH V2 est un **Système d'Information des Ressources Humaines (SIRH)** intégrant progressivement la gestion administrative de la carrière.

Le produit est organisé autour de quatre grands domaines :

### Domaine A — Référentiel RH

* Organisations
* Structures
* Services
* Fonctions
* Postes
* Employés
* Utilisateurs
* Rôles et permissions

### Domaine B — Vie administrative et carrière

* Dossier agent
* Documents RH
* Actes administratifs
* Affectations
* Mouvements
* Prise et cessation de service
* Intérims
* Missions

### Domaine C — Temps et absences

* Pointage
* Présences
* Congés
* Autorisations d'absence
* Soldes
* Calendrier
* Disponibilité des agents

### Domaine D — Pilotage

* Tableaux de bord
* Statistiques
* Rapports
* Notifications
* Audit
* Historique

Le registre visiteurs demeure un module complémentaire du produit.

---

# 4. Problème à résoudre

Le système actuel répond partiellement aux besoins opérationnels mais présente deux catégories de problèmes.

## 4.1 Problèmes techniques

L'audit révèle notamment :

* mapping Doctrine invalide ;
* relations Employe/User/SoldeConge incohérentes ;
* templates manquants ;
* utilisation d'API Symfony supprimées ;
* requêtes Doctrine incorrectes ;
* données mockées retournées en cas d'erreur SQL ;
* absence d'isolation multi-tenant ;
* contrôle d'accès insuffisant ;
* secrets présents dans l'environnement ;
* API JWT non opérationnelle ;
* absence de tests ;
* absence de CI/CD ;
* absence de documentation technique.

Ces éléments doivent être traités avant toute généralisation du produit.

## 4.2 Problèmes fonctionnels

Le système actuel ne permet pas encore de gérer correctement :

* l'historique de carrière ;
* les actes administratifs ;
* les affectations ;
* les mouvements ;
* les postes ;
* les prises et cessations de service ;
* les intérims ;
* les documents RH ;
* les missions ;
* les workflows administratifs complexes.

Le produit cible doit donc passer d'une logique de **gestion CRUD d'employés** à une logique de **gestion du cycle de vie administratif de l'agent**.

---

# 5. Objectifs stratégiques

## Objectif 1 — Construire un référentiel RH fiable

Chaque agent doit posséder une fiche unique et cohérente.

## Objectif 2 — Constituer un dossier numérique

Le dossier doit regrouper :

* identité ;
* situation administrative ;
* affectation ;
* carrière ;
* actes ;
* documents ;
* absences ;
* congés ;
* missions ;
* historique.

## Objectif 3 — Digitaliser les processus

Les principales demandes doivent suivre des workflows :

```text
Demande
   ↓
Avis hiérarchique
   ↓
Validation
   ↓
Décision
   ↓
Notification
   ↓
Archivage
```

## Objectif 4 — Garantir la traçabilité

Chaque opération sensible doit pouvoir répondre à :

```text
QUI ?
QUOI ?
QUAND ?
SUR QUEL OBJET ?
AVANT ?
APRÈS ?
```

## Objectif 5 — Garantir l'isolation des données

Une organisation ne doit jamais pouvoir accéder aux données d'une autre organisation.

## Objectif 6 — Produire des données fiables

Aucune statistique métier ne doit provenir de données mockées.

---

# 6. Utilisateurs et rôles

| Rôle                        | Responsabilités                                       |
| --------------------------- | ----------------------------------------------------- |
| Super Administrateur        | Administration globale de la plateforme               |
| Administrateur organisation | Configuration de son organisation                     |
| DRH / Gestionnaire RH       | Gestion des agents, dossiers et processus RH          |
| Responsable de structure    | Gestion des agents de sa structure                    |
| Chef de service             | Avis et validation de premier niveau                  |
| Agent                       | Consultation de son dossier et soumission de demandes |
| Agent d'accueil             | Gestion des visiteurs                                 |
| Auditeur                    | Consultation des historiques et journaux              |
| Administrateur technique    | Comptes, sécurité, configuration technique            |

Les permissions doivent être déterminées par :

```text
Utilisateur
+
Rôle
+
Organisation
+
Structure
+
Ressource
+
Action
```

---

# 7. Principe d'organisation du produit

Le système doit être conçu selon une logique :

```text
ORGANISATION
    │
    ├── Structures
    │      ├── Directions
    │      ├── Services
    │      └── Unités
    │
    ├── Postes
    │
    └── Agents
           │
           ├── Dossier administratif
           ├── Carrière
           ├── Actes
           ├── Affectations
           ├── Documents
           ├── Congés
           ├── Absences
           ├── Missions
           ├── Présences
           └── Historique
```

---

# 8. Architecture fonctionnelle cible

## 8.1 Socle

### CORE — Administration et sécurité

* Authentification
* Utilisateurs
* Rôles
* Permissions
* Organisations
* Structures
* Services
* Fonctions
* Paramétrage
* Audit

---

## 8.2 RH

### RH — Gestion des agents

* Employés
* Fiches agents
* Statuts
* Situations administratives
* Contacts
* Affectation actuelle
* Poste actuel

### CAREER — Carrière

* Historique des affectations
* Nominations
* Promotions
* Changements de poste
* Changements de structure
* Avancements
* Prises de service
* Cessations de service
* Intérims

---

## 8.3 Documents et actes

### DOCUMENT — Dossier documentaire

Chaque document doit comporter au minimum :

* catégorie ;
* titre ;
* référence ;
* date ;
* agent concerné ;
* type ;
* fichier ;
* version ;
* auteur ;
* date d'ajout ;
* statut ;
* historique.

### ACT — Actes administratifs

Le système doit gérer :

* décret ;
* arrêté ;
* décision ;
* note de service ;
* ordre de mission ;
* titre de congé ;
* autorisation d'absence ;
* PV de prise de service ;
* PV de cessation de service.

Pour chaque acte :

```text
Type
Numéro
Date de signature
Date d'effet
Objet
Signataire
Statut
Document
Agents concernés
Postes concernés
Acte parent
Acte modificatif
Historique
```

Les décrets et actes émis hors application doivent pouvoir être **enregistrés et archivés**, sans que le système prétende les produire juridiquement.

---

# 9. Gestion des affectations

Une affectation représente un changement administratif significatif.

Elle doit permettre de gérer :

```text
Agent
   ↓
Poste actuel
   ↓
Proposition de mouvement
   ↓
Validation
   ↓
Acte administratif
   ↓
Cessation
   ↓
Prise de service
   ↓
Nouveau poste
```

Le système doit conserver l'historique complet.

Une affectation passée ne doit jamais être simplement écrasée.

---

# 10. Gestion des postes

Le poste devient une entité métier indépendante de l'agent.

Un poste contient :

* référence ;
* intitulé ;
* structure ;
* service ;
* fonction ;
* niveau ;
* statut ;
* date de création ;
* date de fermeture éventuelle ;
* occupant actuel.

Statuts :

```text
VACANT
OCCUPE
SUSPENDU
FERME
```

Cela permet notamment de répondre à :

> Quels sont les postes vacants dans une structure ?

et :

> Qui occupait ce poste avant l'agent actuel ?

---

# 11. Gestion des congés

Le module existant doit être profondément refondu.

## Workflow cible

```text
Agent
 ↓
Demande
 ↓
Contrôle automatique
 ├── Solde
 ├── Chevauchement
 ├── Période
 └── Justificatif
 ↓
Chef de service
 ↓
Responsable
 ↓
RH
 ↓
Validation
 ↓
Titre de congé
 ↓
Notification
 ↓
Départ
 ↓
Reprise
```

## Règles

Le système doit :

* calculer le nombre de jours ;
* appliquer les règles configurées ;
* vérifier le solde ;
* empêcher les chevauchements ;
* enregistrer les validations ;
* débiter le solde lors de la validation selon la règle métier retenue ;
* gérer l'annulation ;
* gérer la prolongation ;
* gérer la reprise ;
* conserver l'historique.

Les règles de calcul doivent rester paramétrables et ne pas être figées dans le code tant que les règles réglementaires n'ont pas été validées.

---

# 12. Autorisations d'absence

Le module doit gérer :

* demande ;
* motif ;
* date ;
* heure ;
* justificatif ;
* circuit de validation ;
* décision ;
* historique.

Les types d'absence doivent être paramétrables.

---

# 13. Missions

Le module Mission doit gérer :

```text
Création
 ↓
Agents concernés
 ↓
Destination
 ↓
Itinéraire
 ↓
Dates
 ↓
Budget éventuel
 ↓
Validation
 ↓
Ordre de mission
 ↓
Exécution
 ↓
Rapport
 ↓
Validation
 ↓
Clôture
```

Une mission doit être historisée dans le dossier de chaque agent.

---

# 14. Intérim et continuité de service

Le système doit permettre de désigner temporairement un remplaçant.

Une délégation doit comporter :

* titulaire ;
* intérimaire ;
* fonction ;
* date début ;
* date fin ;
* motif ;
* périmètre des pouvoirs ;
* autorité ayant validé.

Les droits temporaires doivent automatiquement expirer.

---

# 15. Présence et pointage

Le pointage actuel est conservé mais refondu.

## MVP

Le système doit au minimum permettre :

* arrivée ;
* départ ;
* statut ;
* date ;
* heure ;
* employé ;
* structure ;
* historique.

## Évolution

Les mécanismes suivants pourront être ajoutés :

* QR dynamique ;
* borne ;
* géolocalisation ;
* badge ;
* application mobile.

Le QR/géofencing ne doit pas être un prérequis du premier MVP si le besoin métier n'est pas confirmé.

---

# 16. Gestion des visiteurs

Le module visiteurs est conservé comme module complémentaire.

Fonctions :

* enregistrement ;
* hôte ;
* motif ;
* pièce d'identité ;
* entrée ;
* sortie ;
* statut.

Évolution :

* pré-enregistrement ;
* notification de l'hôte ;
* badge ;
* QR ;
* historique.

---

# 17. Tableau de bord

Le dashboard doit être basé exclusivement sur des données réelles.

## Dashboard global

* nombre d'agents ;
* agents actifs ;
* absents ;
* en congé ;
* en mission ;
* postes vacants ;
* demandes en attente ;
* actes en attente ;
* documents incomplets.

## Dashboard RH

* effectifs par structure ;
* effectifs par statut ;
* congés ;
* absences ;
* ancienneté ;
* mouvements ;
* dossiers incomplets.

## Dashboard manager

* équipe ;
* présents ;
* absents ;
* demandes à valider ;
* intérims ;
* missions.

---

# 18. Notifications

Le système doit disposer d'un mécanisme central de notification.

Canaux :

* notification interne ;
* email ;
* SMS en évolution.

Événements :

* demande créée ;
* demande approuvée ;
* demande rejetée ;
* acte signé ;
* mission créée ;
* mission clôturée ;
* document manquant ;
* validation en attente ;
* expiration d'une délégation.

---

# 19. Audit

Le journal d'audit devient un véritable module.

Chaque événement sensible doit enregistrer :

```text
Utilisateur
Organisation
Action
Objet
Identifiant objet
Date/heure
Adresse IP
User-Agent
Ancienne valeur
Nouvelle valeur
Résultat
```

Le faux template d'historique existant doit être supprimé et remplacé par ce mécanisme réel.

---

# 20. Multi-tenancy

Le produit doit être conçu pour isoler strictement les organisations.

Chaque donnée métier concernée doit être rattachée à une organisation/tenant.

Principe :

```text
TENANT
 │
 ├── Users
 ├── Structures
 ├── Employés
 ├── Postes
 ├── Documents
 ├── Congés
 ├── Missions
 ├── Visiteurs
 └── Audit
```

Aucune requête métier ne doit permettre de traverser cette frontière.

L'isolation doit être garantie à plusieurs niveaux :

1. filtre/restriction Doctrine ;
2. repositories ;
3. services métier ;
4. Security Voters ;
5. contrôleurs ;
6. tests automatisés d'isolation.

---

# 21. Modèle de données cible

Le modèle doit évoluer de l'actuel modèle centré sur `Employe` vers un modèle centré sur le cycle de vie de l'agent.

Entités principales :

```text
Tenant
User
Role
Permission

Institution / Organisation
Structure
Service
Fonction
Poste

Employe
SituationAdministrative
OccupationPoste
Affectation
Interim

Acte
TypeActe
Document
DocumentVersion

Conge
TypeConge
SoldeConge
CongeHistorique

Absence
TypeAbsence

Mission
MissionAgent
RapportMission

Pointage
Presence

Visite

Notification
AuditLog
```

Les relations existantes doivent être nettoyées plutôt que conservées artificiellement.

---

# 22. Architecture technique cible

Le produit reste un monolithe Symfony mais devient un **monolithe modulaire**.

Organisation cible :

```text
src/
├── Core/
│   ├── Security/
│   ├── Tenant/
│   ├── Audit/
│   └── Notification/
│
├── Organisation/
│   ├── Entity/
│   ├── Repository/
│   ├── Service/
│   └── Controller/
│
├── RH/
│   ├── Employe/
│   ├── Carriere/
│   ├── Poste/
│   └── Document/
│
├── Conges/
├── Absences/
├── Missions/
├── Affectations/
├── Pointage/
├── Visiteurs/
└── Dashboard/
```

Les contrôleurs ne doivent plus contenir la logique métier complexe.

Principe :

```text
Controller
    ↓
Application Service
    ↓
Domain Logic
    ↓
Repository
    ↓
Doctrine
```

---

# 23. Sécurité

Le système doit obligatoirement disposer de :

* authentification sécurisée ;
* mots de passe forts ;
* protection CSRF ;
* rate limiting ;
* RBAC ;
* Security Voters ;
* isolation tenant ;
* gestion des sessions ;
* expiration des comptes ;
* audit ;
* secrets hors repository ;
* stockage sécurisé des documents ;
* clés JWT correctement gérées si l'API est activée.

Les secrets actuellement présents dans l'environnement doivent être régénérés et sortis du dépôt. L'API JWT actuelle ne peut pas être considérée comme opérationnelle tant que ses clés cryptographiques ne sont pas correctement configurées.

---

# 24. Documents

Les fichiers RH doivent être stockés avec :

* nom logique ;
* nom physique sécurisé ;
* MIME type ;
* taille ;
* hash ;
* version ;
* auteur ;
* date ;
* catégorie ;
* niveau de confidentialité ;
* relation métier.

Les documents ne doivent pas être accessibles directement par URL publique.

---

# 25. UX/UI

Le produit doit adopter une interface :

* professionnelle ;
* claire ;
* responsive ;
* mobile-friendly ;
* accessible ;
* sans dépendance CDN critique ;
* avec états loading / empty / error / success.

Le système actuel mélange AssetMapper, Webpack Encore et plusieurs CDN externes ; la cible doit retenir une seule stratégie d'assets et supprimer les dépendances inutiles.

---

# 26. Performance

Les listes volumineuses doivent utiliser :

* pagination ;
* filtres ;
* recherche ;
* tri ;
* requêtes optimisées ;
* jointures maîtrisées ;
* index SQL.

Les `findAll()` sur des tables susceptibles de grossir doivent être supprimés.

Les requêtes N+1 doivent être identifiées et corrigées.

---

# 27. Qualité logicielle

Le projet doit introduire :

* PHPUnit/Pest ;
* tests unitaires ;
* tests fonctionnels ;
* tests d'intégration ;
* tests de sécurité ;
* tests multi-tenant ;
* tests des workflows ;
* PHPStan/Psalm ;
* PHP-CS-Fixer ;
* Rector si nécessaire ;
* CI/CD.

Aucun module ne doit être considéré comme terminé sans tests associés.

---

# 28. API

L'API doit être conçue comme une capacité transverse.

Elle devra prévoir :

* authentification ;
* versionnement ;
* permissions ;
* rate limiting ;
* documentation OpenAPI ;
* logs ;
* contrôle tenant ;
* réponses normalisées.

L'API existante devra être auditée et corrigée plutôt que simplement conservée.

---

# 29. Exigences non fonctionnelles

| Domaine         | Exigence                                    |
| --------------- | ------------------------------------------- |
| Sécurité        | OWASP, RBAC, CSRF, Voters, isolation tenant |
| Performance     | Pagination et requêtes optimisées           |
| Disponibilité   | Sauvegardes et procédure de restauration    |
| Audit           | Toutes les actions sensibles tracées        |
| Documents       | Stockage sécurisé et versionné              |
| UX              | Responsive et accessible                    |
| API             | OpenAPI + versionnement                     |
| Tests           | Couverture des règles métier critiques      |
| CI/CD           | Tests automatiques à chaque changement      |
| Évolutivité     | Architecture modulaire                      |
| Données         | Aucune statistique mockée                   |
| Confidentialité | Accès au strict nécessaire                  |

---

# 30. MVP — Définition du produit minimal exploitable

Le MVP n'est pas simplement :

> « faire fonctionner les pages existantes ».

Le MVP doit être un produit techniquement fiable permettant de gérer le cycle administratif essentiel d'un agent.

Il doit couvrir :

### Socle

* Authentification
* Utilisateurs
* Rôles
* Permissions
* Organisation
* Structures
* Services
* Audit
* Multi-tenancy

### RH

* Agents
* Fiche agent
* Postes
* Affectation actuelle
* Historique minimal

### Documents

* Dossier numérique
* Upload
* Catégorisation
* Consultation
* Historique

### Actes

* Enregistrement
* Référence
* Date
* Date d'effet
* Signataire
* Document
* Rattachement à l'agent

### Congés

* Types
* Soldes
* Demandes
* Validation
* Rejet
* Historique
* Notification

### Absences

* Demandes
* Validation
* Historique

### Pilotage

* Dashboard
* Effectifs
* Présences
* Absences
* Congés
* demandes en attente

---

# 31. Modules à développer ou améliorer pour atteindre le MVP

## MODULE 00 — Stabilisation technique

**Type : REFONTE OBLIGATOIRE**

À réaliser avant toute nouvelle fonctionnalité.

### Actions

* Corriger les mappings Doctrine ;
* corriger `Employe ↔ User` ;
* corriger `Employe ↔ Institution` ;
* corriger `Employe ↔ SoldeConge` ;
* régénérer les migrations ;
* valider le schéma ;
* corriger les erreurs Symfony 6/7 ;
* supprimer `getDoctrine()` ;
* corriger les FormTypes ;
* supprimer les mocks ;
* supprimer le code mort ;
* corriger les templates manquants.

**Critère de sortie :**

```text
doctrine:schema:validate = PASS
```

et aucune route critique ne doit provoquer de 500.

---

## MODULE 01 — IAM / Authentification

**Type : AMÉLIORER**

* Login
* Logout
* Reset password
* changement de mot de passe
* gestion des sessions
* rôles
* permissions
* rate limiting
* MFA pour profils sensibles

---

## MODULE 02 — Multi-Tenancy

**Type : NOUVEAU / CRITIQUE**

* Tenant
* rattachement des données
* Tenant context
* Doctrine filter
* Voters
* tests d'isolation

Ce module est indispensable compte tenu de la faille actuelle permettant à un utilisateur d'accéder aux données d'autres institutions.

---

## MODULE 03 — Organisation

**Type : AMÉLIORER**

* Institutions/organisations
* structures
* services
* fonctions
* organigramme
* historique des changements

---

## MODULE 04 — Gestion des agents

**Type : AMÉLIORER**

La fiche actuelle doit devenir une véritable fiche agent 360° :

```text
Identité
Situation administrative
Poste
Affectation
Carrière
Documents
Congés
Absences
Missions
Présences
Historique
```

---

## MODULE 05 — Gestion des postes

**Type : NOUVEAU**

* catalogue des postes ;
* postes occupés ;
* postes vacants ;
* historique d'occupation ;
* rattachement structure/service/fonction.

---

## MODULE 06 — Carrière & affectations

**Type : NOUVEAU**

* affectation ;
* mutation ;
* changement de poste ;
* prise de service ;
* cessation ;
* historique ;
* actes associés.

---

## MODULE 07 — Documents RH

**Type : NOUVEAU**

* coffre documentaire ;
* catégories ;
* upload ;
* téléchargement sécurisé ;
* versionnement ;
* métadonnées ;
* association à l'agent ;
* historique.

---

## MODULE 08 — Actes administratifs

**Type : NOUVEAU**

* actes ;
* références ;
* statuts ;
* documents ;
* signataires ;
* dates d'effet ;
* rattachements ;
* historique.

---

## MODULE 09 — Congés

**Type : REFONTE**

Le module actuel doit être repris pour intégrer :

* calcul du solde ;
* contrôle du solde ;
* validation ;
* workflow ;
* historique ;
* débit du solde ;
* reprise ;
* notifications.

Le problème actuel est explicite : l'approbation passe à `VALIDATED` sans décrémenter le solde.

---

## MODULE 10 — Autorisations d'absence

**Type : NOUVEAU**

* types d'absence ;
* demande ;
* justificatifs ;
* validation ;
* décision ;
* historique.

---

## MODULE 11 — Missions

**Type : NOUVEAU / MVP ÉTENDU**

* création ;
* agents ;
* destination ;
* ordre de mission ;
* statut ;
* rapport ;
* clôture.

---

## MODULE 12 — Intérim

**Type : NOUVEAU**

* désignation ;
* période ;
* délégation ;
* droits temporaires ;
* expiration automatique.

---

## MODULE 13 — Pointage & Présence

**Type : AMÉLIORER**

MVP :

* arrivée ;
* départ ;
* statut ;
* historique ;
* dashboard.

Hors MVP initial :

* QR dynamique ;
* géofencing ;
* borne avancée.

---

## MODULE 14 — Visiteurs

**Type : AMÉLIORER**

MVP :

* entrée ;
* sortie ;
* hôte ;
* motif ;
* statut.

Post-MVP :

* notification ;
* badge ;
* pré-enregistrement ;
* QR.

---

## MODULE 15 — Notifications

**Type : NOUVEAU**

* notifications internes ;
* email ;
* rappels ;
* événements métier ;
* centre de notifications.

---

## MODULE 16 — Audit

**Type : REFONTE COMPLÈTE**

Remplacer le faux historique existant par un véritable journal d'audit.

---

## MODULE 17 — Dashboard & Reporting

**Type : REFONTE**

Toutes les données doivent provenir des repositories et agrégations réelles.

Aucun fallback vers des données fictives ne doit être autorisé.

---

## MODULE 18 — API

**Type : AMÉLIORER**

* authentification ;
* permissions ;
* tenant ;
* versionnement ;
* OpenAPI ;
* logs ;
* rate limiting.

---

## MODULE 19 — Qualité & CI/CD

**Type : NOUVEAU**

* tests ;
* analyse statique ;
* lint ;
* sécurité ;
* CI ;
* déploiement contrôlé.

Le projet actuel possède zéro test et aucun pipeline CI/CD.

---

# 32. Ordre de développement recommandé du MVP

```text
PHASE 0
STABILISATION
│
├── Doctrine
├── Symfony
├── Templates
├── Repositories
└── Suppression des mocks
        ↓
PHASE 1
SOCLE SÉCURISÉ
│
├── IAM
├── RBAC
├── Multi-Tenancy
├── Audit
└── Sécurité
        ↓
PHASE 2
RÉFÉRENTIEL RH
│
├── Organisation
├── Structures
├── Services
├── Postes
└── Agents
        ↓
PHASE 3
DOSSIER ADMINISTRATIF
│
├── Documents
├── Actes
├── Carrière
├── Affectations
└── Historique
        ↓
PHASE 4
ABSENCES
│
├── Congés
├── Soldes
├── Autorisations
└── Workflows
        ↓
PHASE 5
PILOTAGE
│
├── Dashboard
├── Reporting
└── Notifications
        ↓
PHASE 6
EXTENSIONS
│
├── Missions
├── Intérims
├── Pointage avancé
└── Visiteurs avancés
```

---

# 33. Critères d'acceptation du MVP

Le MVP sera considéré comme atteint lorsque :

### Socle

* [ ] Le schéma Doctrine est valide.
* [ ] Les migrations sont reproductibles.
* [ ] Aucune donnée mockée n'est utilisée.
* [ ] Les erreurs critiques Symfony sont supprimées.

### Sécurité

* [ ] Un utilisateur ne peut accéder qu'aux données autorisées.
* [ ] L'isolation entre organisations est testée.
* [ ] Les actions sensibles sont protégées.
* [ ] Les secrets ne sont plus présents dans le repository.
* [ ] Les comptes et rôles sont correctement contrôlés.

### RH

* [ ] Un agent possède une fiche complète.
* [ ] Un agent peut être rattaché à une structure et un poste.
* [ ] L'historique de ses affectations est conservé.

### Documents

* [ ] Les pièces RH peuvent être déposées.
* [ ] Elles sont sécurisées.
* [ ] Elles sont rattachées à l'agent.

### Actes

* [ ] Un acte peut être enregistré.
* [ ] Une référence et une date d'effet sont conservées.
* [ ] L'acte peut être rattaché à un agent.
* [ ] Le document original est conservé.

### Congés

* [ ] Un agent peut demander un congé.
* [ ] Le solde est contrôlé.
* [ ] Le workflow est respecté.
* [ ] Le solde est mis à jour.
* [ ] L'historique est conservé.

### Pilotage

* [ ] Le dashboard repose uniquement sur des données réelles.
* [ ] Les statistiques peuvent être vérifiées avec les données sources.

### Qualité

* [ ] Les parcours critiques sont couverts par des tests.
* [ ] La CI exécute les tests automatiquement.
* [ ] Une sauvegarde peut être restaurée.

---

# 34. Hors MVP

Les éléments suivants sont volontairement repoussés :

* MFA généralisé ;
* SMS avancé ;
* pointage biométrique ;
* géofencing ;
* borne de pointage avancée ;
* IA RH ;
* OCR avancé ;
* signature électronique qualifiée ;
* paie ;
* évaluations annuelles ;
* formation ;
* gestion avancée des compétences ;
* interconnexion externe ;
* SSO gouvernemental ;
* application mobile native.

Ils pourront être ajoutés après stabilisation du socle.

---

# 35. Roadmap cible

## Sprint 0 — Stabilisation

**Objectif : rendre le produit techniquement exploitable.**

* Doctrine
* migrations
* Symfony
* templates
* repositories
* suppression mocks
* nettoyage du code

## Sprint 1 — Sécurité

* IAM
* RBAC
* Multi-tenancy
* Voters
* CSRF
* Audit
* secrets
* JWT

## Sprint 2 — Référentiel RH

* organisation
* structures
* services
* postes
* agents

## Sprint 3 — Dossier agent

* documents
* actes
* carrière
* affectations
* historique

## Sprint 4 — Congés & absences

* soldes
* demandes
* workflows
* validation
* notifications

## Sprint 5 — Dashboard & reporting

* indicateurs
* statistiques
* exports
* alertes

## Sprint 6 — Missions / intérims

* missions
* ordres
* rapports
* intérims

## Sprint 7 — Industrialisation

* tests
* CI/CD
* documentation
* monitoring
* sécurité finale

---

# 36. Principe directeur final

La V2 ne doit pas être développée comme une accumulation de nouvelles pages CRUD.

Le produit doit évoluer selon cette logique :

```text
ANCIEN PRIMATECH

CRUD
Employé
Service
Fonction
Congé
Pointage
Visiteur

                ↓

PRIMATECH V2

                    ┌───────────────┐
                    │ ORGANISATION  │
                    └───────┬───────┘
                            │
                    ┌───────▼───────┐
                    │     AGENT     │
                    └───────┬───────┘
                            │
          ┌─────────────────┼─────────────────┐
          │                 │                 │
     ┌────▼────┐       ┌────▼────┐       ┌────▼────┐
     │ CARRIÈRE│       │ DOCUMENT│       │  ACTES  │
     └────┬────┘       └─────────┘       └─────────┘
          │
     ┌────▼─────┐
     │AFFECTATION│
     └────┬─────┘
          │
     ┌────▼──────────────────────────────┐
     │ VIE ADMINISTRATIVE                │
     │ Congés • Absences • Missions      │
     │ Intérim • Présence • Historique   │
     └────────────────┬─────────────────┘
                      │
              ┌───────▼────────┐
              │   PILOTAGE RH  │
              └────────────────┘
```

**Le produit cible n'est donc plus seulement un outil de pointage et de congés : c'est un référentiel numérique du cycle de vie administratif de l'agent, construit progressivement à partir du socle PRIMATECH existant.**

---

# 37. Livrable suivant

À partir de ce PRD, le prochain document de travail doit être le :

## « PLAN DE TRANSFORMATION PRIMATECH → MVP »

Il devra contenir, pour chaque module :

| Module       | État actuel | Action        | Priorité | Sprint | Entités     | Controllers         | Services         | Routes          | UI  | Tests | Critères de fin       |
| ------------ | ----------- | ------------- | -------- | ------ | ----------- | ------------------- | ---------------- | --------------- | --- | ----- | --------------------- |
| IAM          | Partiel     | Refonte       | P0       | S1     | User/Role   | Security            | AuthService      | /login          | Oui | Oui   | Validé                |
| Multi-Tenant | Absent      | Développement | P0       | S1     | Tenant      | —                   | TenantService    | —               | Oui | Oui   | Isolation validée     |
| Employés     | Existant    | Refonte       | P0       | S2     | Employe     | EmployeController   | EmployeService   | /admin/employes | Oui | Oui   | Fiche 360°            |
| Documents    | Absent      | Développement | P0       | S3     | Document    | DocumentController  | DocumentService  | /documents      | Oui | Oui   | Upload sécurisé       |
| Actes        | Absent      | Développement | P0       | S3     | Acte        | ActeController      | ActeService      | /actes          | Oui | Oui   | Workflow opérationnel |
| Congés       | Partiel     | Refonte       | P0       | S4     | Conge/Solde | CongesController    | CongeService     | /conges         | Oui | Oui   | Solde fiable          |
| Dashboard    | Partiel     | Refonte       | P0       | S5     | —           | DashboardController | ReportingService | /dashboard      | Oui | Oui   | 100% données réelles  |

Ce document servira ensuite directement de **backlog technique et fonctionnel pour Claude Code / l'équipe Symfony**, avec une distinction stricte entre **réutiliser, corriger, refactorer, supprimer et développer**.
