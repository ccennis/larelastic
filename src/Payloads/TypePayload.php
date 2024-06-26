<?php

namespace Larelastic\Elastic\Payloads;

use Exception;
use Larelastic\Elastic\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;

class TypePayload extends IndexPayload
{
    /**
     * The model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * TypePayload constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @throws \Exception
     * @return void
     */
    public function __construct(Model $model)
    {
        if (! in_array(Searchable::class, class_uses_recursive($model))) {
            throw new Exception(sprintf(
                'The %s model must use the %s trait.',
                get_class($model),
                Searchable::class
            ));
        }

        $this->model = $model;

        parent::__construct($model->getIndexConfigurator());

        $this->protectedKeys[] = 'type';
    }
}
