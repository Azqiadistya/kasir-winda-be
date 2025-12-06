<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'paid' => $this->paid,
            'total' => $this->total,
            'change' => $this->change,
            'cashier' => $this->cashier,
            'items' => SaleItemResource::collection($this->whenLoaded('saleItems')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
