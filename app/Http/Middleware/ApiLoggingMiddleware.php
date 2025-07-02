<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Log as LogModel;
use App\Enums\ActionType;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // Only log POST, PUT, PATCH, DELETE requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Get the response
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000); // Convert to milliseconds

        try {
            $this->logRequest($request, $response, $duration);
        } catch (\Exception $e) {
            // Don't let logging errors break the API response
            Log::error('API Logging failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Log the API request to the database
     */
    private function logRequest(Request $request, Response $response, int $duration): void
    {
        $user = Auth::user();
        $method = $request->method();
        $url = $request->fullUrl();
        $path = $request->path();
        
        // Determine action type based on HTTP method and path
        $actionType = $this->determineActionType($method, $path);
        
        // Determine entity type and ID from the URL
        $entityInfo = $this->extractEntityInfo($path);
        
        // Get old values for updates and deletes
        $oldValues = null;
        if (in_array($method, ['PUT', 'PATCH', 'DELETE']) && $entityInfo['id']) {
            $oldValues = $this->getOldValues($entityInfo['type'], $entityInfo['id']);
        }
        
        // Get new values (request data)
        $newValues = $this->sanitizeRequestData($request->all());
        
        // Determine status based on response
        $status = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ? 'SUCCESS' : 'FAILURE';
        
        // Create log entry
        LogModel::create([
            'user_id' => $user ? $user->id : null,
            'username' => $user ? $user->name : 'System',
            'session_id' => $this->getSessionId($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'action_type' => $actionType,
            'entity_type' => $entityInfo['type'],
            'entity_id' => $entityInfo['id'] ?? '',
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'additional_details' => $this->getAdditionalDetails($request, $response),
            'status' => $status,
            'error_message' => $status === 'FAILURE' ? $this->getErrorMessage($response) : null,
            'performed_at' => now(),
            'request_url' => $url,
            'request_method' => $method,
            'referrer_url' => $request->header('referer'),
            'duration' => $duration,
            'system_event' => false,
        ]);
    }

    /**
     * Determine action type based on HTTP method and path
     */
    private function determineActionType(string $method, string $path): string
    {
        $pathParts = explode('/', trim($path, '/'));
        
        // Handle specific cases
        if (str_contains($path, 'login')) {
            return ActionType::LOGIN_ACTION;
        }
        
        if (str_contains($path, 'logout')) {
            return ActionType::LOGOUT_ACTION;
        }
        
        if (str_contains($path, 'password')) {
            return ActionType::PASSWORD_RESET_ACTION;
        }
        
        if (str_contains($path, 'import')) {
            return ActionType::CREATE_ACTION; // Import is a create action
        }
        
        if (str_contains($path, 'export')) {
            return ActionType::EXPORT_ACTION;
        }
        
        // Default mapping based on HTTP method
        switch ($method) {
            case 'POST':
                return ActionType::CREATE_ACTION;
            case 'PUT':
            case 'PATCH':
                return ActionType::UPDATE_ACTION;
            case 'DELETE':
                return ActionType::DELETE_ACTION;
            default:
                return ActionType::READ_ACTION;
        }
    }

    /**
     * Extract entity type and ID from URL path
     */
    private function extractEntityInfo(string $path): array
    {
        $pathParts = explode('/', trim($path, '/'));
        
        // Map common entity types
        $entityMap = [
            'users' => 'User',
            'lecturers' => 'Lecturer',
            'students' => 'Student',
            'programs' => 'Program',
            'evaluations' => 'Evaluation',
            'nominations' => 'Nomination',
            'assignments' => 'Assignment',
            'roles' => 'Role',
            'imports' => 'Import',
            'reports' => 'Report',
            'settings' => 'Setting',
        ];
        
        $entityType = 'Unknown';
        $entityId = null;
        
        foreach ($pathParts as $index => $part) {
            if (isset($entityMap[$part])) {
                $entityType = $entityMap[$part];
                // Check if next part is an ID (numeric)
                if (isset($pathParts[$index + 1]) && is_numeric($pathParts[$index + 1])) {
                    $entityId = $pathParts[$index + 1];
                }
                break;
            }
        }
        
        return [
            'type' => $entityType,
            'id' => $entityId
        ];
    }

    /**
     * Get old values for updates and deletes
     */
    private function getOldValues(string $entityType, string $entityId): ?array
    {
        try {
            // Map entity types to correct model namespaces
            $modelMap = [
                'User' => 'App\\Models\\User',
                'Lecturer' => 'App\\Modules\\UserManagement\\Models\\Lecturer',
                'Student' => 'App\\Modules\\Student\\Models\\Student',
                'Program' => 'App\\Modules\\Program\\Models\\Program',
                'Evaluation' => 'App\\Modules\\Evaluation\\Models\\Evaluation',
                'Nomination' => 'App\\Modules\\Evaluation\\Models\\Nomination',
                'Assignment' => 'App\\Modules\\Evaluation\\Models\\Assignment',
                'Role' => 'App\\Modules\\UserManagement\\Models\\Role',
            ];
            
            $modelClass = $modelMap[$entityType] ?? "App\\Models\\{$entityType}";
            
            if (class_exists($modelClass)) {
                $model = $modelClass::find($entityId);
                return $model ? $model->toArray() : null;
            }
        } catch (\Exception $e) {
            // Silently fail if we can't get old values
        }
        
        return null;
    }

    /**
     * Sanitize request data to remove sensitive information
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***HIDDEN***';
            }
        }
        
        return $data;
    }

    /**
     * Get additional details about the request
     */
    private function getAdditionalDetails(Request $request, Response $response): ?string
    {
        $details = [];
        
        // Add query parameters if any
        if ($request->query()) {
            $details['query_params'] = $request->query();
        }
        
        // Add headers if needed
        $importantHeaders = ['content-type', 'accept', 'authorization'];
        foreach ($importantHeaders as $header) {
            if ($request->header($header)) {
                $details['headers'][$header] = $request->header($header);
            }
        }
        
        return !empty($details) ? json_encode($details) : null;
    }

    /**
     * Get session ID safely for both session and JWT requests
     */
    private function getSessionId(Request $request): string
    {
        try {
            // Try to get session ID if session store is available
            if ($request->hasSession()) {
                return $request->session()->getId() ?? 'api';
            }
        } catch (\Exception $e) {
            // Session store not available (JWT requests)
        }
        
        // For JWT requests, use a combination of user ID and timestamp
        $user = Auth::user();
        if ($user) {
            return 'jwt_' . $user->id . '_' . time();
        }
        
        return 'api_' . time();
    }

    /**
     * Get error message from response
     */
    private function getErrorMessage(Response $response): ?string
    {
        try {
            $content = $response->getContent();
            $data = json_decode($content, true);
            
            if (isset($data['message'])) {
                return $data['message'];
            }
            
            if (isset($data['error'])) {
                return $data['error'];
            }
            
            return $content;
        } catch (\Exception $e) {
            return 'Unable to extract error message';
        }
    }
} 