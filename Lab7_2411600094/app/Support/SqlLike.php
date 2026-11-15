<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Lab 7 Part 7 Step 2: the only place that turns a browser's text box into a
 * LIKE pattern.
 *
 * A search term is data, and LIKE gives the two characters `%` and `_` a
 * meaning of their own, so "100%" would otherwise match everything and
 * "a_b" would match "axb". Everything is therefore escaped before it is
 * wrapped, and the escape character is named in the SQL itself (ESCAPE '!')
 * instead of leaning on MySQL's backslash default — the test suite runs the
 * same scopes on sqlite, and there an unquoted backslash is just a backslash.
 */
class SqlLike
{
    /** Longest term we will look for; a paste of a whole page is not a search. */
    public const MAX_LENGTH = 120;

    /**
     * @param  array<int, string>  $columns  column names written by code, never by the browser
     */
    public static function any(Builder $query, array $columns, ?string $term, string $boolean = 'and'): Builder
    {
        $pattern = self::pattern($term);

        if ($pattern === null) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($columns, $pattern, $boolean) {
            foreach ($columns as $i => $column) {
                $clause = fn (Builder $q) => $q->whereRaw("$column LIKE ? ESCAPE '!'", [$pattern]);

                $i === 0 ? $clause($inner) : $inner->orWhere($clause);
            }
        }, null, null, $boolean);
    }

    /** "%escaped term%" — null when there is nothing worth searching for. */
    public static function pattern(?string $term): ?string
    {
        // Strip control characters (they are never typed on purpose), collapse
        // the spacing, then cap the length.
        $clean = preg_replace('/[\x00-\x1f\x7f]+/u', ' ', (string) $term) ?? '';
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? '');

        if ($clean === '') {
            return null;
        }

        $clean = mb_substr($clean, 0, self::MAX_LENGTH);

        return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $clean) . '%';
    }
}
