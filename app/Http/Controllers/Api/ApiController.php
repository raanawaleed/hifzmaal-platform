<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;
use App\Http\Controllers\Controller;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="HifzMaal API",
 *         description="Islamic Family Finance Management Platform",
 *         @OA\Contact(email="support@hifzmaal.com")
 *     ),
 *     @OA\Server(url="http://localhost:8000", description="Local"),
 *     @OA\Server(url="https://api.hifzmaal.com", description="Production"),
 *     security={{"sanctum": {}}}
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(name="Authentication", description="Authentication endpoints")
 * @OA\Tag(name="Families", description="Family management")
 * @OA\Tag(name="Transactions", description="Transaction management")
 * @OA\Tag(name="Budgets", description="Budget management")
 * @OA\Tag(name="Bills", description="Bill management")
 * @OA\Tag(name="Savings Goals", description="Savings goals")
 * @OA\Tag(name="Zakat", description="Zakat calculations")
 * @OA\Tag(name="Dashboard", description="Dashboard insights")
 */
class ApiController extends Controller
{
    //
}