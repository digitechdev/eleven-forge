<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The "Show N entries" choice shared by every paginated table.
 */
final class PerPage
{
    /**
     * Page sizes a user can pick.
     *
     * @var list<int>
     */
    public const array OPTIONS = [10, 25, 50, 100, 200];

    public const int DEFAULT = 10;

    /**
     * The requested page size, or the default when it is missing or not one of the options.
     */
    public static function fromRequest(Request $request): int
    {
        $perPage = $request->integer('per_page', self::DEFAULT);

        return in_array($perPage, self::OPTIONS, true) ? $perPage : self::DEFAULT;
    }
}
