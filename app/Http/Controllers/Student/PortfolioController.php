<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\PortfolioService;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __construct(
        protected PortfolioService $service,
    ) {
    }

    public function index(Request $request)
    {
        $data = $this->service->getUserPortfolios($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => '',
        ]);
    }

    public function store(Request $request)
    {
        $portfolio = $this->service->create(
            $request->user()->id,
            $request->all(),
        );

        return response()->json($portfolio, 201);
    }
}
