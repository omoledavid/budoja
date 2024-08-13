<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class WishlistResources extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function toArray( $request )
    {
        return [
            "id"                => $this->id ?? 'N/A',
            "user_id"              => $this->user_id ?? 'N/A',
            "session_id"              => $this->session_id ?? 'N/A',
            "product_id"              => $this->product_id ?? 'N/A',
            "created_at"              => $this->created_at ?? 'N/A',
            "product"              => new MenuItemResource($this->product),
        ];
        
        
        
    }

}
