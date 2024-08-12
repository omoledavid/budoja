<?php

namespace App\Http\Requests;

use App\Models\Restaurant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RestaurantStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'            => ['required', 'string', Rule::unique("restaurants", "name")->ignore($this->restaurant), 'max:191'],
            'description'     => ['required', 'string'],
            'lat'             => ['nullable'],
            'long'            => ['nullable'],
            'opening_time'    => ['nullable'],
            'closing_time'    => ['nullable'],
            'address'         => ['nullable', 'max:200'],
            'current_status'  => ['sometimes', 'numeric'],
            'delivery_status' => ['nullable', 'numeric'],
            'pickup_status'   => ['nullable', 'numeric'],
            'table_status'    => ['nullable', 'numeric'],
            'image'           => 'image|mimes:jpeg,png,jpg|max:5098',
        ];
    }

    public function attributes()
    {
        return [
            'name'            => trans('validation.attributes.name'),
            'description'     => trans('validation.attributes.description'),
            'lat'             => trans('validation.attributes.lat'),
            'long'            => trans('validation.attributes.long'),
            'opening_time'    => trans('validation.attributes.opening_time'),
            'closing_time'    => trans('validation.attributes.closing_time'),
            'address'         => trans('validation.attributes.address'),
            'current_status'  => trans('validation.attributes.current_status'),
            'image'           => trans('validation.attributes.image'),
        ];
    }
}
