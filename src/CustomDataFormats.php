<?php

namespace Engency\DataStructures;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

trait CustomDataFormats
{
    /**
     * @var bool
     */
    private $exportFormat = false;

    /**
     * @param bool $resolveRelations
     *
     * @return array
     */
    public function toArray(bool $resolveRelations = true) : array
    {
        return $this->toArrayFormat(null, $resolveRelations);
    }

    /**
     * @param string|null $format
     * @param bool        $resolveRelations
     *
     * @return array
     */
    public function toArrayFormat(?string $format = null, bool $resolveRelations = true) : array
    {
        $returnData = [];
        if ($format === null) {
            $format = $this->exportFormat === false ? 'default' : $this->exportFormat;
        }

        foreach ($this->getArrayFieldsForFormat($format) as $field) {
            $returnData = array_merge($returnData, $this->parseField($field, $resolveRelations));
        }

        return $returnData;
    }

    /**
     * @param string $format
     *
     * @return array
     */
    private function getArrayFieldsForFormat(string $format = 'default') : array
    {
        if (!isset($this->exports) || !is_array($this->exports)) {
            return [];
        }

        if (!isset($this->exports[$format]) || !is_array($this->exports[$format])) {
            $format = 'default';
        }

        return ( isset($this->exports[$format]) && is_array($this->exports[$format]) )
            ? $this->exports[$format] : [];
    }

    /**
     * @param string|array $field
     * @param bool         $resolveRelations
     *
     * @return array
     */
    private function parseField($field, bool $resolveRelations) : array
    {
        if (!is_array($field)) {
            $field = [
                $field,
                [],
            ];
        }

        if (isset($field[1]['format'])) {
            return $this->parseFieldAsArray($field[0], $field[1], $resolveRelations);
        }

        $value = $this->{$field[0]};
        if ($resolveRelations && $value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if ($value instanceof Carbon) {
            $value = $this->parseFieldAsDate($value, $field[1]);
        }

        return [$field[0] => $value];
    }

    /**
     * @param string $fieldName
     * @param array  $properties
     * @param bool   $resolveRelations
     *
     * @return array
     */
    private function parseFieldAsArray(string $fieldName, array $properties, bool $resolveRelations) : array
    {
        $subFormat = $properties['format'];
        /** @var Builder $q */
        $q = $this->$fieldName();
        if ($q instanceof BelongsTo || $q instanceof HasOne) {
            return [$fieldName => $this->parseFieldAsSingleRelation($q, $properties, $resolveRelations)];
        }

        $limit = isset($properties['limit']) ? ( (int) $properties['limit'] ) : -1;
        if (isset($properties['order'])) {
            $q = $q->orderBy(...$properties['order']);
        }

        $data = [];
        $q    = $q->limit($limit)->get();
        if ($resolveRelations) {
            $data[$fieldName] = $q->map(
                function ($item) use ($subFormat) {
                    if ($item instanceof ExportsCustomDataFormats) {
                        return $item->toArrayFormat($subFormat);
                    }

                    /** @var Arrayable $item */
                    return $item->toArray();
                }
            )->toArray();
        } else {
            $data[$fieldName] = $q->map(
                function ($item) use ($subFormat) {
                    if ($item instanceof ExportsCustomDataFormats) {
                        $item->setFormat($subFormat);
                    }

                    return $item;
                }
            );
        }

        if (isset($properties['countFields'])) {
            $totalCount                          = $this->$fieldName()->count();
            $data[$properties['countFields'][0]] = $totalCount;
            $data[$properties['countFields'][1]] =
                $resolveRelations ? count($data[$fieldName]) : $data[$fieldName]->count();
        }

        return $data;
    }

    /**
     * @param Relation $query
     * @param array    $properties
     * @param bool     $resolveRelations
     *
     * @return array|mixed
     */
    private function parseFieldAsSingleRelation(Relation $query, array $properties, bool $resolveRelations)
    {
        $format = $properties['format'] ?? null;
        $item   = $query->get()->first();
        if (!$resolveRelations) {
            if ($item instanceof ExportsCustomDataFormats) {
                $item->setFormat($format);
            }

            return $item;
        }

        if ($item instanceof ExportsCustomDataFormats) {
            return $item->toArrayFormat($format);
        }

        return ( $item === null ) ? null : $item->toArray();
    }

    /**
     * @param Carbon $date
     * @param array  $properties
     * @return string
     */
    private function parseFieldAsDate(Carbon $date, array $properties) : string
    {
        if (isset($properties['dateFormat'])) {
            return $date->format($properties['dateFormat']);
        }

        return $date->toISOString();
    }

    /**
     * @param string $format
     *
     * @return $this
     */
    public function setFormat(string $format = 'default')
    {
        $this->exportFormat = $format;

        return $this;
    }
}
