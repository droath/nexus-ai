<?php

declare(strict_types=1);

namespace Droath\NextusAi\Messages\Concerns;

use Throwable;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

trait ViewSupport
{
    public static function fromView(View $view): self
    {
        try {
            return self::make($view->render());
        } catch (Throwable $e) {
            Log::error($e->getMessage());
        }

        return self::make('');
    }
}
