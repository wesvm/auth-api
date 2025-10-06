<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

function jsonResponse(int $status = 200, string $message = 'Ok', mixed $data = null, array $errors = []): JsonResponse
{
    $response = [
        'status' => $status,
        'message' => $message,
    ];

    if (!empty($data)) {
        $response['data'] = $data;
    }

    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    return response()->json($response, $status);
}

/**
 * @throws Throwable
 */
function transactional(Closure $callback)
{
    DB::beginTransaction();

    try {
        $result = $callback();
        DB::commit();
        return $result;
    } catch (Throwable $exception) {
        DB::rollBack();

        Log::error('Transaction failed', [
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        throw $exception;
    }
}
