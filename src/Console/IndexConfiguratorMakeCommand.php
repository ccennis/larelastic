<?php


namespace Larelastic\Elastic\Console;


use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use function str_replace;

class IndexConfiguratorMakeCommand extends GeneratorCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'make:index-configurator';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a new Elasticsearch index configurator';

    /**
     * {@inheritdoc}
     */
    protected $type = 'Configurator';

    /**
     * {@inheritdoc}
     */
    public function getStub()
    {
        return __DIR__.'/stubs/index_configurator.stub';
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            ['DummyNamespace', 'DummyRootNamespace', 'NamespacedDummyUserModel', 'DummyName'],
            [$this->getNamespace($name), $this->rootNamespace(), $this->userProviderModel(),
                Str::snake(Str::pluralStudly(str_replace('IndexConfigurator', '', $this->argument('name'))))],
            $stub
        );

        return $this;
    }
}