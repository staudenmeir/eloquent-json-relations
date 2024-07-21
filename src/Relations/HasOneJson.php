<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

class HasOneJson extends HasManyJson
{
    use SupportsDefaultModels;

    /** @inheritDoc */
    public function getResults()
    {
        if (is_null($this->getParentKey())) {
            return $this->getDefaultFor($this->parent);
        }

        return $this->first() ?: $this->getDefaultFor($this->parent);
    }

    /** @inheritDoc */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    /** @inheritDoc */
    public function match(array $models, Collection $results, $relation)
    {
        return $this->matchOne($models, $results, $relation);
    }

    /** @inheritDoc */
    public function matchOne(array $models, Collection $results, $relation)
    {
        if ($this->hasCompositeKey()) {
            $this->matchWithCompositeKey($models, $results, $relation, 'one');
        } else {
            HasOneOrMany::matchOneOrMany($models, $results, $relation, 'one');
        }

        if ($this->key) {
            foreach ($models as $model) {
                $model->setRelation(
                    $relation,
                    $this->hydratePivotRelation(
                        new Collection(
                            array_filter([$model->$relation])
                        ),
                        $model,
                        fn (Model $model) => $model->{$this->getPathName()}
                    )->first()
                );
            }
        }

        return $models;
    }

    /** @inheritDoc */
    public function newRelatedInstanceFor(Model $parent)
    {
        return $this->related->newInstance();
    }
}
