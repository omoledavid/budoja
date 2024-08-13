<?php

namespace App\Http\Controllers\Api\v1;

use Anand\LaravelPaytmWallet\Facades\PaytmWallet;
use App\Enums\PaymentMethod;
use App\Http\Controllers\FrontendController;
use App\Http\Services\PaymentService;
use App\Http\Services\PushNotificationService;
use App\Http\Services\StripeService;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Restaurant;
use Dipesh79\LaravelPhonePe\LaravelPhonePe;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Paystack;
use Razorpay\Api\Api;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class CheckoutController extends FrontendController
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        if (blank(session()->get('cart'))) {
            return redirect('/');
        }

        $this->data['addresses'] = Address::where('user_id', auth()->user()->id)->get();
        $this->data['lastAddress'] = '';

        $lastAddress = Order::select('address')->where('user_id', auth()->user()->id)->latest()->first();
        if (!blank($lastAddress)) {
            if (isJson($lastAddress->address)) {
                $this->data['lastAddress'] = Address::where('address', json_decode($lastAddress->address, true)['address'])->first();
            }
        }

        if (blank($this->data['lastAddress'])) {
            $this->data['lastAddress'] = Address::where('user_id', auth()->user()->id)->first();
        }

        $this->data['menuitems'] = session()->get('cart');
        $this->data['totalPayment'] = session()->get('cart')['totalPayAmount'];
        $this->data['restaurant'] = Restaurant::find(session('session_cart_restaurant_id'));
        return view('frontend.restaurant.checkout', $this->data);
    }

    public function store(Request $request)
{
    $user = auth()->user();
    
    // Fetch cart items with product details
    $cart = Cart::where('user_id', $user->id)
                ->with(['product' => function($q) {
                    $q->select('id', 'restaurant_id', 'name', 'description', 'unit_price', 'discount_price');
                }])
                ->get();
    
    // Check if the cart is empty
    if ($cart->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'Your cart is empty.',
        ], 400);
    }

    // Get the first cart item's restaurant ID
    $restaurantId = $cart->first()->product->restaurant_id;

    $this->setDeliveryCharge($request);
    $restaurant = Restaurant::find($restaurantId);

    // Define validation rules
    $validation = [
        'mobile'      => 'required',
        'payment_type' => 'required|numeric',
    ];
    
    if (!$cart->first()->delivery_type) {
        $validation['address'] = 'required|string';
    }

    $totalAmount = $cart->sum(function($item) {
        return $item->product->unit_price * $item->qty;
    });

    // Perform validation
    $validator = Validator::make($request->all(), $validation);
    $validator->after(function ($validator) use ($request, $totalAmount) {
        if ($request->payment_type == PaymentMethod::WALLET) {
            if ((float) auth()->user()->balance->balance < (float) ($totalAmount)) {
                $validator->errors()->add('payment_type', 'The Credit balance is not enough for this payment.');
            }
        }
    })->validate();

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors(),
        ]);
    }

    // Handle different payment methods
    session()->put('checkoutRequest', $request->all());
    $paymentType = $request->payment_type;

    switch ($paymentType) {
        case PaymentMethod::STRIPE:
            return $this->processStripePayment($restaurant, $totalAmount);
        case PaymentMethod::PAYSTACK:
            return $this->preparePaystackPaymentData($request);
        case PaymentMethod::PAYTM:
            return $this->payWithPaytm($request);
        case PaymentMethod::PHONEPE:
            return $this->phonePePayment($request);
        case PaymentMethod::PAYPAL:
            return $this->initiatePaypalPayment($totalAmount);
        case PaymentMethod::SSLCOMMERZ:
            return $this->sslcommerzPayment($request);
        case PaymentMethod::RAZORPAY:
            return $this->processRazorpayPayment($request);
        default:
            return $this->processDefaultPayment();
    }
}


    public function sslcommerzPayment($request)
    {
        try {
            $array['store_id'] = env('SSLCOMMERZ_STORE_ID');
            $array['store_passwd'] = env('SSLCOMMERZ_STORE_PASSWORD');
            $array['total_amount'] = session()->get('cart')['totalAmount'] + session()->get('delivery_charge');
            $array['currency'] = "USD";
            $array['tran_id'] = "SSLCZ_" . uniqid();
            $array['shipping_method'] = "NO";
            $array['cus_name'] = auth()->user()->name;
            $array['cus_email'] = auth()->user()->email;
            $array['cus_add1'] = $request->address;
            $array['cus_city'] = "";
            $array['cus_state'] = "";
            $array['cus_postcode'] = "";
            $array['cus_country'] = "";
            $array['cus_phone'] = $request->countrycode . $request->mobile;
            $array['product_name'] = "FoodBank";
            $array['product_category'] = "Food";
            $array['product_profile'] = "general";
            $array['product_amount'] = session()->get('cart')['totalAmount'] + session()->get('delivery_charge');
            $array['discount_amount'] = "";
            $array['convenience_fee'] = session()->get('delivery_charge');
            $array['success_url'] = url('/sslcommerz/success');
            $array['fail_url'] = url('/sslcommerz/fail');
            $array['cancel_url'] = url('/sslcommerz/cancel');

            $apiUrl = 'sandbox' == env('SSLCOMMERZ_MODE') ? "https://sandbox.sslcommerz.com/gwprocess/v4/api.php" : "https://securepay.sslcommerz.com/gwprocess/v4/api.php";

            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $apiUrl);
            curl_setopt($handle, CURLOPT_TIMEOUT, 30);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($handle, CURLOPT_POST, 1);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $array);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, !('sandbox' == env('SSLCOMMERZ_MODE')));

            $content = curl_exec($handle);
            $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if ($code == 200 && !(curl_errno($handle))) {
                curl_close($handle);
                $sslcommerzResponse = $content;
            } else {
                curl_close($handle);
                return redirect(route('checkout.index'))->withError('Failed to connect with SSLCOMMERZ API');
            }

            $response = json_decode($sslcommerzResponse, true);

            if (isset($response['GatewayPageURL']) && $response['GatewayPageURL'] != "") {
                return redirect($response['GatewayPageURL']);
            } else {
                return redirect(route('checkout.index'))->withError('JSON Data parsing error!');
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return redirect(route('checkout.index'))->withError('Something went wrong!');
        }
    }

    public function sslcommerzSuccess(Request $request)
    {
        if (isset($request->bank_tran_id)) {
            $orderService = app(PaymentService::class)->payment(true);
        } else {
            $orderService = app(PaymentService::class)->payment(false);
        }
        return $this->handleOrderServiceResponse($orderService);
    }

    public function sslcommerzFail()
    {
        return redirect(route('checkout.index'))->withError('Something went wrong!');
    }
    public function sslcommerzCancle()
    {
        return redirect(route('checkout.index'))->withError('Something went wrong!');
    }

    public function phonePePayment($request)
    {
        $phonepe = new LaravelPhonePe();
        $amount = session()->get('cart')['totalAmount'] + session()->get('delivery_charge');
        $phone = $request->countrycode . $request->mobile;
        $callbak_url = url('/phonepe/status');
        $uniqueId = uniqid();
        $url = $phonepe->makePayment($amount, $phone, $callbak_url, $uniqueId);
        return redirect()->away($url);
    }

    public function phonepeCallback(Request $request)
    {
        $phonepe = new LaravelPhonePe();
        $response = $phonepe->getTransactionStatus($request->all());
        if ($response) {
            $orderService = app(PaymentService::class)->payment(true);
        } else {
            $orderService = app(PaymentService::class)->payment(false);
        }
        return $this->handleOrderServiceResponse($orderService);
    }

    protected function payWithPaytm($request)
    {
        $payment = PaytmWallet::with('receive');
        $payment->prepare([
            'order' => uniqid(),
            'user' => auth()->user()->id,
            'mobile_number' => $request->countrycode . $request->mobile,
            'email' => auth()->user()->email,
            'amount' => session()->get('cart')['totalAmount'] + session()->get('delivery_charge'),
            'callback_url' => url('/paytm/status'),
        ]);
        return $payment->receive();
    }

    protected function paytmCallback()
    {
        $transaction = PaytmWallet::with('receive');
        $response = $transaction->response();
        if ($transaction->isSuccessful()) {
            $orderService = app(PaymentService::class)->payment(true);
        } else {
            $orderService = app(PaymentService::class)->payment(false);
        }
        return $this->handleOrderServiceResponse($orderService);
    }

    protected function setDeliveryCharge($request)
    {
        $deliveryCharge = $request->total_delivery_charge;
        session()->put('delivery_charge', $deliveryCharge ?: 0);
    }

    protected function getLastAddress()
    {
        $lastAddress = Order::select('address')
            ->where('user_id', auth()->user()->id)
            ->latest()
            ->first();

        if (!blank($lastAddress) && isJson($lastAddress->address)) {
            return Address::where('address', json_decode($lastAddress->address, true)['address'])->first();
        }

        return Address::where('user_id', auth()->user()->id)->first();
    }

    protected function validateCheckoutRequest($request, $restaurant)
    {
        $validation = [
            'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
            'payment_type' => 'required|numeric',
        ];
        if (!$request->delivery_type) {
            $validation['address'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $validation);

        $validator->after(function ($validator) use ($request, $restaurant) {
            if (
                $request->payment_type == PaymentMethod::WALLET &&
                (float) auth()->user()->balance->balance < (float) (session()->get('cart')['totalAmount'] + session()->get('delivery_charge'))
            ) {
                $validator->errors()->add('payment_type', 'The Credit balance does not enough for this payment.');
            }
        });

        return $validator;
    }

    protected function processStripePayment($restaurant, $totalAmount)
    {
        $stripeService = new StripeService();
        $stripeParameters = [
            'amount' => $totalAmount,
            'currency' => 'USD',
            'token' => request('stripeToken'),
            'description' => 'N/A',
        ];
        
        $payment = $stripeService->payment($stripeParameters);
        return 'here';  
        $orderService = $this->handlePaymentResponse($payment);

        if ($orderService->status) {
            $order = Order::find($orderService->order_id);
            $this->clearSessionData();
            $this->sendOrderNotifications($order);
            return redirect(route('account.order.show', $order->id))->withSuccess('You order completed successfully.');
        } else {
            return redirect(route('checkout.index'))->withError($orderService->message);
        }
    }

    protected function preparePaystackPaymentData($request)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . setting('paystack_secret'),
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => auth()->user()->email,
            'amount' => (session()->get('cart')['totalAmount'] + session()->get('delivery_charge')) * 100, // Convert to kobo
            'callback_url' => route('paystack.callback'),
        ]);

        $responseData = $response->json();
        if (isset($responseData['data']['authorization_url'])) {
            $paymentUrl = $responseData['data']['authorization_url'];
            return redirect($paymentUrl);
        } else {
            return redirect()->route('pay')->with('error', 'Payment initialization failed. Please try again.');
        }
    }

    public function PaystackCallback()
    {
        $payment = Paystack::getPaymentData();

        if ($payment['status'] && $payment['data']['status'] === 'success') {
            $orderService = app(PaymentService::class)->payment(true);
        } else {
            $orderService = app(PaymentService::class)->payment(false);
        }

        return $this->handleOrderServiceResponse($orderService);
    }

    protected function initiatePaypalPayment($totalAmount)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        $response = $this->createPaypalOrder($provider, $totalAmount);
        // return $response;
        if (isset($response['id']) && $response['id'] != null) {
            return $this->redirectPaypalApproval($response['links']);
        } else {
            return redirect(route('checkout.index'))->withError('You have canceled the transaction.');
        }
    }

    protected function createPaypalOrder($provider, $totalAmount)
    {
        return $provider->createOrder([
            'intent' => 'CAPTURE',
            'application_context' => [
                'return_url' => route('successTransaction'),
                'cancel_url' => route('cancelTransaction'),
            ],
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'USD',
                        // 'currency_code' => setting('currency_name'),
                        'value' => $totalAmount,
                    ],
                ],
            ],
        ]);
    }

    protected function redirectPaypalApproval($links)
    {
        foreach ($links as $link) {
            if ($link['rel'] == 'approve') {
                // return redirect()->away($link['href']);
                return response()->json([
                    'link' => $links[1],
                ]);
            }
        }

        return redirect(route('checkout.index'))->withError('You have canceled the transaction.');
    }

    protected function processRazorpayPayment($request)
    {
        $input = $request->all();
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
        $payment = $this->fetchRazorpayPayment($api, $input);

        if (count($input) && !empty($input['razorpay_payment_id'])) {
            $response = $this->captureRazorpayPayment($api, $input, $payment);

            $orderService = app(PaymentService::class)->payment($response['status'] === 'captured');
            return $this->handleOrderServiceResponse($orderService);
        } else {
            return redirect(route('checkout.index'))->withError('You have canceled the transaction.');
        }
    }

    protected function fetchRazorpayPayment($api, $input)
    {
        return $api->payment->fetch($input['razorpay_payment_id']);
    }

    protected function captureRazorpayPayment($api, $input, $payment)
    {
        return $api->payment->fetch($input['razorpay_payment_id'])->capture(['amount' => $payment['amount']]);
    }

    protected function processDefaultPayment()
    {
        $orderService = app(PaymentService::class)->payment(false);
        return $this->handleOrderServiceResponse($orderService);
    }

    protected function handleOrderServiceResponse($orderService)
    {
        if ($orderService->status) {
            $order = Order::find($orderService->order_id);
            $this->clearSessionData();
            $this->sendOrderNotifications($order);
            // return redirect(route('account.order.show', $order->id))->withSuccess('You order completed successfully.');
            return response()->json([
                'status' => true,
                'message' => 'Payment successful, Order complete'
            ]);
        } else {
            // return redirect(route('checkout.index'))->withError($orderService->message);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ], 400);
        }
    }

    protected function clearSessionData()
    {
        session()->put('cart', null);
        session()->put('checkoutRequest', null);
        session()->put('session_cart_restaurant_id', 0);
        session()->put('session_cart_restaurant', null);
    }

    protected function sendOrderNotifications($order)
    {
        try { 
            app(PushNotificationService::class)->NotificationForRestaurant($order, $order->restaurant->user, 'restaurant');
            app(PushNotificationService::class)->NotificationForCustomer($order, auth()->user(), 'customer');
        } catch (\Exception $exception) {
            //
        }
    }

    protected function handlePaymentResponse($payment)
    {
        if (is_object($payment) && $payment->isSuccessful()) {
            $orderService = app(PaymentService::class)->payment(true);
        } else {
            $orderService = app(PaymentService::class)->payment(false);
        }
        return $orderService;
    }

    public function paypalSuccessTransaction(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request['token']);

        if (isset($response['status']) && $response['status'] === 'COMPLETED') {
            $orderService = app(PaymentService::class)->payment(true);
        } else {
            $orderService = app(PaymentService::class)->payment(false);
        }

        return $this->handleOrderServiceResponse($orderService);
    }

    public function paypalCancelTransaction(Request $request)
    {
        return redirect(route('checkout.index'))->withError('You have canceled the transaction.');
    }
}
