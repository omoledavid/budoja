<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\FrontendController;
use App\Models\Cart;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\MenuItemOption;
use App\Traits\ApiResponse;
use App\Models\MenuItemVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends FrontendController
{
    use ApiResponse;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth:api');
    }

    public function index()
    {
        $cart = Cart::where('user_id', auth()->id())->with(['product' => function ($query) {
            $query->select('id', 'restaurant_id', 'name', 'description', 'unit_price', 'discount_price')->with(['restaurant' => function ($query) {
                $query->select('id', 'user_id', 'name', 'description', 'address');
            }]);
        }])->get();
        if ($cart) {
            $cartItems = $cart->map(function ($item) {
                return [
                    'cart' => [
                        'id' => $item->id ?? 'N/A',
                        'user_id' => $item->user_id ?? 'N/A',
                        'qty' => $item->qty ?? 'N/A',
                        'product_id' => $item->product_id ?? 'N/A',
                        'product' => [
                            'id' => $item->product->id ?? 'N/A',
                            'name' => $item->product->name ?? 'N/A',
                            'description' => $item->product->description ?? 'N/A',
                            'unit_price' => $item->product->unit_price ?? 'N/A',
                            'discount_price' => $item->product->discount_price ?? 'N/A',
                            'restaurant_id' => $item->product->restaurant_id ?? 'N/A',
                            'image' => $item->product->image,  // Accessing the image attribute
                            'restaurant' => [
                                'id' => $item->product->restaurant->id ?? 'N/A',
                                'name' => $item->product->restaurant->name ?? 'N/A',
                                'description' => $item->product->restaurant->description ?? 'N/A',
                                'address' => $item->product->restaurant->address ?? 'N/A',
                            ]
                        ]
                    ]
                ];
            });
            return response()->json([
                'status' => true,
                'data' => [
                    'cart' => $cartItems,
                ]
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'something is wrong'
        ], 400);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:menu_items,id',
            'qty'        => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth()->user();
        $resturantExist = Cart::where('user_id', $user->id)->first();

        if ($resturantExist) {
            $cartProduct = MenuItem::find($resturantExist->product_id);
            $requestProduct = MenuItem::find($request->product_id);

            if ($cartProduct && $requestProduct) {
                if ($cartProduct->restaurant_id != $requestProduct->restaurant_id) {
                    return response()->json([
                        'message' => 'You can only purchase an item from one restaurant at a time.',
                    ], 400);
                }
            }
        }

        $cartData = Cart::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartData) {
            $cartData->qty = $request->qty;
            $cartData->save();
            return response()->json([
                'status'  => true,
                'message' => 'Cart updated',
                'data'    => $cartData
            ], 200);
        } else {
            $cart = new Cart;
            $cart->user_id = $user->id;
            $cart->product_id = $request->product_id;
            $cart->qty = $request->qty;
            $cart->save();
            return response()->json([
                'status'  => true,
                'message' => 'Product added to cart',
                'data'    => $cart
            ], 200);
        }
    }


    public function remove($id)
    {
        $cart_item = Cart::findorFail($id);
        $cart_item->delete();
        return $this->successresponse(['status' => 200, 'message' => 'Removed successfully']);
    }
}
