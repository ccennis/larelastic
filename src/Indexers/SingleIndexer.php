<?php

namespace Larelastic\Elastic\Indexers;

use Larelastic\Elastic\Facades\Elastic;
use Larelastic\Elastic\Traits\Migratable;
use Larelastic\Elastic\Payloads\DocumentPayload;
use Illuminate\Database\Eloquent\Collection;
use Log;

class SingleIndexer implements IndexerInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(Collection $models)
    {
        try {
            $models->each(function ($model) {
                if ($model::usesSoftDelete() && config('scout.soft_delete', false)) {
                    $model->pushSoftDeleteMetadata();
                }

                $modelData = $model->toSearchableArray();

                if (empty($modelData)) {
                    return true;
                }

                $indexConfigurator = $model->getIndexConfigurator();

                $payload = (new DocumentPayload($model))
                    ->set('body', $modelData);

                if (in_array(Migratable::class, class_uses_recursive($indexConfigurator))) {
                    $payload->useAlias('write');
                }

                if ($documentRefresh = config('scout_elastic.document_refresh')) {
                    $payload->set('refresh', $documentRefresh);
                }

                Elastic::index($payload->get());
            });
        } catch (\Exception $e){
            $message = $e->getMessage()." ".(basename($e->getFile()))." ".$e->getLine();
            Log::error($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Collection $models)
    {
        $models->each(function ($model) {
            $payload = (new DocumentPayload($model))
                ->set('client.ignore', 404)
                ->get();

            Elastic::delete($payload);
        });
    }
}
