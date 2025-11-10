<?php

namespace App\Traits;

trait ResponseTraits {
    public function successResponse($code = 200, $message = "Opération réussie", $data = null) {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function errorResponse($message = "Une erreur est survenue", $code = 400) {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $code);
    }
    public function paginateResponse($data, $message = "Opération réussie", $code = 200) {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem()
            ]
        ], $code);
    }
}
