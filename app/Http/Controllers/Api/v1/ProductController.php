<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MenuItemRequest;
use App\Http\Resources\v1\MenuItemResource;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Services\MenuItemService;
use App\Models\Restaurant;
use App\Traits\ApiResponse;

class ProductController extends Controller
{
    use ApiResponse;
    protected  $menuItemService;
    public function __construct(MenuItemService $menuItemService)
    {
        // parent::__construct();  
        $this->middleware('auth:api');
        $this->menuItemService = $menuItemService;
    }
    public function index()
    {
        // Product is menuitem
        $user = auth()->user();
        $products = MenuItem::where('creator_id', $user->id)->where('status', 5)->get();

        $data = MenuItemResource::collection($products);

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validator = new MenuItemRequest();
        $validator = Validator::make($request->all(), $validator->rules());
        if (!$validator->fails()) {
            $restuarant = Restaurant::where('id', $request->restaurant_id)->where('status', RestaurantStatus::ACTIVE)->first();
            if (!$restuarant) {
                return response()->json([
                    'status' => false,
                    'message' => 'The resturant is currently inactive',
                ]);
            }

            try {
                DB::beginTransaction();
                $menuItem = $this->menuItemService->store($request);
                $this->menuItemService->media($menuItem);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ]);
            }
            return response()->json([
                'status' => true,
                'data' => $menuItem
            ]);
        } else {
            return response()->json([
                'code'  => 422,
                'error' => $validator->errors(),
            ], 422);
        }
    }
    public function show($id)
    {
        try {
            $menuitem = new MenuItemResource($this->menuItemService->show($id));
            return $this->successResponse(['status' => 200, 'data' => $menuitem]);
        } catch (\Exception $e) {
            return response()->json([
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $menuItem = MenuItem::where(['id' => $id, 'restaurant_id' => auth()->user()->restaurant->id])->first();
        if (!blank($menuItem)) {
            $validator = new MenuItemRequest();
            $validator = Validator::make($request->all(), $validator->rules());
            if ($validator->fails()) {
                return response()->json([
                    'code'  => 422,
                    'error' => $validator->errors(),
                ], 422);
            }

            try {
                DB::beginTransaction();
                $this->menuItemService->update($request, $menuItem);
                $this->menuItemService->updateMedia($menuItem);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ]);
            }
            return response()->json([
                'status' => true,
                'data' => new MenuItemResource($menuItem)
            ]);
        }
        return $this->errorResponse('Invalid request', 401);
    }
    public function destroy($id)
    {
        // Use findOrFail to get the MenuItem by its ID
        try {
            $product = MenuItem::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        // Delete the product
        // $product->delete();
        $product->status = 10;
        $product->save();

        // Return a JSON response
        return response()->json([
            'message' => 'Product Deleted successfully'
        ]);
    }
}
