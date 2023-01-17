<?php

declare(strict_types=1);

namespace App\Services\Actions\Common;

use Illuminate\Support\Facades\DB;

class DieQuerySql
{
    public static function handle($builder, $response = false): ?string
    {
        DB::enableQueryLog();
        $builder->get();
        $items = DB::getQueryLog();
        $count = count($items);
        $results = $response ? "\n" : "<div style='font-size: 25px;font-weight: bolder;text-align: center;'>we have $count query in this argument : </div><br><br><hr style='color: #c65a5a;'>";
        foreach ($items as $item) {
            $addSlashes = str_replace('?', "'?'", $item['query']);

            $results .= vsprintf(str_replace('?', '%s', $addSlashes), $item['bindings']) . ($response ?"\n\n":"<br><br><hr style='color: #c65a5a;'>");
        }

        $results = str_replace('\\', '\\\\', $results);

        if ($response) return $results;

        echo '<div style="font-size:12px;background-color: #000;    color: #56DB3A;padding: 5px;font: 12px Menlo, Monaco, Consolas, monospace;;font-weight: bold;">';
        print_r($results);
        echo "<br><div style='text-align: center;font-weight: bolder;'>Create By : OneMohsen</div><br></div>";
        dd();
    }
}
