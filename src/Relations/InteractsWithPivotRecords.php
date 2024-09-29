<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
trait InteractsWithPivotRecords
{
    /**
     * Attach models to the relationship.
     *
     * @param int|array<int|string, array<string, mixed>>|string|\Illuminate\Database\Eloquent\Collection<int, TRelatedModel>|TRelatedModel|\Illuminate\Support\Collection<int, int|string> $ids
     * @return TDeclaringModel
     */
    public function attach($ids)
    {
        [$records, $others] = $this->decodeRecords();

        $records = $this->formatIds(
            $this->parseIds($ids)
        ) + $records;

        $this->child->{$this->path} = $this->encodeRecords($records, $others);

        return $this->child;
    }

    /**
     * Detach models from the relationship.
     *
     * @param int|string|\Illuminate\Database\Eloquent\Collection<int, TRelatedModel>|TRelatedModel|\Illuminate\Support\Collection<int, int|string> $ids
     * @return TDeclaringModel
     */
    public function detach($ids = null)
    {
        [$records, $others] = $this->decodeRecords();

        if (!is_null($ids)) {
            /** @var list<int|string> $parsedIds */
            $parsedIds = $this->parseIds($ids);

            $records = array_diff_key(
                $records,
                array_flip($parsedIds)
            );
        } else {
            $records = [];
        }

        $this->child->{$this->path} = $this->encodeRecords($records, $others);

        return $this->child;
    }

    /**
     * Sync the relationship with a list of models.
     *
     * @param int|array<int|string, array<string, mixed>>|string|\Illuminate\Database\Eloquent\Collection<int, TRelatedModel>|TRelatedModel|\Illuminate\Support\Collection<int, int|string> $ids
     * @return TDeclaringModel
     */
    public function sync($ids)
    {
        [, $others] = $this->decodeRecords();

        $records = $this->formatIds(
            $this->parseIds($ids)
        );

        $this->child->{$this->path} = $this->encodeRecords($records, $others);

        return $this->child;
    }

    /**
     * Toggle models from the relationship.
     *
     * @param int|array<int|string, array<string, mixed>>|string|\Illuminate\Database\Eloquent\Collection<int, TRelatedModel>|TRelatedModel|\Illuminate\Support\Collection<int, int|string> $ids
     * @return TDeclaringModel
     */
    public function toggle($ids)
    {
        [$records, $others] = $this->decodeRecords();

        $ids = $this->formatIds(
            $this->parseIds($ids)
        );

        $records = array_diff_key(
            $ids + $records,
            array_intersect_key($records, $ids)
        );

        $this->child->{$this->path} = $this->encodeRecords($records, $others);

        return $this->child;
    }

    /**
     * Decode the records on the child model.
     *
     * @return array{0: array<int|string, array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    protected function decodeRecords()
    {
        $records = [];
        $others = [];

        $key = $this->key ? str_replace('->', '.', $this->key) : $this->key;

        foreach ((array) $this->child->{$this->path} as $record) {
            if (!is_array($record)) {
                $records[$record] = [];

                continue;
            }

            $foreignKey = Arr::get($record, $key);

            if (!is_null($foreignKey)) {
                $records[$foreignKey] = $record;
            } else {
                $others[] = $record;
            }
        }

        return [$records, $others];
    }

    /**
     * Encode the records for the child model.
     *
     * @param array<int|string, array<string, mixed>> $records
     * @param list<array<string, mixed>> $others
     * @return list<array<string, mixed>>|list<int|string>
     */
    protected function encodeRecords(array $records, array $others)
    {
        if (!$this->key) {
            return array_keys($records);
        }

        $key = str_replace('->', '.', $this->key);

        foreach ($records as $id => &$attributes) {
            Arr::set($attributes, $key, $id);
        }

        return array_merge(
            array_values($records),
            $others
        );
    }

    /**
     * Get all of the IDs from the given mixed value.
     *
     * @param int|array<int|string, array<string, mixed>>|string|\Illuminate\Database\Eloquent\Collection<int, TRelatedModel>|TRelatedModel|\Illuminate\Support\Collection<int, int|string> $value
     * @return array<int|string, array<string, mixed>>|list<int|string>
     */
    protected function parseIds($value)
    {
        if ($value instanceof Model) {
            return [$value->{$this->ownerKey}];
        }

        if ($value instanceof Collection) {
            /** @var \Illuminate\Support\Collection<int, int|string> $ids */
            $ids = $value->pluck($this->ownerKey);

            return $ids->all();
        }

        if ($value instanceof BaseCollection) {
            /** @var list<int|string> $ids */
            $ids = $value->toArray();

            return $ids;
        }

        return (array) $value;
    }

    /**
     * Format the parsed IDs.
     *
     * @param array<int|string, array<string, mixed>>|list<int|string> $ids
     * @return array<int|string, array<string, mixed>>
     */
    protected function formatIds(array $ids)
    {
        return (new BaseCollection($ids))->mapWithKeys(function ($attributes, $id) {
            if (!is_array($attributes)) {
                [$id, $attributes] = [$attributes, []];
            }

            return [$id => $attributes];
        })->all();
    }
}
