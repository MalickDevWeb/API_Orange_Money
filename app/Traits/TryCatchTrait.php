<?php

namespace App\Traits;

trait TryCatchTrait
{
    /**
     * Execute a callable with try-catch and return appropriate response
     *
     * @param callable $callback
     * @param string|null $successMessage
     * @return mixed
     */
    protected function tryCatch(callable $callback, ?string $successMessage = null)
    {
        try {
            $result = $callback();

            if ($successMessage) {
                return $this->successResponse($result, $successMessage);
            }

            return $result;
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Execute a callable with parameters and try-catch
     *
     * @param callable $callback
     * @param array $params
     * @param string|null $successMessage
     * @return mixed
     */
    protected function tryCatchWithParams(callable $callback, array $params = [], ?string $successMessage = null)
    {
        try {
            $result = call_user_func_array($callback, $params);

            if ($successMessage) {
                return $this->successResponse($result, $successMessage);
            }

            return $result;
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
