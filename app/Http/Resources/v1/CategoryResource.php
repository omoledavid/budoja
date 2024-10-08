<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            "id"          => $this->id,
            "title"       => $this->name,
            // "slug"        => $this->slug,
            "description" => strip_tags($this->description),
            "image"       => $this->image,
        ];
    }
}
