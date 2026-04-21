import pymysql
import sqlite3

sqlite_path = r'C:\animal-tracking-system-head\backend\database\database.sqlite'

conn = sqlite3.connect(sqlite_path)
cursor = conn.cursor()

db = pymysql.connect(host='localhost', user='root', password='', database='oasis_staging')
my_cursor = db.cursor()

my_cursor.execute("SET FOREIGN_KEY_CHECKS = 0")
db.commit()

for table in ['users', 'animals', 'devices', 'geofences', 'geofence_alerts', 'animal_groups', 'animal_group_member', 'auctions', 'bids', 'user_subscriptions', 'subscription_tiers', 'location_history', 'species', 'breeds']:
    try:
        my_cursor.execute(f"DELETE FROM {table}")
    except:
        pass
db.commit()

tables_config = {
    'users': {
        'sqlite': ['id', 'name', 'email', 'email_verified_at', 'password', 'phone', 'location', 'avatar_url', 'role', 'language', 'subscription_tier_id', 'subscription_status', 'managed_by', 'is_active', 'remember_token', 'created_at', 'updated_at'],
        'mysql': ['id', 'name', 'email', 'email_verified_at', 'password', 'phone', 'location', 'avatar_url', 'role', 'language', 'subscription_tier_id', 'subscription_status', 'managed_by', 'is_active', 'remember_token', 'created_at', 'updated_at']
    },
    'animals': {
        'sqlite': ['id', 'animal_id', 'species', 'breed', 'date_of_birth', 'gender', 'color_markings', 'current_weight', 'identification_photo', 'baseline_temperature', 'normal_heart_rate', 'owner_id', 'device_id', 'created_at', 'updated_at'],
        'mysql': ['id', 'animal_id', 'species', 'breed', 'date_of_birth', 'gender', 'color_markings', 'current_weight', 'identification_photo', 'baseline_temperature', 'normal_heart_rate', 'owner_id', 'created_at', 'updated_at']
    },
    'devices': {
        'sqlite': ['id', 'device_id', 'name', 'type', 'serial_number', 'firmware_version', 'battery_level', 'signal_strength', 'status', 'update_interval', 'advanced_tracking', 'animal_id', 'gps_lat', 'gps_lng', 'last_ping', 'created_at', 'updated_at', 'owner_id'],
        'mysql': ['id', 'device_id', 'name', 'type', 'serial_number', 'firmware_version', 'battery_level', 'signal_strength', 'status', 'update_interval', 'advanced_tracking', 'animal_id', 'gps_lat', 'gps_lng', 'last_ping', 'created_at', 'updated_at', 'owner_id']
    },
    'geofences': {
        'sqlite': ['id', 'name', 'coordinates', 'color', 'alert_type', 'is_active', 'owner_id', 'created_at', 'updated_at'],
        'mysql': ['id', 'name', 'coordinates', 'color', 'alert_type', 'is_active', 'owner_id', 'created_at', 'updated_at']
    },
    'geofence_alerts': {
        'sqlite': ['id', 'geofence_id', 'animal_id', 'type', 'triggered_at', 'is_acknowledged', 'created_at', 'updated_at', 'notification_sent', 'notification_sent_at', 'device_id', 'latitude', 'longitude'],
        'mysql': ['id', 'geofence_id', 'animal_id', 'type', 'triggered_at', 'is_acknowledged', 'created_at', 'updated_at', 'notification_sent', 'notification_sent_at', 'device_id', 'latitude', 'longitude']
    },
    'animal_groups': {
        'sqlite': ['id', 'name', 'description', 'color', 'owner_id', 'created_at', 'updated_at'],
        'mysql': ['id', 'name', 'description', 'color', 'owner_id', 'created_at', 'updated_at']
    },
    'animal_group_member': {
        'sqlite': ['id', 'animal_group_id', 'animal_id', 'created_at', 'updated_at'],
        'mysql': ['id', 'animal_group_id', 'animal_id', 'created_at', 'updated_at']
    },
    'auctions': {
        'sqlite': ['id', 'animal_id', 'owner_id', 'starting_price', 'current_price', 'reserve_price', 'status', 'description', 'title', 'starts_at', 'ends_at', 'ended_at', 'winner_id', 'created_at', 'updated_at', 'payment_proof_url', 'payment_expires_at', 'payment_verified_at', 'payment_status', 'payment_notes', 'verified_by', 'second_winner_id'],
        'mysql': ['id', 'animal_id', 'owner_id', 'starting_price', 'current_price', 'reserve_price', 'status', 'description', 'title', 'starts_at', 'ends_at', 'ended_at', 'winner_id', 'created_at', 'updated_at', 'payment_proof_url', 'payment_expires_at', 'payment_verified_at', 'payment_status', 'payment_notes', 'verified_by', 'second_winner_id']
    },
    'bids': {
        'sqlite': ['id', 'auction_id', 'user_id', 'amount', 'bidder_name', 'bid_at', 'is_winning', 'created_at', 'updated_at'],
        'mysql': ['id', 'auction_id', 'user_id', 'amount', 'bidder_name', 'bid_at', 'is_winning', 'created_at', 'updated_at']
    },
    'user_subscriptions': {
        'sqlite': ['id', 'user_id', 'tier_id', 'status', 'started_at', 'trial_ends_at', 'ends_at', 'cancelled_at', 'billing_cycle', 'created_at', 'updated_at', 'payment_method', 'payment_reference'],
        'mysql': ['id', 'user_id', 'tier_id', 'status', 'started_at', 'trial_ends_at', 'ends_at', 'cancelled_at', 'billing_cycle', 'payment_method', 'payment_reference', 'created_at', 'updated_at']
    },
    'subscription_tiers': {
        'sqlite': ['id', 'name', 'slug', 'description', 'price_monthly', 'price_yearly', 'trial_days', 'max_animals', 'max_devices', 'max_users', 'has_geofencing', 'has_auctions', 'has_advanced_reports', 'has_api_access', 'sort_order', 'is_active', 'created_at', 'updated_at'],
        'mysql': ['id', 'name', 'slug', 'description', 'price_monthly', 'price_yearly', 'trial_days', 'max_animals', 'max_devices', 'max_users', 'has_geofencing', 'has_auctions', 'has_advanced_reports', 'has_medical_records', 'has_tasks', 'has_api_access', 'has_ai_assistant', 'sort_order', 'is_active', 'created_at', 'updated_at'],
        'defaults': {'has_medical_records': 0, 'has_tasks': 0, 'has_ai_assistant': 0}
    },
    'location_history': {
        'sqlite': ['id', 'device_id', 'animal_id', 'latitude', 'longitude', 'speed', 'heading', 'recorded_at', 'created_at', 'updated_at'],
        'mysql': ['id', 'device_id', 'animal_id', 'latitude', 'longitude', 'speed', 'heading', 'recorded_at', 'created_at', 'updated_at']
    },
    'species': {
        'sqlite': ['id', 'name', 'description', 'is_active', 'created_at', 'updated_at'],
        'mysql': ['id', 'name', 'description', 'is_active', 'created_at', 'updated_at']
    },
    'breeds': {
        'sqlite': ['id', 'species_id', 'name', 'description', 'is_active', 'created_at', 'updated_at'],
        'mysql': ['id', 'species_id', 'name', 'description', 'is_active', 'created_at', 'updated_at']
    },
}

for table, config in tables_config.items():
    sqlite_cols = config['sqlite']
    mysql_cols = config['mysql']
    defaults = config.get('defaults', {})
    
    cursor.execute(f"SELECT * FROM {table}")
    rows = cursor.fetchall()
    sqlite_columns = [desc[0] for desc in cursor.description]
    col_map = {sqlite_columns[i]: i for i in range(len(sqlite_columns))}
    
    if not rows:
        print(f"No data in table: {table}")
        continue
        
    print(f"Exporting {table}: {len(rows)} rows")
    
    placeholders = ', '.join(['%s'] * len(mysql_cols))
    cols = ', '.join(mysql_cols)
    sql = f"INSERT INTO {table} ({cols}) VALUES ({placeholders})"
    
    for row in rows:
        clean_row = []
        for col in mysql_cols:
            if col in col_map:
                val = row[col_map[col]]
            elif col in defaults:
                val = defaults[col]
            else:
                val = None
                
            if val is None:
                clean_row.append(None)
            elif isinstance(val, (int, float)):
                clean_row.append(val)
            else:
                clean_row.append(str(val))
        try:
            my_cursor.execute(sql, clean_row)
        except Exception as e:
            print(f"Error importing {table}: {e}")

db.commit()
my_cursor.execute("SET FOREIGN_KEY_CHECKS = 1")
db.commit()

db.close()
conn.close()
print("\nDone!")