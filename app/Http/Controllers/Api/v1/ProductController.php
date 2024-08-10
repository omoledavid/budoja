<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MenuItemRequest;
use App\Http\Resources\v1\MenuItemResource;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Services\MenuItemService;
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
    public function index(){
        // Product is menuitem
        $user = auth()->user();
        $products = MenuItem::where('creator_id', $user->id)->select('id','restaurant_id', 'name', 'description', 'status', 'created_at')->get();

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
        if (!$validator->fails()){

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

        }else {
            return response()->json([
                'code'  => 422,
                'error' => $validator->errors(),
            ], 422);
        }

    }
    public function show($id)
    {
        try{
            $menuitem= new MenuItemResource($this->menuItemService->show($id));
            return $this->successResponse(['status'=>200,'data'=>$menuitem]);
        } catch(\Exception $e){
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
        return $this->errorResponse('Invalid request',401);
    }
    
}
