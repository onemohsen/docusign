<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Actions\Template\SignTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class TemplateController extends Controller
{
    public function sign(Request $request)
    {
        try {
            $result = SignTemplate::handle($request);
            return response()->json(['data' => $result, 'message' => 'sign sent successfully'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            if ($th instanceof ValidationException) {
                return response()->json(['errors' => $th->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return response()->json(['message' => "sign can't be send"], Response::HTTP_FAILED_DEPENDENCY);
        }
    }
}
