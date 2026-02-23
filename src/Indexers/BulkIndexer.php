<?php

namespace Larelastic\Elastic\Indexers;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Larelastic\Elastic\Traits\Migratable;
use Larelastic\Elastic\Payloads\RawPayload;
use Larelastic\Elastic\Payloads\TypePayload;
use Larelastic\Elastic\Facades\Elastic;
use Illuminate\Database\Eloquent\Collection;

class BulkIndexer implements IndexerInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(Collection $models)
    {
        $model = $models->first();
        $indexConfigurator = $model->getIndexConfigurator();

        $bulkPayload = new TypePayload($model);

        if (in_array(Migratable::class, class_uses_recursive($indexConfigurator))) {
            $bulkPayload->useAlias('write');
        }

        if ($documentRefresh = config('elastic.document_refresh')) {
            $bulkPayload->set('refresh', $documentRefresh);
        }

        $models->each(function ($model) use ($bulkPayload) {
            if ($model::usesSoftDelete() && config('scout.soft_delete', false)) {
                $model->pushSoftDeleteMetadata();
            }

            $modelData = array_merge(
                $model->toSearchableArray(),
                $model->scoutMetadata()
            );

            if (empty($modelData)) {
                return true;
            }

            $actionPayload = (new RawPayload())
                ->set('index._id', $model->getScoutKey());

            $bulkPayload
                ->add('body', $actionPayload->get())
                ->add('body', $modelData);
        });

        Elastic::bulk($bulkPayload->get());
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Collection $models)
    {
        $model = $models->first();

        $bulkPayload = new TypePayload($model);

        $models->each(function ($model) use ($bulkPayload) {
            $actionPayload = (new RawPayload())
                ->set('delete._id', $model->getScoutKey());

            $bulkPayload->add('body', $actionPayload->get());
        });

        try {
            Elastic::bulk($bulkPayload->get());
        } catch (ClientResponseException $e) {
            if ($e->getResponse()->getStatusCode() !== 404) {
                throw $e;
            }
        }
    }
}
