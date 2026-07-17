<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_type' => $this->subject_type ? class_basename($this->subject_type) : null,
            'subject_id' => $this->subject_id,
            'event' => $this->event,
            'description' => $this->description,
            'causer' => $this->causer ? [
                'id' => $this->causer->id,
                'name' => $this->causer->name,
            ] : null,
            'changes' => $this->attribute_changes,
            'created_at' => $this->created_at,
        ];
    }
}
