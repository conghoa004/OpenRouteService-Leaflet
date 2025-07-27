<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TechnicianController extends Controller
{
    // Danh sách kỹ thuật viên mẫu (hoặc lấy từ DB)
    private $technicians = [
        ['id' => 1, 'name' => 'Nguyễn Văn A', 'lat' => 10.767, 'lng' => 106.682],
        ['id' => 2, 'name' => 'Trần Văn B', 'lat' => 10.758, 'lng' => 106.669],
        ['id' => 3, 'name' => 'Lê Văn C', 'lat' => 10.751, 'lng' => 106.675],
        ['id' => 4, 'name' => 'Phạm Văn D', 'lat' => 10.776, 'lng' => 106.700],
    ];

    public function findNearest(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $customer = ['lat' => $request->lat, 'lng' => $request->lng];
        $apiKey = env('OPEN_ROUTE_SERVICE_API_KEY');
        $nearest = null;
        $shortest = INF;
        $bestRoute = null;

        foreach ($this->technicians as $tech) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openrouteservice.org/v2/directions/driving-car/geojson', [
                    'coordinates' => [
                        [$tech['lng'], $tech['lat']],
                        [$customer['lng'], $customer['lat']]
                    ]
                ]);

                if ($response->failed()) {
                    continue;
                }

                $data = $response->json();
                $route = $data['features'][0];
                $distance = $route['properties']['summary']['distance'];
                $duration = $route['properties']['summary']['duration'];

                if ($distance < $shortest) {
                    $shortest = $distance;
                    $nearest = $tech;
                    $bestRoute = [
                        'coords' => array_map(fn($c) => [$c[1], $c[0]], $route['geometry']['coordinates']),
                        'distance' => $distance,
                        'duration' => $duration
                    ];
                }
            } catch (\Throwable $e) {
                // Ghi log lỗi nếu cần
                continue;
            }
        }

        if ($nearest && $bestRoute) {
            return response()->json([
                'technician' => $nearest,
                'route' => $bestRoute
            ]);
        }

        return response()->json(['message' => 'Không tìm được kỹ thuật viên phù hợp'], 404);
    }
}