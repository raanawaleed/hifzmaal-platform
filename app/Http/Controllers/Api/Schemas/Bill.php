<?php

namespace App\Http\Controllers\Api\Schemas;

/**
 * @OA\Schema(
 *     schema="Bill",
 *     type="object",
 *     title="Bill",
 *     required={"id", "name", "type", "amount", "due_date"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Electricity Bill"),
 *     @OA\Property(property="type", type="string", enum={"electricity", "gas", "water", "internet", "mobile", "rent", "school_fees", "other"}),
 *     @OA\Property(property="amount", type="number", format="float", example=3500.00),
 *     @OA\Property(property="average_amount", type="number", format="float"),
 *     @OA\Property(property="due_date", type="string", format="date"),
 *     @OA\Property(property="frequency", type="string", enum={"monthly", "quarterly", "yearly"}),
 *     @OA\Property(property="is_recurring", type="boolean"),
 *     @OA\Property(property="auto_pay", type="boolean"),
 *     @OA\Property(property="provider", type="string"),
 *     @OA\Property(property="account_number", type="string"),
 *     @OA\Property(property="split_members", type="array", @OA\Items(type="integer")),
 *     @OA\Property(property="reminder_days", type="integer"),
 *     @OA\Property(property="status", type="string", enum={"pending", "paid", "overdue"}),
 *     @OA\Property(property="last_paid_date", type="string", format="date", nullable=true),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string")
 *     ),
 *     @OA\Property(property="is_due", type="boolean"),
 *     @OA\Property(property="is_overdue", type="boolean"),
 *     @OA\Property(property="days_until_due", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Bill
{
    //
}