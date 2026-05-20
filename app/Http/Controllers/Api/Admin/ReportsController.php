<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Device;
use App\Models\LocationHistory;
use App\Models\AnimalGroup;
use App\Models\MedicalRecord;
use App\Models\GeofenceAlert;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $role = $user->getPrimaryRoleName();
        $ownerId = $this->resolveOwnerId($user, $role);

        $animalQuery = Animal::query();
        $deviceQuery = Device::query();

        if ($role !== 'Admin' && $ownerId) {
            $animalQuery->where('owner_id', $ownerId);
            $deviceQuery->where('owner_id', $ownerId);
        }

        if ($request->filled('animal_id')) {
            $animalQuery->where('id', $request->animal_id);
        }

        if ($request->filled('group_id')) {
            $group = AnimalGroup::with('animals')->find($request->group_id);
            if ($group) {
                $animalIds = $group->animals->pluck('id')->toArray();
                $animalQuery->whereIn('id', $animalIds);
            }
        }

        $animals = $animalQuery->get();
        $devices = $deviceQuery->get();

        $avgTemp = $animals->whereNotNull('baseline_temperature')->avg('baseline_temperature') ?? 38.5;

        $deviceConnectivity = $devices->count() > 0
            ? round(($devices->where('status', 'online')->count() / $devices->count()) * 100, 1)
            : 100;

        $healthScore = $this->calculateHealthScore($animals);

        $activityData = $this->getActivityTrends($animals);
        $distanceByGroup = $this->getDistanceByGroup($role, $ownerId);
        $activityDistribution = $this->getActivityDistribution($animals);

        return response()->json([
            'stats' => [
                'total_animals' => $animals->count(),
                'total_devices' => $devices->count(),
                'avg_movement' => $activityData['avgDaily'],
                'avg_temp' => round($avgTemp, 1),
                'health_score' => $healthScore,
                'connectivity' => $deviceConnectivity,
            ],
            'activity_trend' => $activityData['trend'],
            'temperature_trend' => $this->getTemperatureTrend($animals),
            'health_metrics' => $this->getHealthMetrics($animals),
            'distance_by_group' => $distanceByGroup,
            'activity_distribution' => $activityDistribution,
            'species_distribution' => $this->getSpeciesDistribution($animals),
            'breed_distribution' => $this->getBreedDistribution($animals),
        ]);
    }

    protected function resolveOwnerId($user, $role)
    {
        if ($role === 'Owner') {
            return $user->id;
        }
        if (in_array($role, ['Manager', 'Doctor', 'Shepherd'])) {
            return $user->managed_by;
        }
        return null;
    }

    protected function calculateHealthScore($animals)
    {
        if ($animals->isEmpty()) return 100;

        $healthyCount = 0;
        $totalWeight = $animals->count();

        foreach ($animals as $animal) {
            $tempFactor = 0;
            if ($animal->baseline_temperature && $animal->baseline_temperature >= 38 && $animal->baseline_temperature <= 39.5) {
                $tempFactor = 1;
            } elseif ($animal->baseline_temperature) {
                $tempFactor = 0.5;
            }

            if ($tempFactor > 0) {
                $healthyCount += $tempFactor;
            }
        }

        return round(($healthyCount / $totalWeight) * 100);
    }

    protected function getActivityTrends($animals)
    {
        $animalIds = $animals->pluck('id')->toArray();

        if (empty($animalIds)) {
            return ['trend' => $this->getDefaultTrendData(), 'avgDaily' => 0];
        }

        $last7Days = Carbon::now()->subDays(7);

        $dailyDistances = LocationHistory::whereIn('animal_id', $animalIds)
            ->where('recorded_at', '>=', $last7Days)
            ->select(
                DB::raw('DATE(recorded_at) as date'),
                DB::raw('COUNT(*) as point_count'),
                DB::raw('SUM(COALESCE(speed, 0)) as total_speed')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $trend = [];
        $totalDistance = 0;
        $daysWithData = 0;

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayData = $dailyDistances->firstWhere('date', $date);

            $distance = $dayData ? round($dayData->total_speed * 0.1, 1) : 0;
            $totalDistance += $distance;
            if ($distance > 0) $daysWithData++;

            $trend[] = [
                'date' => $date,
                'label' => Carbon::now()->subDays($i)->format('M d'),
                'distance' => $distance,
            ];
        }

        $avgDaily = $daysWithData > 0 ? round($totalDistance / $daysWithData, 1) : 0;

        return ['trend' => $trend, 'avgDaily' => $avgDaily];
    }

    protected function getDefaultTrendData()
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $trend[] = [
                'date' => Carbon::now()->subDays($i)->format('Y-m-d'),
                'label' => Carbon::now()->subDays($i)->format('M d'),
                'distance' => 0,
            ];
        }
        return $trend;
    }

    protected function getTemperatureTrend($animals)
    {
        $avgTemp = $animals->whereNotNull('baseline_temperature')->avg('baseline_temperature');
        $criticalCount = $animals->filter(function ($a) {
            return $a->baseline_temperature && $a->baseline_temperature > 39.5;
        })->count();

        $tempRanges = [
            ['range' => 'below_38', 'label' => 'Below 38°C', 'count' => 0, 'color' => '#60a5fa'],
            ['range' => '38_39_5', 'label' => '38–39.5°C', 'count' => 0, 'color' => '#002819'],
            ['range' => 'above_39_5', 'label' => 'Above 39.5°C', 'count' => 0, 'color' => '#ef4444'],
        ];

        foreach ($animals as $a) {
            $t = $a->baseline_temperature;
            if (!$t) continue;
            if ($t < 38) $tempRanges[0]['count']++;
            elseif ($t <= 39.5) $tempRanges[1]['count']++;
            else $tempRanges[2]['count']++;
        }

        return [
            'avg_temp' => $avgTemp ? round($avgTemp, 1) : null,
            'critical_count' => $criticalCount,
            'ranges' => $tempRanges,
        ];
    }

    protected function getHealthMetrics($animals)
    {
        $animalIds = $animals->pluck('id')->toArray();

        $recentRecords = MedicalRecord::whereIn('animal_id', $animalIds)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->get();

        $totalAnimals = $animals->count();
        $animalsWithRecords = $recentRecords->pluck('animal_id')->unique()->count();

        $vaccinations = $recentRecords->where('type', 'vaccination')->count();
        $checkups = $recentRecords->where('type', 'checkup')->count();
        $treatments = $recentRecords->where('type', 'treatment')->count();

        $criticalTempAnimals = $animals->filter(function ($a) {
            return $a->baseline_temperature && $a->baseline_temperature > 39.5;
        })->count();

        return [
            'total_records' => $recentRecords->count(),
            'animals_with_records' => $animalsWithRecords,
            'coverage_percentage' => $totalAnimals > 0 ? round(($animalsWithRecords / $totalAnimals) * 100) : 0,
            'vaccinations' => $vaccinations,
            'checkups' => $checkups,
            'treatments' => $treatments,
            'critical_temp_count' => $criticalTempAnimals,
        ];
    }

    protected function getDistanceByGroup($role, $ownerId)
    {
        $groupQuery = AnimalGroup::with('animals');

        if ($role !== 'Admin' && $ownerId) {
            $groupQuery->where('owner_id', $ownerId);
        }

        $groups = $groupQuery->get();

        if ($groups->isEmpty()) {
            return [];
        }

        $result = [];
        $maxDistance = 0.01;
        foreach ($groups as $group) {
            $animalIds = $group->animals->pluck('id')->toArray();

            $totalSpeed = 0;
            if (!empty($animalIds)) {
                $totalSpeed = LocationHistory::whereIn('animal_id', $animalIds)
                    ->where('recorded_at', '>=', Carbon::now()->subDays(7))
                    ->sum(DB::raw('COALESCE(speed, 0)'));
            }

            $distance = round($totalSpeed * 0.1, 1);
            if ($distance > $maxDistance) $maxDistance = $distance;

            $result[] = [
                'name' => $group->name,
                'distance' => $distance,
                'animal_count' => count($animalIds),
            ];
        }

        $result = array_map(function ($item) use ($maxDistance) {
            $item['percentage'] = min(100, round(($item['distance'] / $maxDistance) * 100));
            return $item;
        }, $result);

        return $result;
    }

    protected function getActivityDistribution($animals)
    {
        $animalIds = $animals->pluck('id')->toArray();

        if (empty($animalIds)) {
            return ['grazing' => 0, 'moving' => 0, 'resting' => 0, 'total_points' => 0];
        }

        $recentHistory = LocationHistory::whereIn('animal_id', $animalIds)
            ->where('recorded_at', '>=', Carbon::now()->subHours(24))
            ->get();

        $total = $recentHistory->count();

        if ($total === 0) {
            return ['grazing' => 0, 'moving' => 0, 'resting' => 0, 'total_points' => 0];
        }

        $grazing = $recentHistory->where('speed', '<', 2)->count();
        $moving = $recentHistory->whereBetween('speed', [2, 8])->count();
        $resting = $recentHistory->where('speed', '>', 8)->count();

        return [
            'grazing' => round(($grazing / $total) * 100),
            'moving' => round(($moving / $total) * 100),
            'resting' => round(($resting / $total) * 100),
            'total_points' => $total,
        ];
    }

    protected function getSpeciesDistribution($animals)
    {
        $distribution = $animals->groupBy('species')->map->count();

        $total = $distribution->sum();
        if ($total === 0) return [];

        return $distribution->map(function ($count, $species) use ($total) {
            return [
                'species' => $species ?: 'Unknown',
                'count' => $count,
                'percentage' => round(($count / $total) * 100),
            ];
        })->values()->toArray();
    }

    protected function getBreedDistribution($animals)
    {
        $distribution = $animals->groupBy('breed')->map->count()->sortDesc()->take(10);

        $total = $animals->count();
        if ($total === 0) return [];

        return $distribution->map(function ($count, $breed) use ($total) {
            return [
                'breed' => $breed ?: 'Unknown',
                'count' => $count,
                'percentage' => round(($count / $total) * 100),
            ];
        })->values()->toArray();
    }

    public function export(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $role = $user->getPrimaryRoleName();
        $ownerId = $this->resolveOwnerId($user, $role);

        $animalQuery = Animal::query();
        $deviceQuery = Device::query();

        if ($role !== 'Admin' && $ownerId) {
            $animalQuery->where('owner_id', $ownerId);
            $deviceQuery->where('owner_id', $ownerId);
        }

        if ($request->filled('animal_id')) {
            $animalQuery->where('id', $request->animal_id);
        }

        if ($request->filled('group_id')) {
            $group = AnimalGroup::with('animals')->find($request->group_id);
            if ($group) {
                $animalIds = $group->animals->pluck('id')->toArray();
                $animalQuery->whereIn('id', $animalIds);
            }
        }

        $animals = $animalQuery->get();
        $devices = $deviceQuery->get();

        $avgTemp = $animals->whereNotNull('baseline_temperature')->avg('baseline_temperature') ?? 38.5;
        $deviceConnectivity = $devices->count() > 0
            ? round(($devices->where('status', 'online')->count() / $devices->count()) * 100, 1)
            : 100;
        $healthScore = $this->calculateHealthScore($animals);
        $activityData = $this->getActivityTrends($animals);

        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $rows = [];
        $rows[] = ['Metric', 'Value'];
        $rows[] = ['Report Period', "$dateFrom to $dateTo"];
        $rows[] = ['Total Animals', $animals->count()];
        $rows[] = ['Total Devices', $devices->count()];
        $rows[] = ['Avg Daily Movement (km)', $activityData['avgDaily']];
        $rows[] = ['Avg Temperature (°C)', round($avgTemp, 1)];
        $rows[] = ['Health Score (%)', $healthScore];
        $rows[] = ['Device Connectivity (%)', $deviceConnectivity];
        $rows[] = [''];

        $speciesDist = $this->getSpeciesDistribution($animals);
        if (!empty($speciesDist)) {
            $rows[] = ['Species', 'Count', 'Percentage'];
            foreach ($speciesDist as $s) {
                $rows[] = [$s['species'], $s['count'], $s['percentage'] . '%'];
            }
            $rows[] = [''];
        }

        $breedDist = $this->getBreedDistribution($animals);
        if (!empty($breedDist)) {
            $rows[] = ['Breed', 'Count', 'Percentage'];
            foreach ($breedDist as $b) {
                $rows[] = [$b['breed'], $b['count'], $b['percentage'] . '%'];
            }
            $rows[] = [''];
        }

        $distanceByGroup = $this->getDistanceByGroup($role, $ownerId);
        if (!empty($distanceByGroup)) {
            $rows[] = ['Group', 'Distance (km)', 'Animals'];
            foreach ($distanceByGroup as $g) {
                $rows[] = [$g['name'], $g['distance'], $g['animal_count']];
            }
            $rows[] = [''];
        }

        if (!empty($animals)) {
            $rows[] = ['Animal ID', 'Name', 'Species', 'Breed', 'Gender', 'Weight (kg)', 'Baseline Temp (°C)', 'Device', 'Device Status', 'Battery (%)'];
            foreach ($animals as $animal) {
                $device = $devices->firstWhere('animal_id', $animal->id);
                $rows[] = [
                    $animal->animal_id,
                    $animal->name ?? '',
                    $animal->species ?? '',
                    $animal->breed ?? '',
                    $animal->gender ?? '',
                    $animal->current_weight ?? '',
                    $animal->baseline_temperature ?? '',
                    $device?->device_id ?? '',
                    $device?->status ?? '',
                    $device?->battery_level ?? '',
                ];
            }
        }

        $csv = $this->generateCsv($rows);
        $filename = 'report-' . date('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function generateCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }
}