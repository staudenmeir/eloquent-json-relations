<?php

namespace Staudenmeir\EloquentJsonRelations\Relations\Postgres;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Staudenmeir\EloquentJsonRelations\Casts\Uuid;

trait IsPostgresRelation
{
    /**
     * Get the wrapped and cast JSON column.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $column
     * @param string $key
     * @return \Illuminate\Database\Query\Expression
     */
    protected function jsonColumn(Builder $query, Model $model, $column, $key)
    {
        $sql = $query->getQuery()->getGrammar()->wrap($column);

        if ($model->getKeyName() === $key && in_array($model->getKeyType(), ['int', 'integer'])) {
            $sql = '('.$sql.')::bigint';
        }

        if ($model->hasCast($key) && $model->getCasts()[$key] === Uuid::class) {
            $sql = '('.$sql.')::uuid';
        }

        return new Expression($sql);
    }

    /**
     * Get the name of the "where in" method for eager loading.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @return string
     */
    protected function whereInMethod(Model $model, $key)
    {
        return 'whereIn';
    }
}
