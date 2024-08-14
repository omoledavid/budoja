<?php

/**
 * Created by PhpStorm.
 * User: dipok
 * Date: 18/4/20
 * Time: 2:07 PM
 */

namespace App\Http\Resources\v1;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderApiResource extends JsonResource
{
    public function toArray($request)
    {
        return   [
            'id'               => $this->id,
            'order_code'       => $this->order_code,
            'user_id'          => (int)$this->user_id,
            'total'            => $this->total,
            'sub_total'        => $this->sub_total,
            'status'           => (int)$this->status,
            'order_type'       =>  (int)$this->order_type,
            'order_type_name'  =>  $this->getOrderType,
            'payment_status'   => (int)$this->payment_status,
            'payment_method'   => (int)$this->payment_method,
            'payment_method_name'    => trans('payment_method.' . $this->payment_method),
            'paid_amount'      => $this->paid_amount,
            'address'          => orderAddress($this->address),
            'invoice_id'       => $this->invoice_id,
            'restaurant_id'    => (int)$this->restaurant_id,
            'product_received' => (int)$this->product_received,
            'mobile'           => $this->mobile,
            'misc'             => $this->misc,
            'created_at'       => $this->created_at->format('d M Y, h:i A'),
            'updated_at'       => $this->updated_at->format('d M Y, h:i A'),
            'time_format'           => $this->created_at->diffForHumans(),
            'date'                  => Carbon::parse($this->created_at)->format('d M Y'),
            'items'            => OrderItemsResource::collection(
                $this->whenLoaded('items')
            ),
            'customer'         => new UserResource($this->user),
            'restaurant'             => new RestaurantResource($this->restaurant),
        ];

    }
}
