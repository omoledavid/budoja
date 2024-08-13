<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MenuItemResource extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function toArray( $request )
    {
        return [
            "id"                => $this->id,
            "name"              => $this->name,
            "unit_price"        => $this->unit_price,
            "discount_price"    => $this->discount_price,
            "cooking_time "    => $this->cooking_time ?? null    ,
            "image"             => $this->image,
            "description"       => strip_tags($this->description),
            "restaurant_id"     => $this->restaurant_id,
            "category"          => CategoryResource::collection($this->categories),
            "restaurant"        => $this->restaurantSearch ? [
                "id"            => $this->restaurantSearch['id'] ?? null,
                "name"          => $this->restaurantSearch['name'] ?? null,
                "description"   => $this->restaurantSearch['description'] ?? null,
                "address"       => $this->restaurantSearch['address'] ?? null,
                "image"         => $this->restaurantSearch['image'] ?? 'default-image-path.jpg',
            ] : null,  // Return null if restaurantSearch is null
        ];
        
        
        
    }

}
