<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\CategoryStatus;
use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\CategoryResource;
use App\Http\Resources\v1\MenuItemResource;
use App\Http\Resources\v1\RestaurantResource;
use App\Http\Services\MenuItemService;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Restaurant;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
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
    public function index()
    {
        return dd(session()->all());
        $products = MenuItem::where('status', 5)->get();

        $data = MenuItemResource::collection($products);

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
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
    public function search(Request $request)
    { {
            $query = $request->input('name');
            $category = $request->input('category');
            $restaurant = $request->input('restaurant');
            $city = $request->input('city');
            $state = $request->input('state');
            $product = $request->input('product');


            // Initialize the query for Restaurant
            $restaurantsQuery = Restaurant::query();
            $productQuery = MenuItem::query();

            // Filter by Restaurant name or description if provided
            // if ($query) {
            //     $restaurantsQuery->where('name', 'LIKE', "%{$query}%")
            //         ->orWhere('description', 'LIKE', "%{$query}%")->orWhere('address', 'LIKE', "%{$query}%");
            // }
            if ($query) {
                $productQuery->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orwhereHas('categories', function ($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%");
                    });
            }

            // Filter by Category if provided (assuming a restaurant has a category relation)
            if ($category) {
                $productQuery->whereHas('categories', function ($q) use ($category) {
                    $q->where('name', 'LIKE', "%{$category}%");
                });
            }
            if ($restaurant) {
                $productQuery->whereHas('restaurantSearch', function ($q) use ($restaurant) {
                    $q->where('name', 'LIKE', "%{$restaurant}%");
                });
            }

            // Filter by City if provided
            if ($city) {
                $restaurantsQuery->where('city', 'LIKE', "%{$city}%");
            }

            // Filter by State if provided
            if ($state) {
                $restaurantsQuery->where('state', 'LIKE', "%{$state}%");
            }

            // Include related MenuItems and filter them by name or description
            $restaurantsQuery->with(['menuItems' => function ($q) use ($query) {
                if ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%");
                }
            }]);

            if ($product) {
                $products = $productQuery->where('name', 'LIKE', "%{$product}%");
            }

            // Execute the query and get the results
            $restaurants = $restaurantsQuery->get();
            $products = $productQuery->get();
            // return $products;
            // return  RestaurantResource::collection($restaurants);

            return response()->json([
                'status' => true,
                'data' =>  MenuItemResource::collection($products), // Re-index the collection
            ]);
        }
    }
    public function getRestaurant (){
        $restaurant = Restaurant::where('status', RestaurantStatus::ACTIVE)->get();
        return response()->json([
            'status' => true,
            'data' => RestaurantResource::collection($restaurant)
        ]);
    }
    public function categories(Request $request)
    {

        try{
            $data = Category::where('status', CategoryStatus::ACTIVE)->get();
            return response()->json([
                'status' => true,
                'data' => CategoryResource::collection($data)
            ]);
        } catch (\Exception $e){
            return response()->json([
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);
        }
    }
    public function info()
    {
        $terms = Page::where('slug', 'terms-conditions')->select('description')->get();
        $privacy = Page::where('slug', 'privacy')->select('description')->get();
        $about = Page::where('slug', 'about-us')->select('description')->get();
        return response()->json([
            'status' => true,
            'policy' => $privacy->pluck('description'),
            'about' => $about->pluck('description'),
            'terms_and_conditions' => $terms->pluck('description')
        ]);
    }
    public function keys(): JsonResponse
    {
        $stripeSecret = Setting::where('key', 'stripe_secret')->first()->value;
        $stripeKey = Setting::where('key', 'stripe_key')->first()->value;
        return response()->json([
            'status' => true,
            'stripe' => [
                'status' => true,
                'secret' => $stripeSecret,
                'key' => $stripeKey,
            ],
            'paypal' => [
                'status' => true
            ],
        ]);
    }
}
