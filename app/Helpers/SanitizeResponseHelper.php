<?php

namespace App\Helpers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SanitizeResponseHelper
{
    /**
     * Handle and sanitize both paginated and non-paginated response data.
     *
     * @param mixed $response
     * @return array
     */
    public static function sanitizeResponse($response)
    {
        if ($response instanceof LengthAwarePaginator) {
            $items = $response->items();

            return [
                'items' => $items,
                'pagination' => [
                    'current_page' => $response->currentPage(),
                    'last_page' => $response->lastPage(),
                    'per_page' => $response->perPage(),
                    'total' => $response->total(),
                    'from' => $response->firstItem() ?? null,
                    'to' => $response->lastItem() ?? null
                ],
            ];
        }

        // Handle non-paginated data
        return $response;
    }
}