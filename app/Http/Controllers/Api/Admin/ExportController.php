<?php

namespace App\Http\Controllers\Api\Admin;

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
        $user = $request->user();

        if ($user?->getPrimaryRoleName() !== 'Admin') {
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
        $user = $request->user();

        if ($user?->getPrimaryRoleName() !== 'Admin') {
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
        $user = $request->user();

        if ($user?->getPrimaryRoleName() !== 'Admin') {
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
        $user = $request->user();

        if ($user?->getPrimaryRoleName() !== 'Admin') {
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
        $user = $request->user();

        if ($user?->getPrimaryRoleName() !== 'Admin') {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $dbName = config('database.connections.mysql.database');
        $fileName = 'oasis_database_' . date('Y-m-d') . '.sql';
        $tempFile = storage_path('app/' . $fileName);

        $sql = $this->buildDatabaseDump($dbName);

        file_put_contents($tempFile, $sql);

        if (!file_exists($tempFile) || filesize($tempFile) === 0) {
            return response()->json(['message' => 'Failed to export MySQL database'], 500);
        }

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/sql',
        ])->deleteFileAfterSend(true);
    }

    private function buildDatabaseDump(string $dbName): string
    {
        $pdo = DB::connection()->getPdo();

        $output = "-- Oasis Trace Database Export\n";
        $output .= "-- Database: {$dbName}\n";
        $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- Server version: " . DB::selectOne('SELECT VERSION() AS v')->v . "\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $output .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $output .= "SET NAMES utf8mb4;\n\n";

        $tables = DB::select('SHOW TABLES');
        $tableKey = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;

            $output .= "-- --------------------------------------------------------\n";
            $output .= "-- Table: {$tableName}\n";
            $output .= "-- --------------------------------------------------------\n\n";

            // Drop table if exists
            $output .= "DROP TABLE IF EXISTS `{$tableName}`;\n";

            // CREATE TABLE statement
            $createStmt = DB::selectOne("SHOW CREATE TABLE `{$tableName}`");
            // The key varies by MySQL version: 'Create Table' (mysql), 'create table' (mariadb)
            $createKey = property_exists($createStmt, 'Create Table') ? 'Create Table' : 'create table';
            $output .= $createStmt->$createKey . ";\n\n";

            // Get column names for INSERT
            $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
            $colNames = [];
            foreach ($columns as $col) {
                $colNames[] = "`{$col->Field}`";
            }
            $colList = '(' . implode(', ', $colNames) . ')';

            // Get data
            $rows = DB::table($tableName)->get();
            if ($rows->isNotEmpty()) {
                $values = [];
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($colNames as $colExpr) {
                        $colName = trim($colExpr, '`');
                        $val = $row->$colName ?? null;

                        if ($val === null) {
                            $rowValues[] = 'NULL';
                        } else {
                            // Use PDO quote for proper escaping (handles quotes, binary, etc.)
                            $rowValues[] = $pdo->quote((string)$val);
                        }
                    }
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }

                // Chunk INSERTs for large datasets (500 rows per INSERT)
                $chunks = array_chunk($values, 500);
                foreach ($chunks as $chunk) {
                    $output .= "INSERT INTO `{$tableName}` {$colList} VALUES\n";
                    $output .= implode(",\n", $chunk) . ";\n\n";
                }
            }

            $output .= "\n";
        }

        $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $output .= "-- Export completed: " . date('Y-m-d H:i:s') . "\n";

        return $output;
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