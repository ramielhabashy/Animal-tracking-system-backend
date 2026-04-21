<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('subscription_tier_id', 'users_subscription_tier_id_index');
            $table->index('managed_by', 'users_managed_by_index');
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->index('owner_id', 'animals_owner_id_index');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->index('animal_id', 'devices_animal_id_index');
            $table->index('owner_id', 'devices_owner_id_index');
        });

        Schema::table('geofences', function (Blueprint $table) {
            $table->index('owner_id', 'geofences_owner_id_index');
        });

        Schema::table('geofence_alerts', function (Blueprint $table) {
            $table->index('geofence_id', 'geofence_alerts_geofence_id_index');
            $table->index('animal_id', 'geofence_alerts_animal_id_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('geofence_id', 'tasks_geofence_id_index');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->index('animal_id', 'medical_records_animal_id_index');
            $table->index('owner_id', 'medical_records_owner_id_index');
        });

        Schema::table('vaccination_schedules', function (Blueprint $table) {
            $table->index('animal_id', 'vaccination_schedules_animal_id_index');
            $table->index('owner_id', 'vaccination_schedules_owner_id_index');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->index('user_id', 'user_subscriptions_user_id_index');
            $table->index('tier_id', 'user_subscriptions_tier_id_index');
        });

        Schema::table('animal_groups', function (Blueprint $table) {
            $table->index('owner_id', 'animal_groups_owner_id_index');
        });

        Schema::table('animal_group_member', function (Blueprint $table) {
            $table->index('animal_group_id', 'animal_group_member_group_id_index');
            $table->index('animal_id', 'animal_group_member_animal_id_index');
        });

        Schema::table('animal_geofence', function (Blueprint $table) {
            $table->index('animal_id', 'animal_geofence_animal_id_index');
            $table->index('geofence_id', 'animal_geofence_geofence_id_index');
        });

        Schema::table('animal_group_geofence', function (Blueprint $table) {
            $table->index('animal_group_id', 'animal_group_geofence_group_id_index');
            $table->index('geofence_id', 'animal_group_geofence_geofence_id_index');
        });

        Schema::table('predefined_tasks', function (Blueprint $table) {
            $table->index('owner_id', 'predefined_tasks_owner_id_index');
            $table->index('animal_id', 'predefined_tasks_animal_id_index');
        });

        Schema::table('task_logs', function (Blueprint $table) {
            $table->index('task_id', 'task_logs_task_id_index');
            $table->index('user_id', 'task_logs_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_subscription_tier_id_index');
            $table->dropIndex('users_managed_by_index');
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->dropIndex('animals_owner_id_index');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex('devices_animal_id_index');
            $table->dropIndex('devices_owner_id_index');
        });

        Schema::table('geofences', function (Blueprint $table) {
            $table->dropIndex('geofences_owner_id_index');
        });

        Schema::table('geofence_alerts', function (Blueprint $table) {
            $table->dropIndex('geofence_alerts_geofence_id_index');
            $table->dropIndex('geofence_alerts_animal_id_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_geofence_id_index');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropIndex('medical_records_animal_id_index');
            $table->dropIndex('medical_records_owner_id_index');
        });

        Schema::table('vaccination_schedules', function (Blueprint $table) {
            $table->dropIndex('vaccination_schedules_animal_id_index');
            $table->dropIndex('vaccination_schedules_owner_id_index');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropIndex('user_subscriptions_user_id_index');
            $table->dropIndex('user_subscriptions_tier_id_index');
        });

        Schema::table('animal_groups', function (Blueprint $table) {
            $table->dropIndex('animal_groups_owner_id_index');
        });

        Schema::table('animal_group_member', function (Blueprint $table) {
            $table->dropIndex('animal_group_member_group_id_index');
            $table->dropIndex('animal_group_member_animal_id_index');
        });

        Schema::table('animal_geofence', function (Blueprint $table) {
            $table->dropIndex('animal_geofence_animal_id_index');
            $table->dropIndex('animal_geofence_geofence_id_index');
        });

        Schema::table('animal_group_geofence', function (Blueprint $table) {
            $table->dropIndex('animal_group_geofence_group_id_index');
            $table->dropIndex('animal_group_geofence_geofence_id_index');
        });

        Schema::table('predefined_tasks', function (Blueprint $table) {
            $table->dropIndex('predefined_tasks_owner_id_index');
            $table->dropIndex('predefined_tasks_animal_id_index');
        });

        Schema::table('task_logs', function (Blueprint $table) {
            $table->dropIndex('task_logs_task_id_index');
            $table->dropIndex('task_logs_user_id_index');
        });
    }
};