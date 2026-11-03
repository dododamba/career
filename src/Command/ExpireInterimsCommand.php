<?php

namespace App\Command;

use App\Service\InterimService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:interim:expire',
    description: 'Vérifie et expire automatiquement les désignations d\'intérim échues'
)]
class ExpireInterimsCommand extends Command
{
    public function __construct(
        private InterimService $interimService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('date', 'd', InputOption::VALUE_OPTIONAL, 'Date de référence pour vérifier l\'expiration (format YYYY-MM-DD)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Vérification et expiration automatique des intérims');

        $dateOption = $input->getOption('date');
        $dateReference = null;

        if ($dateOption) {
            try {
                $dateReference = new \DateTime($dateOption);
                $io->note(sprintf('Utilisation de la date de référence spécifiée : %s', $dateReference->format('d/m/Y')));
            } catch (\Exception $e) {
                $io->error('Format de date invalide. Utilisez le format YYYY-MM-DD.');
                return Command::FAILURE;
            }
        }

        $count = $this->interimService->verifierEtMettreAJourExpirations($dateReference);

        if ($count > 0) {
            $io->success(sprintf('%d intérim(s) échu(s) ont été automatiquement clôturé(s) et expirés.', $count));
        } else {
            $io->info('Aucun intérim actif n\'a atteint son échéance.');
        }

        return Command::SUCCESS;
    }
}
