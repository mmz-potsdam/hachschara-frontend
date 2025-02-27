<?php

// src/Command/ZoteroFetchCollectionCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class ZoteroFetchCollectionCommand extends Command
{
    protected $zoteroApiService;
    protected $cslDir;
    protected $zoteroCollections;

    public function __construct(
        \App\Service\ZoteroApiService $zoteroApiService,
        string $projectDir,
        array $zoteroCollections
    ) {
        // you *must* call the parent constructor
        parent::__construct();

        $this->zoteroApiService = $zoteroApiService;
        $this->cslDir = $projectDir . '/data/csl';
        $this->zoteroCollections = $zoteroCollections;
    }

    protected function configure(): void
    {
        $this
            ->setName('zotero:fetch-collections')
            ->setDescription('Fetch items from Zotero collection')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ret = -3;

        $groupId = $this->zoteroApiService->getGroupId();

        foreach ($this->zoteroCollections as $basename => $descr) {
            $api = $this->zoteroApiService->getInstance();
            $key = $descr['key'];

            $request = $api
                ->collections($key);

            try {
                $response = $request->send();
            }
            catch (\GuzzleHttp\Exception\ClientException $e) {
                $output->writeln(sprintf(
                    '<error>Error requesting collection %s (%s)</error>',
                    $key,
                    $e->getResponse()->getStatusCode()
                ));

                /*
                if (404 == $e->getResponse()->getStatusCode()) {
                    // deleted
                    return false;
                }
                */
                return -1;
            }

            $info = $response->getBody();
            $numItems = $info['meta']['numItems'];

            $start = 0;
            $batchSize = 50;

            $continue = $numItems > 0;
            $data = [];

            while ($continue) {
                // start with new instance since start/limit would get set multiple times in query string
                $request = $this->zoteroApiService->getInstance()
                    ->collections($key)
                    ->items()
                    ->sortBy('creator')
                    ->direction('asc')
                    ->start($start)
                    ->limit($batchSize);

                try {
                    $response = $request->send();
                }
                catch (\GuzzleHttp\Exception\ClientException $e) {
                    break;
                }

                $headers = $response->getHeaders();

                $start += $batchSize;
                $continue = $start < $headers['Total-Results'][0];

                $items  = $response->getBody();
                foreach ($items as $item) {
                    if (in_array($item['data']['itemType'], [ 'attachment', 'note'])) {
                        continue;
                    }

                    $creativeWork = \App\Entity\CreativeWork::fromZotero($item['data'], $item['meta']);
                    $data[] = $creativeWork->jsonSerialize(); // to citproc json
                }

                $statusCode = $response->getStatusCode();
                if ($statusCode < 200 || $statusCode >= 300) {
                    // something went wrong
                    break;
                }
            }

            if (count($data) > 0) {
                if (-3 == $ret) {
                    $ret = 0;
                }

                $fnameOut = $this->cslDir . '/' . $basename . '.json';

                $out = json_encode([
                    'group-id' => $groupId,
                    'key' => $key,
                    'data' => $data,
                ], JSON_UNESCAPED_SLASHES
                       | JSON_PRETTY_PRINT
                       | JSON_UNESCAPED_UNICODE);

                $res = file_put_contents($fnameOut, $out);

                if (false === $res) {
                    $ret = -2;
                    $output->writeln(sprintf(
                        '<error>Error writing %s</error>',
                        $fnameOut
                    ));
                }
            }
        }

        if (-3 == $ret) {
            $output->writeln(sprintf('<info>Empty collection</info>'));

        }

        return $ret;
    }
}
