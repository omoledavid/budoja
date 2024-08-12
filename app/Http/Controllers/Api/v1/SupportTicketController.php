<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Traits\SupportTicketManager;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    use SupportTicketManager;
    
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    protected function seller()
    {
        return auth()->user();
    }

    public function index()
    {
        $seller = $this->seller();
        $tickets = SupportTicket::where('user_id', $seller->id)->orwhere('user_id', $seller->id)
            ->orderBy('priority', 'desc')
            ->orderBy('id','desc')
            ->paginate(10);
        return response()->json([
            'status' => 'sucessful',
            'tickets' => $tickets
        ]);
    }
    public function store(Request $request): JsonResponse
    {
        $savedTicket = $this->storeTicket($request, $request->user()->id, 'user');
        return response()->json([
            'status' => 'success',
            'data' => $savedTicket
        ]);
    }
}
