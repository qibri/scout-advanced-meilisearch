<?php

namespace Omure\ScoutAdvancedMeilisearch;

use Closure;
use Laravel\Scout\Builder as LaravelScoutBuilder;
use Omure\ScoutAdvancedMeilisearch\Exceptions\BuilderException;

class Builder extends LaravelScoutBuilder
{
    public array $nested;

    protected array $allowedOperators = [
        '=',
        '!=',
        '>',
        '>=',
        '<',
        '<=',
        'IN',
        'NOT IN'
    ];

    public function where(mixed $field, $value = null, $valueWithOperator = null): static
    {
        $this->buildWhere($field, $value, $valueWithOperator);

        return $this;
    }

    public function orWhere($field, $value = null, $valueWithOperator = null): static
    {
        $this->buildWhere($field, $value, $valueWithOperator, 'OR');

        return $this;
    }

    public function whereBetween($field, array $values): static
    {
        $this->validateWhereBetweenValues($values);

        $this->where($field, '>=', $values[0]);
        $this->where($field, '<=', $values[1]);

        return $this;
    }

    public function orWhereBetween($field, array $values): static
    {
        $this->orWhere(function (Builder $query) use ($field, $values) {
            $query
                ->where($field, '>=', $values[0])
                ->where($field, '<=', $values[1]);
        });

        return $this;
    }

    private function _whereIn($field, array $values, bool $not = false, string $connector  = 'AND'): static
    {

        $operator = ($not ? 'NOT ' : '') . 'IN';
        $this->buildWhere($field, $operator, $values, $connector);

        return $this;
    }

    public function whereIn($field, array $values) :static
    {
        $this->_whereIn($field, $values);

        return $this;
    }

    public function orWhereIn($field, array $values): static
    {
        $this->_whereIn($field, $values, false, 'OR');

        return $this;
    }

    public function whereNotIn($field, array $values): static
    {
        $this->_whereIn($field, $values, true);

        return $this;
    }

    public function orWhereNotIn($field, array $values): static
    {
        $this->_whereIn($field, $values, true, 'OR');

        return $this;
    }

    protected function getTotalCount($results): int
    {
        $engine = $this->engine();
        return $engine->getTotalCount($results);
    }

    protected function buildWhere($field, $value = null, $valueWithOperator = null, $connector = 'AND')
    {
        if ($field instanceof Closure) {
            $nestedBuilder = new static($this->model, '');

            $field($nestedBuilder);

            $this->wheres[] = new BuilderWhere(
                $nestedBuilder,
                null,
                null,
                $connector,
            );

            return;
        }

        $appliedOperator = $valueWithOperator ? $value : '=';

        if (!in_array($appliedOperator, $this->allowedOperators)) {
            $allowedOperatorsList = implode(',', $this->allowedOperators);
            throw new BuilderException(
                "Operator $appliedOperator is not allowed. Allowed operators: $allowedOperatorsList."
            );
        }

        $appliedValue = $valueWithOperator ?: $value;

        $this->wheres[] = new BuilderWhere(
            $field,
            $appliedOperator,
            $appliedValue,
            $connector,
        );
    }

    private function validateWhereBetweenValues(array $values)
    {
        if (count($values) != 2) {
            throw new BuilderException("whereBetween values array requires exactly two elements.");
        }

        if (!is_numeric($values[0]) || !is_numeric($values[1])) {
            throw new BuilderException("whereBetween values array should contain only numeric elements.");
        }
    }
}