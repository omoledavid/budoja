<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\CurrentStatus;
use App\Http\Resources\v1\PopularRestaurantResource;
use App\Models\TimeSlot;
use App\Enums\TableStatus;
use App\Models\Restaurant;
use App\Enums\PickupStatus;
use App\Enums\RatingStatus;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Enums\DeliveryStatus;
use App\Enums\RestaurantStatus;
use App\Models\RestaurantRating;
use App\Http\Services\RatingsService;
use App\Http\Services\RestaurantService;
use App\Http\Resources\v1\RatingResource;
use App\Http\Controllers\BackendController;
use App\Http\Resources\v1\MenuItemResource;
use App\Http\Resources\v1\RestaurantResource;
use App\Models\MenuItem;

class   SearchController extends BackendController
{
    use ApiResponse;
    protected  $restaurantService;

    public function __construct(RestaurantService $restaurantService)
    {
        parent::__construct();
        $this->data['siteTitle'] = 'Restaurants';
        $this->middleware('auth:api');
        $this->restaurantService = $restaurantService;
    }
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
    $query = $request->input('query');
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

    if($product){
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


    public function getallrestaurant($name, $expedition)
    {
        $queryArray = [];
        $queryArray['status'] = RestaurantStatus::ACTIVE;
        $queryArray['current_status'] = CurrentStatus::YES;

        if (!blank($expedition)) {
            if ($expedition == 'delivery') {
                $queryArray['delivery_status'] = DeliveryStatus::ENABLE;
            } elseif ($expedition == 'pickup') {
                $queryArray['pickup_status'] = PickupStatus::ENABLE;
            } elseif ($expedition == 'table') {
                $queryArray['table_status'] = TableStatus::ENABLE;
            }
        }


        $current_time = now()->format('H:i');

        if (!blank($queryArray) && !blank($name)) {
            $restaurants = Restaurant::where([['opening_time', '>', 'closing_time'], ['opening_time', '<', $current_time]])
                ->Orwhere([['opening_time', '<', 'closing_time'], ['opening_time', '<', $current_time], ['closing_time', '>', $current_time]])
                ->where($queryArray)->where('name', 'like', '%' . $name . '%')
                ->descending()->select()->get();
        } elseif (!blank($expedition)) {
            $restaurants = Restaurant::where([['opening_time', '>', 'closing_time'], ['opening_time', '<', $current_time]])
                ->Orwhere([['opening_time', '<', 'closing_time'], ['opening_time', '<', $current_time], ['closing_time', '>', $current_time]])
                ->where($queryArray)
                ->descending()->select()->get();
        } elseif (!blank($name)) {
            $restaurants = Restaurant::where('name', 'like', '%' . $name . '%')
                ->where([['opening_time', '>', 'closing_time'], ['opening_time', '<', $current_time]])
                ->Orwhere([['opening_time', '<', 'closing_time'], ['opening_time', '<', $current_time], ['closing_time', '>', $current_time]])
                ->descending()->select()->get();
        } else {
            $restaurants = Restaurant::where([['opening_time', '>', 'closing_time'], ['opening_time', '<', $current_time]])
                ->Orwhere([['opening_time', '<', 'closing_time'], ['opening_time', '<', $current_time], ['closing_time', '>', $current_time]])
                ->where($queryArray)
                ->descending()->select()->get();
        }

        return  $restaurants;
    }
}
