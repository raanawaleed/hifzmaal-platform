<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ur' => $this->name_ur,
            'type' => $this->type,
            'icon' => $this->icon,
            'color' => $this->color,
            'is_halal' => $this->is_halal,
            'is_system' => $this->is_system,
            'sort_order' => $this->sort_order,
            'full_name' => $this->getFullName(),
            'parent' => $this->when($this->parent, [
                'id' => $this->parent?->id,
                'name' => $this->parent?->name,
            ]),
            'children_count' => $this->children()->count(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
