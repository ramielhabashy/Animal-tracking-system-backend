<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Auction;
use Illuminate\Http\JsonResponse;

class EmbedController extends Controller
{
    public function auctions(): JsonResponse
    {
        $auctions = Auction::with(['animal' => function ($q) {
            $q->select('id', 'animal_id', 'species', 'breed', 'identification_photo', 'baseline_temperature', 'current_weight');
        }, 'owner' => function ($q) {
            $q->select('id', 'name');
        }])
            ->whereIn('status', ['active', 'live'])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($auction) {
                return [
                    'id' => $auction->id,
                    'title' => $auction->title,
                    'status' => $auction->status,
                    'starting_price' => (float) $auction->starting_price,
                    'current_bid' => (float) ($auction->current_bid ?? $auction->starting_price),
                    'currency' => $auction->currency ?? 'USD',
                    'ends_at' => $auction->ends_at?->toISOString(),
                    'animal' => $auction->animal ? [
                        'id' => $auction->animal->id,
                        'animal_id' => $auction->animal->animal_id,
                        'species' => $auction->animal->species,
                        'breed' => $auction->animal->breed,
                        'image' => $auction->animal->identification_photo,
                        'weight' => $auction->animal->current_weight,
                    ] : null,
                    'owner' => $auction->owner ? [
                        'name' => $auction->owner->name,
                    ] : null,
                ];
            });

        return response()->json(['data' => $auctions]);
    }

    public function animals(): JsonResponse
    {
        $animals = Animal::with(['owner' => function ($q) {
            $q->select('id', 'name');
        }])
            ->whereNotNull('owner_id')
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($animal) {
                return [
                    'id' => $animal->id,
                    'animal_id' => $animal->animal_id,
                    'name' => $animal->name,
                    'species' => $animal->species,
                    'breed' => $animal->breed,
                    'gender' => $animal->gender,
                    'weight' => $animal->current_weight,
                    'image' => $animal->identification_photo,
                    'date_of_birth' => $animal->date_of_birth?->toISOString(),
                    'color_markings' => $animal->color_markings,
                    'owner' => $animal->owner ? [
                        'name' => $animal->owner->name,
                    ] : null,
                ];
            });

        return response()->json(['data' => $animals]);
    }
}
