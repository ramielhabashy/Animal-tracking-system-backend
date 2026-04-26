<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Device;
use App\Models\Geofence;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    public function exportAnimals(Request $request)
    {
        $userId = $request->header('X-User-Id');
        $userRole = $request->header('X-User-Role');
        
        if ($userRole !== 'Admin') {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $animals = Animal::with(['owner', 'device'])->get();

        $csv = $this->generateCsv([
            ['ID', 'Animal ID', 'Species', 'Breed', 'Gender', 'Date of Birth', 'Weight (kg)', 'Owner', 'Device ID', 'Temperature', 'Heart Rate', 'Created At'],
            ...$animals->map(function ($animal) {
                return [
                    $animal->id,
                    $animal->animal_id,
                    $animal->species,
                    $animal->breed,
                    $animal->gender,
                    $animal->date_of_birth,
                    $animal->current_weight,
                    $animal->owner->name ?? '',
                    $animal->device_id ?? '',
                    $animal->baseline_temperature,
                    $animal->normal_heart_rate,
                    $animal->created_at,
                ];
            })->toArray()
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="animals_' . date('Y-m-d') . '.csv"',
        ]);
    }

    public function exportDevices(Request $request)
    {
        $userId = $request->header('X-User-Id');
        $userRole = $request->header('X-User-Role');
        
        if ($userRole !== 'Admin') {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $devices = Device::all();

        $csv = $this->generateCsv([
            ['ID', 'Device ID', 'Name', 'Status', 'Battery Level', 'GPS Lat', 'GPS Lng', 'Owner ID', 'Assigned At'],
            ...$devices->map(function ($device) {
                return [
                    $device->id,
                    $device->device_id,
                    $device->name ?? '',
                    $device->status,
                    $device->battery_level,
                    $device->gps_lat,
                    $device->gps_lng,
                    $device->owner_id,
                    $device->created_at,
                ];
            })->toArray()
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="devices_' . date('Y-m-d') . '.csv"',
        ]);
    }

    public function exportGeofences(Request $request)
    {
        $userId = $request->header('X-User-Id');
        $userRole = $request->header('X-User-Role');
        
        if ($userRole !== 'Admin') {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $geofences = Geofence::with(['owner', 'animals'])->get();

        $csv = $this->generateCsv([
            ['ID', 'Name', 'Type', 'Latitude', 'Longitude', 'Radius (m)', 'Owner', 'Animals', 'Created At'],
            ...$geofences->map(function ($geofence) {
                return [
                    $geofence->id,
                    $geofence->name,
                    $geofence->type,
                    $geofence->latitude,
                    $geofence->longitude,
                    $geofence->radius,
                    $geofence->owner->name ?? '',
                    $geofence->animals->pluck('animal_id')->implode(', '),
                    $geofence->created_at,
                ];
            })->toArray()
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="geofences_' . date('Y-m-d') . '.csv"',
        ]);
    }

    public function exportUsers(Request $request)
    {
        $userId = $request->header('X-User-Id');
        $userRole = $request->header('X-User-Role');
        
        if ($userRole !== 'Admin') {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $users = User::all();

        $csv = $this->generateCsv([
            ['ID', 'Name', 'Email', 'Phone', 'Role', 'Managed By', 'Created At'],
            ...$users->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone ?? '',
                    $user->getPrimaryRoleName(),
                    $user->manager ? $user->manager->name : '',
                    $user->created_at,
                ];
            })->toArray()
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_' . date('Y-m-d') . '.csv"',
        ]);
    }

    public function exportDatabase(Request $request)
    {
        $userRole = $request->header('X-User-Role');
        
        if ($userRole !== 'Admin') {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $dbPath = database_path('database.sqlite');
        
        if (!file_exists($dbPath)) {
            return response()->json(['message' => 'Database file not found'], 404);
        }

        return response()->download($dbPath, 'oasis_database_' . date('Y-m-d') . '.sqlite', [
            'Content-Type' => 'application/vnd.sqlite3',
        ]);
    }

    private function generateCsv(array $rows): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
