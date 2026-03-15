<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Fare lookup for seat selection
Route::get('/v1/fare', function(Request $request) {
    $fare = \App\Models\Fare::where('schedule_id', $request->schedule_id)
        ->where('boarding_point_id', $request->boarding)
        ->where('dropoff_point_id',  $request->dropoff)
        ->where('is_active', true)->first();
    if (!$fare) return response()->json(['total'=>0,'base'=>0,'tax'=>0,'service'=>0]);
    return response()->json([
        'total'   => $fare->total,
        'base'    => $fare->base_fare,
        'tax'     => $fare->tax_amount,
        'service' => $fare->service_charge,
    ]);
});
