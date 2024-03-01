<?php declare(strict_types=1);

namespace PayonePayment\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PayonePayment\Components\ResendNotifyHandler\ResendNotifyHandler;

class ResendNotifyCommand extends Command
{
    protected static $defaultName = 'payone:send-notify';

    public function __construct(
        private readonly ResendNotifyHandler $resendNotifyHandler
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Send notification forwarding queue');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Sending notification forwarding queue');

        $this->resendNotifyHandler->send();

        $output->writeln('Notification forwarding queue sent');

        return 0;
    }
}
