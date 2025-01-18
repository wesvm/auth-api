<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

function jsonResponse(int $status = 200, string $message = 'Ok', $data = [], array $errors = []): JsonResponse
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


function transactional(Closure $callback)
{
    DB::beginTransaction();
    try {
        $result = $callback();
        DB::commit();
        return $result;
    } catch (Exception $exception) {
        DB::rollBack();
        //Log::error($exception->getMessage());
        return jsonResponse(status: 500, message: 'An error occurred.');
    }
}
