<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class AdminListQuery
{
    /**
     * @param  list<string>  $searchable
     * @param  list<string>  $sortable
     * @param  array<string, list<string>>  $filters
     */
    public static function paginate(
        Builder $query,
        Request $request,
        array $searchable,
        array $sortable,
        array $filters = [],
        string $defaultSort = 'created_at',
    ): LengthAwarePaginator {
        $search = trim((string) $request->query('q', ''));
        if ($search !== '' && $searchable !== []) {
            $query->where(function (Builder $nested) use ($searchable, $search): void {
                foreach ($searchable as $column) {
                    if (str_contains($column, '.')) {
                        $segments = explode('.', $column);
                        $field = array_pop($segments);
                        $relation = implode('.', $segments);
                        $nested->orWhereHas($relation, fn (Builder $related) => $related->where($field, 'like', '%'.$search.'%'));
                    } else {
                        $nested->orWhere($column, 'like', '%'.$search.'%');
                    }
                }
            });
        }

        foreach ($filters as $parameter => $column) {
            $value = $request->query($parameter);
            if (! is_scalar($value) || $value === '') {
                continue;
            }
            if (is_array($column)) {
                $query->whereIn($column[0], $column[1]);
            } else {
                $query->where($column, $value);
            }
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $sort = (string) $request->query('sort', $defaultSort);
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $fallbackSort = in_array($defaultSort, $sortable, true) ? $defaultSort : ($sortable[0] ?? 'created_at');
        $query->orderBy(in_array($sort, $sortable, true) ? $sort : $fallbackSort, $direction);

        return $query->paginate(min(100, max(10, (int) $request->query('per_page', 15))))->withQueryString();
    }
}
