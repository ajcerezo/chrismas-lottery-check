<?php
/**
 * @copyright
 */

namespace Acerezo\SorteoNavidad;

use Gnello\Mattermost\Driver;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Http\Adapter\Guzzle7\Client as ClientAlias;
use Http\Client\HttpClient;
use Joli\JoliNotif\Notification;
use Joli\JoliNotif\Notifier;
use Joli\JoliNotif\NotifierFactory;
use Pimple\Container;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Antonio Jose Cerezo Aranda <acerezo@elconfidencial.com>
 */
class ChrismasLottery extends Command
{
    private HttpClient $client;
    private RequestFactoryInterface $factory;
    private Notifier $notifier;
    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $guzzle = new Client();
        $this->factory = new HttpFactory();
        $this->client = new ClientAlias($guzzle);
        $this->notifier = NotifierFactory::create();
    }

    protected function configure()
    {
        $this->setName('lottery:chrismas');
        $this->addOption(
            'enumers',
            'e',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Query numer'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $numers = $input->getOption('enumers');

        $wonNumer = [];
        $moneyWon = 0;
        $url = 'https://api.elpais.com/ws/LoteriaNavidadPremiados';
        foreach ($numers as $numer) {
            $request = $this->factory->createRequest('GET', $url.'?n='.$numer);
            $response = $this->client->sendRequest($request);
            $result = json_decode(explode('=', $response->getBody()->getContents())[1], true);
            if (0 !== $result['premio']) {
                $wonNumer[] = $numer;
                $moneyWon += $result['premio'];
            }
        }

        $message = '';
        if (0 < count($wonNumer)) {
            $output->writeln('<info>Numer won:</info>');
            $message = "Numer won:\n";
            foreach ($wonNumer as $numer) {
                $output->writeln("<info>    - {$numer}</info>");
                $message .= "    - {$numer}\n";
            }
        }

        $output->writeln('<info>TOTAL WON: '.$moneyWon.'</info>');
        $message .= "Total Won: {$moneyWon}\n";
        $notification = new Notification();
        $notification
            ->setTitle('Lottery prces')
            ->setBody($message)
            ->setIcon(__DIR__.'/bingo3.png');

        $this->notifier->send($notification);


        return Command::SUCCESS;
    }

}