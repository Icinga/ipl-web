<?php

namespace ipl\Tests\Web;

use ipl\Orm\Common\SortUtil;

/**
 * Imitates the behavior of {@see \ipl\Web\Control\SortControl} class
 *
 * Reduces the {@see self::__construct()} and {@see self::create()} method to only required functionality
 */
class SortControl {

    protected $columns = [];

    protected $default;

    private function __construct(array $columns)
    {
        $this->setColumns($columns);
    }

    public static function create(array $options)
    {
        $normalized = [];
        foreach ($options as $spec => $label) {
            $normalized[SortUtil::normalizeSortSpec($spec)] = $label;
        }

        return new static($normalized);
    }

    public function setColumns($columns)
    {
        $this->columns = array_change_key_case($columns, CASE_LOWER);

        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function setDefault(string $default): self
    {
        // We're working with lowercase keys throughout the sort control
        $this->default = strtolower($default);

        return $this;
    }

    public function getSortParam()
    {
        return '';
    }

    public function handleRequest()
    {
    }

    public function apply()
    {
        return $this;
    }
}
