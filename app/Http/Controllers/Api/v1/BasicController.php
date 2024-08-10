<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\MenuItemResource;
use App\Http\Services\MenuItemService;
use App\Models\MenuItem;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BasicController extends Controller
{
    use ApiResponse;
    protected  $menuItemService;
    public function __construct(MenuItemService $menuItemService)
    {
        // parent::__construct();  
        $this->menuItemService = $menuItemService;
    }
    public function index(){
        $products = MenuItem::where('status', 5)->get();

        $data = MenuItemResource::collection($products);
    
        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
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
}
