<?php

namespace App\Command;

use App\Service\BlockedFilesManager;
use App\Service\Mailer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BlockedFilesReminderCommand extends Command
{
    protected static $defaultName = 'app:blocked-files-reminder';
    protected static $defaultDescription = 'Check if there is any blocked files and send a reminder email if there\'s any';

    private Mailer $mailer;
    private BlockedFilesManager $bfm;

    public function __construct(
        Mailer $mailer,
        BlockedFilesManager $bfm,
        string $name = null
    ) {
        $this->mailer = $mailer;
        $this->bfm = $bfm;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!empty($this->bfm->read(false))) {
            $this->mailer->sendBlockedFilesReminder();
        }

        return Command::SUCCESS;
    }
}
