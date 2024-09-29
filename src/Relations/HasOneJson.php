<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Staudenmeir\EloquentJsonRelations\Relations\HasManyJson<TRelatedModel, TDeclaringModel>
 */
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
                /** @var \Illuminate\Database\Eloquent\Collection<int, TRelatedModel> $relatedModel */
                $relatedModel = new Collection(
                    array_filter([$model->$relation])
                );

                $model->setRelation(
                    $relation,
                    $this->hydratePivotRelation(
                        $relatedModel,
                        $model,
                        fn (Model $model) => $model->{$this->getPathName()}
                    )->first()
                );
            }
        }

        return $models;
    }

    /**
     * Make a new related instance for the given model.
     *
     * @param TDeclaringModel $parent
     * @return TRelatedModel
     */
    public function newRelatedInstanceFor(Model $parent)
    {
        return $this->related->newInstance();
    }
}
