<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\v1\RestaurantResource;
use App\Http\Services\PushNotificationService;
use Carbon\Carbon;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Traits\ApiResponse;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\OrderHistory;
use Illuminate\Http\Request;
use App\Models\OrderLineItem;
use App\Enums\OrderTypeStatus;
use App\Models\MenuItemVariation;
use App\Http\Services\FileService;
use App\Http\Services\OrderService;
use App\Notifications\OrderCreated;
use App\Notifications\OrderUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use App\Http\Resources\v1\UserResource;
use App\Http\Resources\v1\OrderResource;
use App\Http\Services\TransactionService;
use Illuminate\Support\Facades\Validator;
use App\Notifications\NewShopOrderCreated;
use App\Http\Resources\v1\OrderApiResource;
use App\Http\Requests\Api\OrderStoreRequest;
use App\Models\Cart;

class OrderController extends Controller
{
    use ApiResponse;

    public $adminBalanceId = 1;

    /**
     * OrderController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }


    public function index()
    {
        $response = Order::where(['user_id' => auth()->user()->id])
            ->select('id', 'user_id', 'total', 'payment_status', 'status', 'paid_amount', 'address', 'mobile', 'restaurant_id', 'product_received', 'payment_method', 'created_at')
            ->orderBy('id', 'desc')
            ->with('items')
            ->get();

        if ($response->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No order found',
            ], 404);
        }

        $response->map(function ($post) {
            // $post['status_name']         = trans('order_status.' . $post->status);
            $post['order_code'] = $post->order_code;
            $post['address'] = orderAddress($post->address);
            // $post['order_type']          = (int)$post->order_type;
            // $post['order_type_name']     = $post->getOrderType;
            $post['payment_method_name'] = trans('payment_method.' . $post->payment_method);

            foreach ($post['items'] as $itemKey => $item) {
                $post['items'][$itemKey]['created_at_convert'] = food_date_format($post->created_at);
                $post['items'][$itemKey]['updated_at_convert'] = food_date_format($post->updated_at);
                $post['items'][$itemKey]['menuItem']['image'] = $item['menuItem']->image;
                $post['items'][$itemKey]['restaurant']['image'] = $item['restaurant']->image;
            }
            return $post;
        });

        return new OrderResource($response);
    }


    public function show($id)
    {
        try {
            $response = Order::where(['id' => $id, 'user_id' => auth()->user()->id])->latest()->with('items', 'invoice.transactions')->first();

            if ($response == null) {
                return $this->successResponse(['status' => 200, 'message' => 'No available orders']);
            }
            $order = new OrderApiResource($response);
            return $this->successResponse(['status' => 200, 'data' => $order]);
        } catch (\Exception $e) {
            return response()->json([
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

        $validator = new OrderStoreRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if (!$validator->fails()) {
            $cart = Cart::where('user_id', auth()->id())->with('product')->get();
            $orderItems = $cart;
            $items = [];
            if (!blank($orderItems)) {
                $i = 0;
                $menuItemVariationId = 0;
                $options = [];
                foreach ($orderItems as $item) {
                    $items[$i] = [
                        'restaurant_id' => $item->product->restaurant_id,
                        'menu_item_id' => $item->product->id,
                        'unit_price' => (float)$item->product->unit_price,
                        'quantity' => (int)$item->qty,
                        'discounted_price' => (float)$item->product->discounted_price,
                        'instructions' => $item->instructions,
                    ];
                    $i++;
                }
            }
            $request->request->add([
                'items' => $items,
                'order_type' => $request->order_type,
                'restaurant_id' => $items[0]['restaurant_id'],
                'user_id' => auth()->user()->id,
                'mobile' => auth()->user()->phone,
                'total' => $items[0]['unit_price'] * count($items),
                'delivery_charge' => $request->delivery_charge,
            ]);


            if (($request->paid_amount == '' || $request->paid_amount == 0) || $request->payment_method == PaymentMethod::CASH_ON_DELIVERY) {
                $request->request->add([
                    'paid_amount' => 0,
                    'payment_method' => PaymentMethod::CASH_ON_DELIVERY,
                    'payment_status' => PaymentStatus::UNPAID
                ]);
            } else {
                $request->request->add([
                    'paid_amount' => $request->paid_amount,
                    'payment_method' => $request->payment_type,
                    'payment_status' => PaymentStatus::PAID
                ]);
            }

            $orderService = app(OrderService::class)->order($request);


            if ($orderService->status) {
                $order = Order::find($orderService->order_id);

                return response()->json([
                    'status' => 200,
                    'message' => 'You order completed successfully.',
                    'data' => $this->orderResponse($order),
                ], 200);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => $orderService->message,
                ], 401);
            }
        } else {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors(),
            ], 422);
        }
    }

    private function orderResponse($order)
    {
        return ['order_id' => $order->id, 'total_amount' => $order->total];
    }

    private function createShow($id)
    {
        $response = Order::where(['id' => $id, 'user_id' => auth()->user()->id])->latest()->with('items', 'invoice.transactions')->first();

        $response->setAttribute('status_name', trans('order_status.' . $response->status));
        $response->setAttribute('created_at_convert', $response->created_at->format('d M Y, h:i A'));
        $response->setAttribute('updated_at_convert', $response->updated_at->format('d M Y, h:i A'));
        $response->setAttribute('attachment', $response->image);

        if (isset($response['invoice'])) {
            $response['invoice']['created_at_convert'] = food_date_format($response['invoice']->created_at);
            $response['invoice']['updated_at_convert'] = food_date_format($response['invoice']->updated_at);
        }

        if (isset($response['invoice']) && isset($response['invoice']['transactions'])) {
            foreach ($response['invoice']['transactions'] as $transactionKey => $transaction) {
                $response['invoice']['transactions'][$transactionKey]['created_at_convert'] = food_date_format($transaction->created_at);
                $response['invoice']['transactions'][$transactionKey]['updated_at_convert'] = food_date_format($transaction->updated_at);
            }
        }

        if (isset($response['items'])) {
            foreach ($response['items'] as $itemKey => $item) {
                $response['items'][$itemKey]['created_at_convert'] = food_date_format($item->created_at);
                $response['items'][$itemKey]['updated_at_convert'] = food_date_format($item->updated_at);
                $response['items'][$itemKey]['options'] = json_decode($item->options);
                $response['items'][$itemKey]['product']['image'] = $item['product']->images ?? '';
                unset($response['items'][$itemKey]['product']['media']);
            }
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function update(Request $request, $id)
    {
        $status = [
            OrderStatus::CANCEL => 'Cancel'
        ];

        if ((int)$id) {
            $order = Order::find($id);
            if (!blank($order)) {
                if (isset($status[$request->status])) {
                    $orderService = app(OrderService::class)->orderUpdate($id, $request->status);

                    if ($orderService->status) {
                        try {
                            app(PushNotificationService::class)->sendNotificationOrderUpdate($order, $order->user, 'customer');
                        } catch (\Exception $e) {
                        }
                        return response()->json([
                            'status' => 200,
                            'message' => 'You order update successfully completed.',
                            'data' => $orderService
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 422,
                            'message' => $orderService->message
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'status' => 422,
                        'message' => 'The status not found',
                    ], 422);
                }
            } else {
                return response()->json([
                    'status' => 422,
                    'message' => 'The order not found',
                ], 422);
            }
        } else {
            return response()->json([
                'status' => 422,
                'message' => 'The order id not found',
            ], 422);
        }
    }

    public function orderPayment(Request $request)
    {
        if ((int)$request->order_id) {
            $order = Order::find($request->order_id);
            if (!blank($order)) {
                if ($request->payment_method != PaymentMethod::CASH_ON_DELIVERY && $order->payment_status != PaymentStatus::PAID) {
                    if ($request->payment_method != PaymentMethod::WALLET) {
                        app(TransactionService::class)->addFund(0, $order->user->balance_id, $order->payment_method, $order->total, $order->id);
                    }
                    if ($this->adminBalanceId != $order->user->balance_id) {
                        app(TransactionService::class)->payment($order->user->balance_id, $this->adminBalanceId, $order->total, $order->id);
                    }

                    $order->paid_amount = $order->total;
                    $order->payment_method = $request->payment_method;
                    $order->payment_status = PaymentStatus::PAID;
                    $order->save();
                    return response()->json([
                        'status' => 200,
                        'message' => 'Payment successfully complete',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 422,
                        'message' => 'Select your correct payment method',
                    ], 422);
                }
            } else {
                return response()->json([
                    'status' => 422,
                    'message' => 'The order not found',
                ], 422);
            }
        } else {
            return response()->json([
                'status' => 422,
                'message' => 'The order id not found',
            ], 422);
        }
    }

    public function orderCancel($id)
    {
        if ($id) {
            $order = Order::where([
                'user_id' => auth()->id(),
                'status' => OrderStatus::PENDING
            ])->find($id);
            if (!blank($order)) {
                $orderService = app(OrderService::class)->cancel($id);
                if ($orderService->status) {
                    try {
                        app(PushNotificationService::class)->sendNotificationOrderUpdate($order, $order->user, 'customer');
                    } catch (\Exception $e) {
                    }
                    return response()->json([
                        'status' => 200,
                        'message' => 'You order cancel successfully',
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 422,
                        'message' => $orderService->message
                    ], 422);
                }
            } else {
                return response()->json([
                    'status' => 422,
                    'message' => 'The order not found',
                ], 422);
            }
        } else {
            return response()->json([
                'status' => 422,
                'message' => 'The order id not found',
            ], 422);
        }
    }

    public function attachment($id)
    {
        $order = Order::query()->where(['id' => $id, 'user_id' => auth()->user()->id])->first();
        if (!blank($order)) {
            return response()->json([
                'data' => $order->image,
                'status' => 200,
                'message' => 'Success',
            ], 200);
        }
        return response()->json([
            'status' => 401,
            'message' => 'Bad Request',
        ], 401);
    }

    public function filter($status): \Illuminate\Http\JsonResponse
    {
        $orders = Order::query()->where('status', $status)->get();
        return response()->json([
            'status' => true,
            'data' => OrderApiResource::collection($orders)
        ]);
    }
}
