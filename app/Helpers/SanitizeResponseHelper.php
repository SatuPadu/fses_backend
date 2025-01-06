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
                    'page' => $response->currentPage(),
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

        /**
     * Sanitiz the given authors array.
     *
     * @param array $authors
     * @return array
     */
    public static function sanitizeAuthors(array $authors): array
    {
        $sanitizedAuthors = array_filter($authors, function ($author) {
            // Filter out empty strings or null values
            return !is_null($author) && trim($author) !== '';
        });

        $sanitizedAuthors = array_map(function ($author) {
            // Remove prefixes like "By", "Exclusive by"
            $author = preg_replace('/^(By|Exclusive by)\s+/', '', $author);

            // Remove email or parenthetical information
            $author = preg_replace('/\([^)]+\)/', '', $author);

            // Remove titles and designations
            $author = preg_replace('/,\s*(Science Editor|Environment Editor|Global Health Correspondent|Senior Political Correspondent|Chief Reporter|Deputy Editor|Education Correspondent|Political Editor|Technology Editor)$/i', '', $author);

            // Remove location mentions (e.g., "in New York", "at Craven Cottage")
            $author = preg_replace('/\s+(in|at)\s+[A-Z][a-zA-Z0-9,\s]+$/i', '', $author);

            // Remove "and" conjunction for combined authors
            $author = preg_replace('/,\s*and\s+/i', ', ', $author);

            // Trim extra spaces and capitalize consistently
            return trim($author);
        }, $sanitizedAuthors);

        // Flatten long combined author names into separate entries
        $flatAuthors = [];
        foreach ($sanitizedAuthors as $author) {
            $parts = preg_split('/\s*,\s*/', $author);
            $flatAuthors = array_merge($flatAuthors, $parts);
        }

        // Ensure uniqueness
        return array_values(array_unique($flatAuthors));
    }
}