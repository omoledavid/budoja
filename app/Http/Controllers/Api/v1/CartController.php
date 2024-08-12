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
        $cart = Cart::where('user_id', auth()->id())->with(['product' => function($query){
            $query->select('id', 'restaurant_id', 'name', 'description', 'unit_price', 'discount_price')->with(['restaurant' => function($query){
                $query->select('id', 'user_id', 'name', 'description', 'address');
            }]);
        }])->get();
        if ($cart) {
            $cartItems = $cart->map(function ($item) {
                return [
                    'cart' => [
                        'id' => $item->id,
                        'user_id' => $item->user_id,
                        'qty' => $item->qty,
                    ],
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'description' => $item->product->description,
                        'unit_price' => $item->product->unit_price,
                        'discount_price' => $item->product->discount_price,
                        'image' => $item->product->image,  // Accessing the image attribute
                        'restaurant' => [
                            'id' => $item->product->restaurant->id,
                            'name' => $item->product->restaurant->name,
                            'description' => $item->product->restaurant->description,
                            'address' => $item->product->restaurant->address,
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

    private function cartInfo($menuItemId, $variationId = null)
    {
        $product = [];
        $carts = Cart::content()->toArray();
        if (is_array($carts)) {
            foreach ($carts as $cart) {
                if (count($cart['options']['variation']) > 0) {
                    if (isset($product[$cart['options']['menuItem_id']]['single'])) {
                        $product[$cart['options']['menuItem_id']]['single'] += $cart['qty'];
                    } else {
                        $product[$cart['options']['menuItem_id']]['single'] = $cart['qty'];
                    }
                    if (isset($product[$cart['options']['menuItem_id']]['variation'][$cart['options']['variation']['id']])) {
                        $product[$cart['options']['menuItem_id']]['variation'][$cart['options']['variation']['id']] += $cart['qty'];
                    } else {
                        $product[$cart['options']['menuItem_id']]['variation'][$cart['options']['variation']['id']] = $cart['qty'];
                    }
                } else {
                    if (isset($product[$cart['options']['menuItem_id']]['single'])) {
                        $product[$cart['options']['menuItem_id']]['single'] += $cart['qty'];
                    } else {
                        $product[$cart['options']['menuItem_id']]['single'] = $cart['qty'];
                    }
                    $product[$cart['options']['menuItem_id']]['variation'] = [];
                }
            }
        }

        if ($variationId) {
            $quantity = isset($product[$menuItemId]['variation'][$variationId]) ? $product[$menuItemId]['variation'][$variationId] : 0;
        } else {
            $quantity = isset($product[$menuItemId]['single']) ? $product[$menuItemId]['single'] : 0;
        }

        return $quantity;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:menu_items,id',
            'qty'  => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $user = auth()->user();
        $product = MenuItem::where('id', $request->product_id)->get();
        $cartData = Cart::where('user_id', $user->id)->where('product_id', $request->product_id)->first();
        if ($cartData) {
            $cartData->qty = $request->qty;
            $cartData->save();
            return response()->json([
                'status' => true,
                'message' => 'cart updated',
                'data' => $cartData
            ], 200);
        } else {

            $cart = new Cart;
            $cart->user_id = $user->id;
            $cart->product_id = $request->product_id;
            $cart->qty = $request->qty;
            $cart->save();
            return response()->json([
                'status' => true,
                'data' => $cart
            ], 200);
        }
    }

    public function remove($id)
    {
        $cart_item = Cart::findorFail($id);
        $cart_item->delete();
        return $this->successresponse(['status' => 200, 'message' => 'Removed successfully']);
    }

    public function quantity(Request $request)
    {
        $validation = [
            'rowId'          => 'required',
            'quantity'       => 'required|numeric',
            'deliveryCharge' => 'required',
        ];
        $validator = Validator::make($request->all(), $validation);
        if (!$validator->fails()) {
            $carts = Cart::content()->toArray();
            if (isset($carts[$request->rowId])) {
                $menuItemId   = $carts[$request->rowId]['options']['menuItem_id'];
                $variationId = (isset($carts[$request->rowId]['options']['variation']['id']) ? $carts[$request->rowId]['options']['variation']['id'] : null);
                $restaurantId      = $carts[$request->rowId]['options']['restaurant_id'];
                $cartQuantity =  $carts[$request->rowId]['qty'];
                $menuItem     = MenuItem::find($menuItemId);
                if (!blank($menuItem)) {
                    Cart::update($request->rowId, $request->quantity);
                    echo json_encode([
                        'status'     => true,
                        'price'      => currencyFormat(Cart::get($request->rowId)->price * Cart::get($request->rowId)->qty),
                        'totalPrice' => currencyFormat(Cart::totalFloat()),
                        'total'      => currencyFormat(Cart::totalFloat() + $request->deliveryCharge)
                    ]);
                }
            } else {
                echo json_encode([
                    'status'  => false,
                    'message' => 'cart not found.'
                ]);
            }
        } else {
            echo json_encode([
                'status'  => false,
                'message' => 'something wrong'
            ]);
        }
    }
}
