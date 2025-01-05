<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Send a success response.
     *
     * @param mixed  $result
     * @param string $message
     * @return JsonResponse
     */
    public function sendResponse($result, string $message): JsonResponse
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        return response()->json($response, \Illuminate\Http\Response::HTTP_OK);
    }

    /**
     * Send an error response.
     *
     * @param string $error
     * @param array  $errorMessages
     * @param int    $code
     * @return JsonResponse
     */
    public function sendError(string $error, array $errorMessages = [], int $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
     * Send a validation error response.
     *
     * @param array $errorMessages
     * @return JsonResponse
     */
    public function sendValidationError(array $errorMessages): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => 'Validation Error',
            'data'    => $errorMessages,
        ];

        return response()->json($response, \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Send an unauthorized error response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function sendUnauthorizedError(string $message = 'Unauthorized'): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        return response()->json($response, \Illuminate\Http\Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Send a forbidden error response.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function sendForbiddenError(string $message = 'Forbidden'): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        return response()->json($response, \Illuminate\Http\Response::HTTP_FORBIDDEN);
    }
}