# Graph Report - src  (2026-11-03)

## Corpus Check
- 165 files · ~80,472 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2226 nodes · 5788 edges · 103 communities (62 shown, 41 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Absence Web Controller
- Acte Form Type
- Employe Domain Entity
- User Domain Entity
- Mission Domain Entity
- Security Access Voters
- TenantInterface Domain Entity
- Interim Domain Entity
- Document Domain Entity
- Acte Domain Entity
- Affectation Domain Entity
- Conges Domain Entity
- Poste Domain Entity
- Absence Domain Entity
- Structure Domain Entity
- SecurityAuditLog Domain Entity
- Multi-Tenancy SQL Filter
- ExpireInterimsCommand Console Tool
- Employe Web Controller
- Service Domain Entity
- AbsenceLog Entity Repository
- AccountSecurity Web Controller
- Institution Web Controller
- TypeAbsence Domain Entity
- Conges Entity Repository
- Form Type Definitions
- DocumentVersion Domain Entity
- Pointage Domain Entity
- Visite Domain Entity
- Absence Form Type
- Dashboard Web Controller
- OrganisationHistory Entity Repository
- Fonction Web Controller
- TypeActe Entity Repository
- MissionParticipant Domain Entity
- OccupationPoste Domain Entity
- SoldeConge Domain Entity
- PasswordResetToken Entity Repository
- OrganisationHistory Entity Repository
- InterimLog Domain Entity
- AbsenceLog Domain Entity
- TenantRequestSubscriber Domain Entity
- MissionLog Domain Entity
- CongesLog Domain Entity
- MissionService Domain Entity
- Service Web Controller
- Mission Entity Repository
- Visite Entity Repository
- Security Access Voters
- CongeService Domain Entity
- InterimService Domain Entity
- Stats Web Controller
- Presence Domain Entity
- DocumentService Domain Entity
- SoldeConge Entity Repository
- Pointage Web Controller
- TypeInstitution Domain Entity
- CrossTenantAccessDeniedException Domain Entity
- Absence Entity Repository
- OccupationPoste Entity Repository
- MissionLog Entity Repository
- Role Domain Entity
- Interim Entity Repository
- Security Access Voters
- AbsenceService Domain Entity
- ActeService Domain Entity
- Security Access Voters
- Security Access Voters
- CarriereService Domain Entity
- User Web Controller
- Acte Entity Repository
- MissionRapport Form Type
- executeInTenantContext() Domain Entity
- Affectation Entity Repository
- Security Access Voters
- Presence Web Controller
- Role Web Controller
- AppFixtures Domain Entity
- SecurityEventSubscriber Domain Entity
- Document Entity Repository
- Security Access Voters
- getDateEmbauche() Operation
- addActesModificatif() Operation
- CongesLog Entity Repository
- PosteAffectation Form Type
- SecurityAuditLog Entity Repository
- Poste Entity Repository
- User Entity Repository
- UserChecker Domain Entity
- Presence Entity Repository
- DocumentVersion Entity Repository
- countPointagesParStatutEtDate() Operation
- Kernel Domain Entity
- getInstitution() Domain Entity
- addPointage() Operation
- setInstitution() Operation
- setInstitution() Operation
- setInstitution() Operation
- findOverlapping() Operation

## God Nodes (most connected - your core abstractions)
1. `Employe` - 262 edges
2. `Institution` - 244 edges
3. `User` - 238 edges
4. `Mission` - 127 edges
5. `Acte` - 118 edges
6. `Document` - 111 edges
7. `Affectation` - 105 edges
8. `Interim` - 102 edges
9. `Absence` - 89 edges
10. `Poste` - 89 edges

## Surprising Connections (you probably didn't know these)
- `getInstitution()` --references--> `Institution`  [EXTRACTED]
  MultiTenancy/TenantAwareInterface.php → Entity/Institution.php
- `executeInTenantContext()` --references--> `Institution`  [EXTRACTED]
  MultiTenancy/TenantContextInterface.php → Entity/Institution.php
- `getCurrentTenant()` --references--> `Institution`  [EXTRACTED]
  MultiTenancy/TenantContextInterface.php → Entity/Institution.php
- `getTenant()` --references--> `Institution`  [EXTRACTED]
  MultiTenancy/TenantContextInterface.php → Entity/Institution.php
- `requireTenant()` --references--> `Institution`  [EXTRACTED]
  MultiTenancy/TenantContextInterface.php → Entity/Institution.php

## Import Cycles
- None detected.

## Communities (103 total, 41 thin omitted)

### Community 0 - "Absence Web Controller"
Cohesion: 0.06
Nodes (13): App\MultiTenancy\TenantContextInterface, AbsenceController, ActeController, AffectationController, CongesController, Response, DocumentController, InterimController (+5 more)

### Community 1 - "Acte Form Type"
Cohesion: 0.05
Nodes (25): ActeType, AffectationType, ChangePasswordType, CongesType, DocumentType, DocumentVersionType, EmployeType, FonctionType (+17 more)

### Community 3 - "User Domain Entity"
Cohesion: 0.04
Nodes (7): DateTimeImmutable, static, User, Scheb\TwoFactorBundle\Model\BackupCodeInterface, Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface, Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface, UrlGeneratorInterface

### Community 4 - "Mission Domain Entity"
Cohesion: 0.05
Nodes (5): Mission, DateTimeImmutable, DateTimeInterface, self, static

### Community 5 - "Security Access Voters"
Cohesion: 0.08
Nodes (12): ActeVoter, AffectationVoter, DocumentVoter, InstitutionVoter, PointageVoter, PosteVoter, ServiceVoter, StructureVoter (+4 more)

### Community 6 - "TenantInterface Domain Entity"
Cohesion: 0.06
Nodes (5): App\MultiTenancy\TenantInterface, Institution, DateTimeImmutable, static, TenantInterface

### Community 7 - "Interim Domain Entity"
Cohesion: 0.06
Nodes (4): Interim, DateTimeImmutable, DateTimeInterface, self

### Community 8 - "Document Domain Entity"
Cohesion: 0.06
Nodes (3): Document, DateTimeInterface, static

### Community 9 - "Acte Domain Entity"
Cohesion: 0.07
Nodes (3): Acte, DateTimeInterface, static

### Community 10 - "Affectation Domain Entity"
Cohesion: 0.08
Nodes (3): Affectation, DateTimeInterface, static

### Community 11 - "Conges Domain Entity"
Cohesion: 0.07
Nodes (4): Conges, DateTimeInterface, self, static

### Community 12 - "Poste Domain Entity"
Cohesion: 0.07
Nodes (3): DateTimeInterface, static, Poste

### Community 13 - "Absence Domain Entity"
Cohesion: 0.08
Nodes (4): Absence, DateTimeImmutable, DateTimeInterface, self

### Community 14 - "Structure Domain Entity"
Cohesion: 0.08
Nodes (4): DateTimeImmutable, self, static, Structure

### Community 15 - "SecurityAuditLog Domain Entity"
Cohesion: 0.10
Nodes (5): DateTimeImmutable, static, SecurityAuditLog, SecurityAuditService, Symfony\Component\HttpFoundation\RequestStack

### Community 16 - "Multi-Tenancy SQL Filter"
Cohesion: 0.15
Nodes (4): App\MultiTenancy\TenantAwareInterface, Doctrine\ORM\Mapping\ClassMetadata, Doctrine\ORM\Query\Filter\SQLFilter, TenantFilter

### Community 17 - "ExpireInterimsCommand Console Tool"
Cohesion: 0.12
Nodes (10): Command, ExpireInterimsCommand, FixEmployeMigrationCommand, FixUserEmployeRelationsCommand, FixUserInstitutionCommand, Doctrine\DBAL\Connection, Symfony\Component\Console\Command\Command, Symfony\Component\Console\Input\InputInterface (+2 more)

### Community 18 - "Employe Web Controller"
Cohesion: 0.08
Nodes (10): EmployeController, Response, OrganigrammeController, Response, SecurityController, ServiceApiController, WebController, Symfony\Bundle\FrameworkBundle\Controller\AbstractController (+2 more)

### Community 19 - "Service Domain Entity"
Cohesion: 0.08
Nodes (3): DateTimeImmutable, static, Service

### Community 20 - "AbsenceLog Entity Repository"
Cohesion: 0.10
Nodes (6): Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository, Doctrine\Persistence\ManagerRegistry, AbsenceLogRepository, FonctionRepository, InterimLogRepository, RoleRepository

### Community 21 - "AccountSecurity Web Controller"
Cohesion: 0.10
Nodes (13): AccountSecurityController, PasswordResetController, Psr\Log\LoggerInterface, WebAuthentificatorAuthenticator, PasswordResetService, Symfony\Component\Mailer\MailerInterface, Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface, Symfony\Component\RateLimiter\RateLimiterFactory (+5 more)

### Community 22 - "Institution Web Controller"
Cohesion: 0.14
Nodes (10): InstitutionController, Response, Response, TypeInstitutionController, Response, VisiteController, Doctrine\ORM\EntityManagerInterface, InstitutionRepository (+2 more)

### Community 23 - "TypeAbsence Domain Entity"
Cohesion: 0.08
Nodes (3): self, static, TypeAbsence

### Community 24 - "Conges Entity Repository"
Cohesion: 0.08
Nodes (3): DateTime, CongesRepository, DateTimeInterface

### Community 25 - "Form Type Definitions"
Cohesion: 0.08
Nodes (3): self, static, TypeConges

### Community 26 - "DocumentVersion Domain Entity"
Cohesion: 0.09
Nodes (3): DocumentVersion, DateTimeInterface, static

### Community 27 - "Pointage Domain Entity"
Cohesion: 0.12
Nodes (3): DateTimeInterface, self, Pointage

### Community 28 - "Visite Domain Entity"
Cohesion: 0.11
Nodes (3): DateTimeInterface, static, Visite

### Community 30 - "Dashboard Web Controller"
Cohesion: 0.08
Nodes (3): DashboardController, DateTime, PointageRepository

### Community 31 - "OrganisationHistory Entity Repository"
Cohesion: 0.11
Nodes (6): OrganisationHistoryRepository, ServiceRepository, StructureRepository, OrganisationService, EntityManagerInterface, Security

### Community 32 - "Fonction Web Controller"
Cohesion: 0.12
Nodes (4): FonctionController, Response, Fonction, static

### Community 33 - "TypeActe Entity Repository"
Cohesion: 0.10
Nodes (3): static, TypeActe, TypeActeRepository

### Community 34 - "MissionParticipant Domain Entity"
Cohesion: 0.11
Nodes (3): MissionParticipant, DateTimeImmutable, self

### Community 35 - "OccupationPoste Domain Entity"
Cohesion: 0.13
Nodes (3): OccupationPoste, DateTimeInterface, static

### Community 36 - "SoldeConge Domain Entity"
Cohesion: 0.12
Nodes (4): DateTimeInterface, self, static, SoldeConge

### Community 37 - "PasswordResetToken Entity Repository"
Cohesion: 0.13
Nodes (4): PasswordResetToken, DateTimeImmutable, static, PasswordResetTokenRepository

### Community 38 - "OrganisationHistory Entity Repository"
Cohesion: 0.13
Nodes (3): OrganisationHistory, DateTimeImmutable, static

### Community 39 - "InterimLog Domain Entity"
Cohesion: 0.12
Nodes (3): InterimLog, DateTimeInterface, self

### Community 40 - "AbsenceLog Domain Entity"
Cohesion: 0.14
Nodes (3): AbsenceLog, DateTimeInterface, self

### Community 41 - "TenantRequestSubscriber Domain Entity"
Cohesion: 0.18
Nodes (4): TenantRequestSubscriber, TenantContext, Symfony\Component\HttpKernel\Event\RequestEvent, TenantContextInterface

### Community 42 - "MissionLog Domain Entity"
Cohesion: 0.15
Nodes (3): MissionLog, DateTimeInterface, self

### Community 43 - "CongesLog Domain Entity"
Cohesion: 0.16
Nodes (3): CongesLog, DateTimeInterface, self

### Community 45 - "Service Web Controller"
Cohesion: 0.19
Nodes (5): App\Service\OrganisationService, Response, ServiceController, Response, StructureController

### Community 46 - "Mission Entity Repository"
Cohesion: 0.16
Nodes (3): MissionRepository, DateTimeInterface, AgentService

### Community 47 - "Visite Entity Repository"
Cohesion: 0.14
Nodes (3): DateTime, DateTimeInterface, VisiteRepository

### Community 52 - "Presence Domain Entity"
Cohesion: 0.23
Nodes (3): DateTimeInterface, static, Presence

### Community 53 - "DocumentService Domain Entity"
Cohesion: 0.27
Nodes (4): DocumentService, DateTimeInterface, Symfony\Component\HttpFoundation\BinaryFileResponse, Symfony\Component\HttpFoundation\File\UploadedFile

### Community 55 - "Pointage Web Controller"
Cohesion: 0.23
Nodes (3): Response, PointageController, Symfony\UX\Chartjs\Builder\ChartBuilderInterface

### Community 57 - "CrossTenantAccessDeniedException Domain Entity"
Cohesion: 0.19
Nodes (7): CrossTenantAccessDeniedException, Throwable, Throwable, TenantMissingException, Throwable, TenantSuspendedException, Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException

### Community 59 - "OccupationPoste Entity Repository"
Cohesion: 0.19
Nodes (3): OccupationPosteRepository, DateTimeInterface, PosteService

### Community 61 - "Role Domain Entity"
Cohesion: 0.27
Nodes (3): DateTimeImmutable, static, Role

### Community 72 - "executeInTenantContext() Domain Entity"
Cohesion: 0.22
Nodes (5): executeInTenantContext(), getCurrentTenant(), getTenant(), requireTenant(), setCurrentTenant()

### Community 77 - "AppFixtures Domain Entity"
Cohesion: 0.36
Nodes (4): AppFixtures, UserFixtures, Doctrine\Bundle\FixturesBundle\Fixture, Doctrine\Persistence\ObjectManager

### Community 78 - "SecurityEventSubscriber Domain Entity"
Cohesion: 0.32
Nodes (4): SecurityEventSubscriber, LogoutEvent, Symfony\Component\EventDispatcher\EventSubscriberInterface, Symfony\Component\Security\Http\Event\LogoutEvent

### Community 88 - "User Entity Repository"
Cohesion: 0.47
Nodes (3): UserRepository, Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface, Symfony\Component\Security\Core\User\PasswordUpgraderInterface

### Community 89 - "UserChecker Domain Entity"
Cohesion: 0.53
Nodes (3): UserChecker, Symfony\Component\Security\Core\User\UserCheckerInterface, Symfony\Component\Security\Core\User\UserInterface

### Community 94 - "Kernel Domain Entity"
Cohesion: 0.67
Nodes (3): Kernel, Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait, Symfony\Component\HttpKernel\Kernel

### Community 95 - "getInstitution() Domain Entity"
Cohesion: 0.50
Nodes (3): getInstitution(), static, setInstitution()

## Knowledge Gaps
- **41 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Institution` connect `TenantInterface Domain Entity` to `Employe Domain Entity`, `User Domain Entity`, `Mission Domain Entity`, `Security Access Voters`, `Interim Domain Entity`, `Document Domain Entity`, `Acte Domain Entity`, `Affectation Domain Entity`, `Conges Domain Entity`, `Poste Domain Entity`, `Absence Domain Entity`, `Structure Domain Entity`, `SecurityAuditLog Domain Entity`, `Multi-Tenancy SQL Filter`, `ExpireInterimsCommand Console Tool`, `Service Domain Entity`, `AbsenceLog Entity Repository`, `Institution Web Controller`, `TypeAbsence Domain Entity`, `Conges Entity Repository`, `Form Type Definitions`, `DocumentVersion Domain Entity`, `Pointage Domain Entity`, `Visite Domain Entity`, `Absence Form Type`, `Dashboard Web Controller`, `OrganisationHistory Entity Repository`, `Fonction Web Controller`, `TypeActe Entity Repository`, `OccupationPoste Domain Entity`, `SoldeConge Domain Entity`, `OrganisationHistory Entity Repository`, `TenantRequestSubscriber Domain Entity`, `MissionService Domain Entity`, `Mission Entity Repository`, `Visite Entity Repository`, `InterimService Domain Entity`, `Presence Domain Entity`, `DocumentService Domain Entity`, `SoldeConge Entity Repository`, `TypeInstitution Domain Entity`, `Absence Entity Repository`, `OccupationPoste Entity Repository`, `Interim Entity Repository`, `ActeService Domain Entity`, `Acte Entity Repository`, `executeInTenantContext() Domain Entity`, `Affectation Entity Repository`, `Document Entity Repository`, `Poste Entity Repository`, `getInstitution() Domain Entity`, `setInstitution() Operation`, `getInstitution() Operation`, `setInstitution() Operation`, `setInstitution() Operation`?**
  _High betweenness centrality (0.200) - this node is a cross-community bridge._
- **Why does `Employe` connect `Employe Domain Entity` to `User Domain Entity`, `Mission Domain Entity`, `Security Access Voters`, `TenantInterface Domain Entity`, `Interim Domain Entity`, `Document Domain Entity`, `Acte Domain Entity`, `Affectation Domain Entity`, `Conges Domain Entity`, `Poste Domain Entity`, `Absence Domain Entity`, `Structure Domain Entity`, `Multi-Tenancy SQL Filter`, `Employe Web Controller`, `Service Domain Entity`, `AbsenceLog Entity Repository`, `Conges Entity Repository`, `Pointage Domain Entity`, `Visite Domain Entity`, `Absence Form Type`, `Dashboard Web Controller`, `OrganisationHistory Entity Repository`, `Fonction Web Controller`, `MissionParticipant Domain Entity`, `OccupationPoste Domain Entity`, `SoldeConge Domain Entity`, `MissionService Domain Entity`, `Mission Entity Repository`, `CongeService Domain Entity`, `InterimService Domain Entity`, `Presence Domain Entity`, `DocumentService Domain Entity`, `SoldeConge Entity Repository`, `Pointage Web Controller`, `Absence Entity Repository`, `OccupationPoste Entity Repository`, `MissionLog Entity Repository`, `Interim Entity Repository`, `AbsenceService Domain Entity`, `ActeService Domain Entity`, `CarriereService Domain Entity`, `Acte Entity Repository`, `Affectation Entity Repository`, `Document Entity Repository`, `Security Access Voters`, `getDateEmbauche() Operation`, `PosteAffectation Form Type`, `addPointage() Operation`, `getNom() Operation`, `getInstitution() Operation`, `findOverlapping() Operation`?**
  _High betweenness centrality (0.190) - this node is a cross-community bridge._
- **Why does `User` connect `User Domain Entity` to `Employe Domain Entity`, `Mission Domain Entity`, `Security Access Voters`, `TenantInterface Domain Entity`, `Interim Domain Entity`, `Document Domain Entity`, `Acte Domain Entity`, `Conges Domain Entity`, `Absence Domain Entity`, `SecurityAuditLog Domain Entity`, `Multi-Tenancy SQL Filter`, `AbsenceLog Entity Repository`, `AccountSecurity Web Controller`, `DocumentVersion Domain Entity`, `Absence Form Type`, `OrganisationHistory Entity Repository`, `PasswordResetToken Entity Repository`, `OrganisationHistory Entity Repository`, `InterimLog Domain Entity`, `AbsenceLog Domain Entity`, `MissionLog Domain Entity`, `CongesLog Domain Entity`, `MissionService Domain Entity`, `Security Access Voters`, `CongeService Domain Entity`, `InterimService Domain Entity`, `DocumentService Domain Entity`, `SoldeConge Entity Repository`, `Absence Entity Repository`, `AbsenceService Domain Entity`, `ActeService Domain Entity`, `Security Access Voters`, `Security Access Voters`, `User Web Controller`, `Security Access Voters`, `Security Access Voters`, `User Entity Repository`, `UserChecker Domain Entity`, `getInstitution() Operation`?**
  _High betweenness centrality (0.175) - this node is a cross-community bridge._
- **Should `Absence Web Controller` be split into smaller, more focused modules?**
  _Cohesion score 0.05912653975363942 - nodes in this community are weakly interconnected._
- **Should `Acte Form Type` be split into smaller, more focused modules?**
  _Cohesion score 0.04819277108433735 - nodes in this community are weakly interconnected._
- **Should `Employe Domain Entity` be split into smaller, more focused modules?**
  _Cohesion score 0.04794210764360018 - nodes in this community are weakly interconnected._
- **Should `User Domain Entity` be split into smaller, more focused modules?**
  _Cohesion score 0.04471153846153846 - nodes in this community are weakly interconnected._