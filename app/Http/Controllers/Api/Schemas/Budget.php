<?php

namespace App\Http\Controllers\Api\Schemas;

/**
 * @OA\Schema(
 *     schema="Budget",
 *     type="object",
 *     title="Budget",
 *     required={"id", "name", "amount", "period"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Monthly Groceries Budget"),
 *     @OA\Property(property="amount", type="number", format="float", example=15000.00),
 *     @OA\Property(property="period", type="string", enum={"weekly", "monthly", "yearly"}),
 *     @OA\Property(property="start_date", type="string", format="date"),
 *     @OA\Property(property="end_date", type="string", format="date"),
 *     @OA\Property(property="alert_threshold", type="number", format="float", example=80.00),
 *     @OA\Property(property="is_active", type="boolean"),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="color", type="string")
 *     ),
 *     @OA\Property(property="spent_amount", type="number", format="float"),
 *     @OA\Property(property="remaining_amount", type="number", format="float"),
 *     @OA\Property(property="percentage_used", type="number", format="float"),
 *     @OA\Property(property="is_over_budget", type="boolean"),
 *     @OA\Property(property="should_alert", type="boolean"),
 *     @OA\Property(property="days_remaining", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Budget
{
    //
}