<?php

namespace App\Traits;
use Illuminate\Database\Eloquent\Builder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait HasSearch
{
    /**
     * Define the searchable fields for the model.
     *
     * @return array
     */
    public function searchFields(): array
    {
        return [];
    }

    /**
     * Scope a query to search by the specified term in searchable fields.
     *
     * @param Builder $builder
     * @param string $search
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function scopeSearch(Builder $builder, string $search = ''): void
    {
        $search = $search ?: request()->get('search');
        if (!$search) return;
        $fields = $this->searchFields();

        $builder->where(function (Builder $builder) use ($fields, $search) {
            foreach (explode(' ', $search) as $word) {
                $builder->whereAny($fields, 'ILIKE', "%$word%");
            }
        });

    }
}
