<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\User;
use App\Models\Device;
use App\Models\Auction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1|max:100']);
        $q = $request->q;

        $animals = Animal::where(function ($query) use ($q) {
            $query->where('animal_id', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%")
                ->orWhere('species', 'like', "%{$q}%")
                ->orWhere('breed', 'like', "%{$q}%");
        })->limit(5)->get(['id', 'animal_id', 'name', 'species', 'breed']);

        $users = User::where(function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%");
        })->limit(5)->get(['id', 'name', 'email']);

        $devices = Device::where(function ($query) use ($q) {
            $query->where('device_id', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%");
        })->limit(5)->get(['id', 'device_id', 'name', 'status']);

        $auctions = Auction::where('title', 'like', "%{$q}%")
            ->limit(5)->get(['id', 'title', 'status', 'current_price']);

        return response()->json([
            'data' => [
                'animals' => $animals,
                'users' => $users,
                'devices' => $devices,
                'auctions' => $auctions,
            ],
        ]);
    }
}
