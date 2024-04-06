<?php


namespace Larelastic\Elastic\Console;


use Larelastic\Elastic\Facades\Elastic;
use Larelastic\Elastic\Models\IndexConfigurator;
use Larelastic\Elastic\Payloads\RawPayload;
use Larelastic\Elastic\Traits\Migratable;
use Larelastic\Elastic\Traits\RequiresModelArgument;
use Illuminate\Console\Command;
use function sprintf;

class ElasticIndexDropCommand extends Command
{
    use RequiresModelArgument;

    /**
     * {@inheritdoc}
     */
    protected $name = 'elastic:drop-index';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Drop an Elasticsearch index';

    /**
     * Create an index.
     *
     * @return void
     */
    public function handle()
    {
        $configurator = $this->getModel()->getIndexConfigurator();
        $indexName = $this->resolveIndexName($configurator);

        $payload = (new RawPayload())
            ->set('index', $indexName)
            ->get();

        Elastic::indices()
            ->delete($payload);

        $this->info(sprintf(
            'The index %s was deleted!',
            $indexName
        ));
    }

    /**
     * @param IndexConfigurator $configurator
     * @return string
     */
    protected function resolveIndexName($configurator)
    {
        if (in_array(Migratable::class, class_uses_recursive($configurator))) {
            $payload = (new RawPayload())
                ->set('name', $configurator->getWriteAlias())
                ->get();

            $aliases = Elastic::indices()
                ->getAlias($payload);

            return key($aliases);
        } else {
            return $configurator->getName();
        }
    }
}