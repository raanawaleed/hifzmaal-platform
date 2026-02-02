<?php

namespace App\Http\Controllers\Api\Schemas;

/**
 * @OA\Schema(
 *     schema="Family",
 *     type="object",
 *     title="Family",
 *     required={"id", "name", "currency", "locale"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Ahmed Family"),
 *     @OA\Property(property="currency", type="string", example="PKR"),
 *     @OA\Property(property="locale", type="string", example="en"),
 *     @OA\Property(
 *         property="owner",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="email", type="string")
 *     ),
 *     @OA\Property(property="total_balance", type="number", format="float"),
 *     @OA\Property(property="active_members", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Family
{
    //
}