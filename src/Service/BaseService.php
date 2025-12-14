<?php

namespace App\Service;

class BaseService
{

    protected array $errors = [];

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function addError(string $message, string $severity, int $code = 400): BaseService
    {
        $this->errors[] = [
            'message' => $message,
            'severity' => $severity,
            'code' => $code
        ];
        return $this;
    }

}
