<?php


namespace Larelastic\Elastic\Console;


use Larelastic\Elastic\Facades\Elastic;
use Larelastic\Elastic\Payloads\TypePayload;
use Larelastic\Elastic\Traits\Migratable;
use Larelastic\Elastic\Traits\RequiresModelArgument;
use Illuminate\Console\Command;
use LogicException;

class ElasticUpdateMappingCommand extends Command
{
    use RequiresModelArgument;

    /**
     * {@inheritdoc}
     */
    protected $name = 'elastic:update-mapping';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Update a model mapping';

    /**
     * Handle the command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$model = $this->getModel()) {
            return;
        }

        $configurator = $model->getIndexConfigurator();

        $mappings = array_merge_recursive(
            $configurator->getDefaultMapping(),
            $configurator->getMappings()
        );

        if (empty($mappings)) {
            throw new LogicException('Nothing to update: the mapping is not specified.');
        }

        $payload = (new TypePayload($model))
            ->set('body', $mappings);

        if (in_array(Migratable::class, class_uses_recursive($configurator))) {
            $payload->useAlias('write');
        }

        Elastic::indices()
            ->putMapping($payload->get());

        $this->info(sprintf(
            'The %s mapping was updated!',
            $model->searchableAs()
        ));
    }

}