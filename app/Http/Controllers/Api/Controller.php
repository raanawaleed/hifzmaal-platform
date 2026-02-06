<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="HifzMaal API Documentation",
 *     description="Islamic Family Finance Management Platform API - Manage your family finances according to Islamic principles",
 *     @OA\Contact(
 *         email="support@hifzmaal.com",
 *         name="HifzMaal Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local Development Server"
 * )
 * 
 * @OA\Server(
 *     url="https://api.hifzmaal.com",
 *     description="Production Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your bearer token in the format: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints for user authentication and registration"
 * )
 * 
 * @OA\Tag(
 *     name="Families",
 *     description="Family management - create and manage family accounts"
 * )
 * 
 * @OA\Tag(
 *     name="Family Members",
 *     description="Manage family members and their roles"
 * )
 * 
 * @OA\Tag(
 *     name="Accounts",
 *     description="Bank accounts, wallets, and cash management"
 * )
 * 
 * @OA\Tag(
 *     name="Transactions",
 *     description="Income, expense, and transfer transactions"
 * )
 * 
 * @OA\Tag(
 *     name="Categories",
 *     description="Transaction categories management"
 * )
 * 
 * @OA\Tag(
 *     name="Budgets",
 *     description="Budget creation and tracking"
 * )
 * 
 * @OA\Tag(
 *     name="Bills",
 *     description="Recurring bills and payments management"
 * )
 * 
 * @OA\Tag(
 *     name="Savings Goals",
 *     description="Savings goals for Hajj, education, marriage, etc."
 * )
 * 
 * @OA\Tag(
 *     name="Zakat",
 *     description="Zakat calculation and payment tracking"
 * )
 * 
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard overview and insights"
 * )
 * 
 * @OA\Tag(
 *     name="Accounts",
 *     description="Account management endpoints"
 * )
 */
abstract class Controller
{
    //
} 
 