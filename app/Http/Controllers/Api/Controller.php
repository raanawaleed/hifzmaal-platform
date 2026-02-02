<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="HifzMaal API Documentation",
 *     description="Islamic Family Finance Management Platform API",
 *     @OA\Contact(
 *         email="support@hifzmaal.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints for user authentication"
 * )
 * 
 * @OA\Tag(
 *     name="Families",
 *     description="Family management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Transactions",
 *     description="Transaction management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Budgets",
 *     description="Budget management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Bills",
 *     description="Bill management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Savings Goals",
 *     description="Savings goal management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Zakat",
 *     description="Zakat calculation and payment endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard and insights endpoints"
 * )
 */
class Controller extends BaseController
{
    //
}