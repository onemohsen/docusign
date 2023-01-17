<?php

declare(strict_types=1);

namespace App\Services\Actions\Common;

use Illuminate\Validation\ValidationException;

class ValidationThrow
{
    public static function handle(array $messages)
    {
        throw ValidationException::withMessages($messages);
    }
}
