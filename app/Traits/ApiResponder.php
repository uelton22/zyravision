<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponder
{
    protected function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $response = [];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $response['meta'] = array_merge(['api_version' => 'v1'], $meta);

        return response()->json($response, $status);
    }

    protected function created(mixed $data = null, array $meta = []): JsonResponse
    {
        return $this->success($data, $meta, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function resource(JsonResource $resource, array $meta = [], int $status = 200): JsonResponse
    {
        $data = $resource->resolve();

        return $this->success($data, $meta, $status);
    }

    protected function paginated(ResourceCollection $collection): JsonResponse
    {
        $paginator = $collection->resource;

        $data = $collection->resolve();

        $meta = [
            'api_version' => 'v1',
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        $links = [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];

        return response()->json([
            'data' => $data,
            'meta' => $meta,
            'links' => $links,
        ]);
    }

    protected function error(string $code, string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $response = [
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if (! empty($errors)) {
            $response['error']['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error('NOT_FOUND', $message, 404);
    }

    protected function forbidden(string $message = 'Access denied.'): JsonResponse
    {
        return $this->error('FORBIDDEN', $message, 403);
    }

    protected function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return $this->error('UNAUTHORIZED', $message, 401);
    }

    protected function validationError(array $errors, string $message = 'Validation failed.'): JsonResponse
    {
        return $this->error('VALIDATION_ERROR', $message, 422, $errors);
    }
}
