<?php

declare(strict_types=1);

namespace App\Services\Actions\Job;

use Illuminate\Support\Facades\DB;

class DeleteJob
{
    public static function handle()
    {
        DB::table('jobs')->where('attempts', '<=', 0)->delete();

        return true;
    }
}
