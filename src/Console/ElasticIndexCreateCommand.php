<?php


namespace Larelastic\Elastic\Console;


use Larelastic\Elastic\Facades\Elastic;
use Larelastic\Elastic\Payloads\IndexPayload;
use Larelastic\Elastic\Payloads\TypePayload;
use Larelastic\Elastic\Traits\Migratable;
use Larelastic\Elastic\Traits\RequiresModelArgument;
use Illuminate\Console\Command;
use function class_uses_recursive;
use function in_array;
use function sprintf;

class ElasticIndexCreateCommand extends Command
{
    use RequiresModelArgument;

    /**
     * {@inheritdoc}
     */
    protected $name = 'elastic:create-index';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create an Elasticsearch index';

    /**
     * Create an index.
     *
     * @return void
     */
    protected function createIndex()
    {
        if (!$model = $this->getModel()) {
            return;
        }

        $configurator = $model->getIndexConfigurator();

        $payload = (new IndexPayload($configurator))
            ->setIfNotEmpty('body.settings', $configurator->getSettings())
            ->get();

        Elastic::indices()
            ->create($payload);

        $payload = (new TypePayload($model))
            ->set('body.' . $model->searchableAs(), $configurator->getMappings())
            ->set('include_type_name', 'true');

        Elastic::indices()
            ->putMapping($payload->get());

        $this->info(sprintf(
            'The %s index was created!',
            $configurator->getName()
        ));
    }

    /**
     * Create an write alias.
     *
     * @return void
     */
    protected function createWriteAlias()
    {
        $configurator = $this->getModel()->getIndexConfigurator();

        if (!in_array(Migratable::class, class_uses_recursive($configurator))) {
            return;
        }

        $payload = (new IndexPayload($configurator))
            ->set('name', $configurator->getWriteAlias())
            ->get();

        Elastic::indices()
            ->putAlias($payload);

        $this->info(sprintf(
            'The %s alias for the %s index was created!',
            $configurator->getWriteAlias(),
            $configurator->getName()
        ));
    }

    /**
     * Handle the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createIndex();

        $this->createWriteAlias();
    }
}