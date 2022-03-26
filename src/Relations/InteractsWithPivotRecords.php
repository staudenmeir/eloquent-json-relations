<?php

namespace Staudenmeir\EloquentJsonRelations\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;

trait InteractsWithPivotRecords
{
    /**
     * Attach models to the relationship.
     *
     * @param mixed $ids
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function attach($ids)
    {
        [$records, $others] = $this->decodeRecords();

        $records = $this->formatIds($this->parseIds($ids)) + $records;

        $this->child->{$this->path} = $this->encodeRecords($records, $others);

        return $this->child;
    }

    /**
     * Detach models from the relationship.
     *
     * @param mixed $ids
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function detach($ids = null)
    {
        [$records, $others] = $this->decodeRecords();

        if (!is_null($ids)) {
            $records = array_diff_key(
                $records,
                array_flip($this->parseIds($ids))
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
     * @param mixed $ids
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function sync($ids)
    {
        [, $others] = $this->decodeRecords();

        $records = $this->formatIds($this->parseIds($ids));

        $this->child->{$this->path} = $this->encodeRecords($records, $others);

        return $this->child;
    }

    /**
     * Toggle models from the relationship.
     *
     * @param mixed $ids
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function toggle($ids)
    {
        [$records, $others] = $this->decodeRecords();

        $ids = $this->formatIds($this->parseIds($ids));

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
     * @return array
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
     * @param array $records
     * @param array $others
     * @return array
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
     * @param mixed $value
     * @return array
     */
    protected function parseIds($value)
    {
        if ($value instanceof Model) {
            return [$value->{$this->ownerKey}];
        }

        if ($value instanceof Collection) {
            return $value->pluck($this->ownerKey)->all();
        }

        if ($value instanceof BaseCollection) {
            return $value->toArray();
        }

        return (array) $value;
    }

    /**
     * Format the parsed IDs.
     *
     * @param array $ids
     * @return array
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
