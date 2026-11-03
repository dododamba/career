<?php

namespace App\Security\Voter;

use App\Entity\Mission;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MissionVoter extends Voter
{
    public const VIEW = 'MISSION_VIEW';
    public const EDIT = 'MISSION_EDIT';
    public const SUBMIT = 'MISSION_SUBMIT';
    public const VALIDATE = 'MISSION_VALIDATE';
    public const REJECT = 'MISSION_REJECT';
    public const START = 'MISSION_START';
    public const COMPLETE = 'MISSION_COMPLETE';
    public const REPORT = 'MISSION_REPORT';
    public const CLOSE = 'MISSION_CLOSE';
    public const CANCEL = 'MISSION_CANCEL';
    public const DELETE = 'MISSION_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::SUBMIT,
            self::VALIDATE,
            self::REJECT,
            self::START,
            self::COMPLETE,
            self::REPORT,
            self::CLOSE,
            self::CANCEL,
            self::DELETE,
        ], true) && $subject instanceof Mission;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var Mission $mission */
        $mission = $subject;

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Multi-tenant isolation: fail closed if institution mismatch
        $userInstitution = $currentUser->getInstitution();
        if (!$userInstitution) {
            return false;
        }

        $missionInstitution = $mission->getInstitution();
        if ($missionInstitution && $missionInstitution->getId() !== $userInstitution->getId()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($currentUser, $mission),
            self::EDIT => $this->canEdit($currentUser, $mission),
            self::SUBMIT => $this->canSubmit($currentUser, $mission),
            self::VALIDATE => $this->canValidate($currentUser, $mission),
            self::REJECT => $this->canReject($currentUser, $mission),
            self::START => $this->canStart($currentUser, $mission),
            self::COMPLETE => $this->canComplete($currentUser, $mission),
            self::REPORT => $this->canReport($currentUser, $mission),
            self::CLOSE => $this->canClose($currentUser, $mission),
            self::CANCEL => $this->canCancel($currentUser, $mission),
            self::DELETE => $this->canDelete($currentUser, $mission),
            default => false,
        };
    }

    private function isParticipant(User $currentUser, Mission $mission): bool
    {
        $employe = $currentUser->getEmploye();
        if ($employe && $mission->hasParticipant($employe)) {
            return true;
        }

        // Par association User direct
        if ($mission->getChefMission() && $mission->getChefMission()->getUser() && $mission->getChefMission()->getUser()->getId() === $currentUser->getId()) {
            return true;
        }

        foreach ($mission->getParticipants() as $p) {
            if ($p->getEmploye() && $p->getEmploye()->getUser() && $p->getEmploye()->getUser()->getId() === $currentUser->getId()) {
                return true;
            }
        }

        return false;
    }

    private function isCreator(User $currentUser, Mission $mission): bool
    {
        return $mission->getCreePar() && $mission->getCreePar()->getId() === $currentUser->getId();
    }

    private function canView(User $currentUser, Mission $mission): bool
    {
        if ($this->isParticipant($currentUser, $mission) || $this->isCreator($currentUser, $mission)) {
            return true;
        }

        return $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canEdit(User $currentUser, Mission $mission): bool
    {
        if (!in_array($mission->getStatut(), [Mission::STATUT_BROUILLON, Mission::STATUT_SOUMISE], true)) {
            return false;
        }

        if ($this->isCreator($currentUser, $mission)) {
            return true;
        }

        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canSubmit(User $currentUser, Mission $mission): bool
    {
        if ($mission->getStatut() !== Mission::STATUT_BROUILLON) {
            return false;
        }

        if ($this->isCreator($currentUser, $mission) || $this->isParticipant($currentUser, $mission)) {
            return true;
        }

        return $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MANAGER');
    }

    private function canValidate(User $currentUser, Mission $mission): bool
    {
        if (!in_array($mission->getStatut(), [Mission::STATUT_SOUMISE, Mission::STATUT_BROUILLON], true)) {
            return false;
        }

        if (!$this->security->isGranted('ROLE_MANAGER') && !$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        // Anti-auto-validation
        if ($this->isParticipant($currentUser, $mission)) {
            return false;
        }

        return true;
    }

    private function canReject(User $currentUser, Mission $mission): bool
    {
        if ($mission->getStatut() !== Mission::STATUT_SOUMISE) {
            return false;
        }

        if (!$this->security->isGranted('ROLE_MANAGER') && !$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        if ($this->isParticipant($currentUser, $mission)) {
            return false;
        }

        return true;
    }

    private function canStart(User $currentUser, Mission $mission): bool
    {
        if ($mission->getStatut() !== Mission::STATUT_VALIDEE) {
            return false;
        }

        if ($this->isParticipant($currentUser, $mission)) {
            return true;
        }

        return $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canComplete(User $currentUser, Mission $mission): bool
    {
        if (!in_array($mission->getStatut(), [Mission::STATUT_VALIDEE, Mission::STATUT_EN_COURS], true)) {
            return false;
        }

        if ($this->isParticipant($currentUser, $mission)) {
            return true;
        }

        return $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canReport(User $currentUser, Mission $mission): bool
    {
        if (!in_array($mission->getStatut(), [Mission::STATUT_VALIDEE, Mission::STATUT_EN_COURS, Mission::STATUT_TERMINEE], true)) {
            return false;
        }

        if ($this->isParticipant($currentUser, $mission)) {
            return true;
        }

        return $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canClose(User $currentUser, Mission $mission): bool
    {
        if ($mission->getStatut() !== Mission::STATUT_TERMINEE) {
            return false;
        }

        if (!$this->security->isGranted('ROLE_MANAGER') && !$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        // Anti-auto-validation
        if ($this->isParticipant($currentUser, $mission)) {
            return false;
        }

        return true;
    }

    private function canCancel(User $currentUser, Mission $mission): bool
    {
        if (in_array($mission->getStatut(), [Mission::STATUT_CLOTUREE, Mission::STATUT_ANNULEE, Mission::STATUT_REFUSEE], true)) {
            return false;
        }

        if ($mission->getStatut() === Mission::STATUT_BROUILLON && $this->isCreator($currentUser, $mission)) {
            return true;
        }

        return $this->security->isGranted('ROLE_MANAGER') || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canDelete(User $currentUser, Mission $mission): bool
    {
        if (in_array($mission->getStatut(), [Mission::STATUT_VALIDEE, Mission::STATUT_EN_COURS, Mission::STATUT_TERMINEE, Mission::STATUT_CLOTUREE], true)) {
            return false;
        }

        if ($this->isCreator($currentUser, $mission)) {
            return true;
        }

        return $this->security->isGranted('ROLE_ADMIN');
    }
}
