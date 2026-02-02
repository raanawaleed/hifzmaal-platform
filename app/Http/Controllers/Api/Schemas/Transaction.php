<?php

namespace App\Http\Controllers\Api\Schemas;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     title="Transaction",
 *     required={"id", "type", "amount", "date"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"income", "expense", "transfer"}, example="expense"),
 *     @OA\Property(property="amount", type="number", format="float", example=1500.50),
 *     @OA\Property(property="currency", type="string", example="PKR"),
 *     @OA\Property(property="date", type="string", format="date", example="2024-01-28"),
 *     @OA\Property(property="description", type="string", example="Grocery shopping"),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}),
 *     @OA\Property(property="needs_approval", type="boolean"),
 *     @OA\Property(property="is_recurring", type="boolean"),
 *     @OA\Property(
 *         property="account",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="type", type="string")
 *     ),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="color", type="string"),
 *         @OA\Property(property="icon", type="string")
 *     ),
 *     @OA\Property(
 *         property="creator",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Transaction
{
    //
}