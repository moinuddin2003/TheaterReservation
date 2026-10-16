<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminTablePage
{
    /**
     * @param  list<string>  $searchable
     * @param  list<string>  $sortable
     * @param  list<array{label: string, key: string, type?: string, property?: string}>  $columns
     * @param  list<array{name: string, label: string, options: array<string, string>}>  $filters
     * @param  array<string, string>  $filterColumns
     */
    public static function render(
        Request $request,
        string $title,
        string $subtitle,
        string $breadcrumb,
        string $icon,
        Builder $query,
        array $searchable,
        array $sortable,
        array $columns,
        array $filters = [],
        array $filterColumns = [],
        string $defaultSort = 'created_at',
    ): View {
        foreach ($filterColumns as $parameter => $column) {
            $value = $request->query($parameter);
            if (is_string($value) && $value !== '') {
                $query->where($column, $value);
            }
        }

        return view('admin.pages.table', [
            'pageTitle' => $title,
            'pageSubtitle' => $subtitle,
            'breadcrumb' => $breadcrumb,
            'icon' => $icon,
            'rows' => AdminListQuery::paginate(
                $query,
                $request,
                $searchable,
                $sortable,
                defaultSort: $defaultSort,
            ),
            'columns' => $columns,
            'filters' => $filters,
            'sortOptions' => collect($sortable)->mapWithKeys(
                fn (string $sort): array => [$sort => str($sort)->replace('_', ' ')->title()->toString()],
            )->all(),
        ]);
    }
}
