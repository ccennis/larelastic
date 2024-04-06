<?php


namespace Larelastic\Elastic\Console;

use Symfony\Component\Console\Input\InputOption;
use Illuminate\Foundation\Console\ModelMakeCommand;
use function str_replace;
use function trim;

class SearchableModelMakeCommand extends ModelMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'make:searchable-model {name}';

    /**
     * {@inheritdoc}
     */
    protected $name = 'make:searchable-model';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a new searchable model';

    /**
     * {@inheritdoc}
     */
    public function getStub()
    {
        return __DIR__.'/stubs/searchable_model.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        $options = parent::getOptions();

        $options[] = [
            'index-configurator',
            'i',
            InputOption::VALUE_REQUIRED,
            'Specify the index configurator for the model. It\'ll be created if doesn\'t exist.',
        ];

        return $options;
    }

    /**
     * Get the index configurator.
     *
     * @return string
     */
    protected function getIndexConfigurator()
    {
        return trim($this->option('index-configurator'));
    }

    /**
     * Build the class.
     *
     * @param string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $indexConfigurator = $this->getIndexConfigurator();

        $stub = str_replace(
            'DummyIndexConfigurator',
            $indexConfigurator ? "{$indexConfigurator}::class" : 'null', $stub
        );

        return $stub;
    }

    /**
     * Handle the command.
     *
     * @var string
     */
    public function handle()
    {
        $indexConfigurator = $this->getIndexConfigurator();

        if ($indexConfigurator && ! $this->alreadyExists($indexConfigurator)) {
            $this->call('make:index-configurator', [
                'name' => $indexConfigurator,
            ]);
        }

        parent::handle();
    }
}