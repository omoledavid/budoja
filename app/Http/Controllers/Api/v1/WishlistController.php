<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\WishlistResources;
use App\Models\MenuItem;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function index()
    {
        $user_id    = auth()->user()->id ?? null;
        $notify[] = [];

        if ($user_id != null) {
            $wishlist_data = Wishlist::where('user_id', $user_id)
                ->with(['product' => function($q){
                    $q->with('restaurantSearch');
                }])
                ->get();
        } else {
            $s_id       = session()->get('session_id');
            if (!$s_id) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'something went wrong',
                    ],
                    400
                );
            }
            $wishlist_data = Wishlist::where('session_id', $s_id)
                ->with('product')
                ->get();
        }
        return response()->json([
            'status' => true,
            'data' => WishlistResources::collection($wishlist_data)
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',

        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $product = MenuItem::where('id', $request->product_id)->first();
        if(!$product){
            return response()->json([
                'status' => false,
                'message' => 'Product do not exist',
            ], 404);
        }

        $user_id = auth()->user()->id ?? null;

        $s_id = session()->get('session_id');
        if ($s_id == null) {
            session()->put('session_id', uniqid());
            $s_id = session()->get('session_id');
        }

        if ($user_id != null) {
            $wishlist = Wishlist::where('user_id', $user_id)
                ->where('product_id', $request->product_id)
                ->first();
        } else {

            $wishlist = Wishlist::where('session_id', $s_id)
                ->where('product_id', $request->product_id)
                ->first();
        }

        if ($wishlist) {
            return response()->json(['error' => 'Already in the wish list']);
        } else {
            $wishlist = new Wishlist();
            $wishlist->user_id    = auth()->user()->id ?? null;
            $wishlist->session_id = $s_id;
            $wishlist->product_id = $request->product_id;
            $wishlist->save();
        }
        $wishlist = session()->get('wishlist');

        $wishlist[$request->product_id] = [
            "id" => $request->product_id,
        ];

        session()->put('wishlist', $wishlist);
        return response()->json([
            'success' => 'Added to Wishlist',
            'data' => $wishlist,
        ]);
    }

    public function destroy($id)
    {
        if($id==0){
            $user_id    = auth()->user()->id??null;
            if($user_id != null){
                $wishlist = Wishlist::where('user_id', $user_id);
            }else{
                $s_id       = session()->get('session_id');
                if(!$s_id){
                    abort(404);
                }
                $wishlist = Wishlist::where('session_id', $s_id);
            }

        }else{
            $wishlist   = Wishlist::findorFail($id);
            $product_id = $wishlist->product_id;
            $wl         = session()->get('wishlist');
            unset($wl[$product_id]);
            session()->put('wishlist', $wl);
        }
        Artisan::call('cache:clear');
        if($wishlist) {
            $wishlist->delete();
            return response()->json(['success' => 'Deleted From Wishlist']);
        }

        return response()->json(['error' => 'This product isn\'t available in your wishlsit']);
    }
}
