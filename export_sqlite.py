import sqlite3

sqlite_path = r'C:\animal-tracking-system-head\backend\database\database.sqlite'
output_file = r'C:\animal-tracking-system-head\staging_data_mysql.sql'

table_mappings = {
    'users': ['id', 'name', 'email', 'email_verified_at', 'password', 'phone', 'location', 'avatar_url', 'role', 'language', 'subscription_tier_id', 'subscription_status', 'managed_by', 'is_active', 'remember_token', 'created_at', 'updated_at'],
    'animals': ['id', 'animal_id', 'species', 'breed', 'date_of_birth', 'gender', 'color_markings', 'current_weight', 'identification_photo', 'baseline_temperature', 'normal_heart_rate', 'owner_id', 'created_at', 'updated_at'],
    'devices': ['id', 'device_id', 'name', 'type', 'serial_number', 'firmware_version', 'battery_level', 'signal_strength', 'status', 'update_interval', 'advanced_tracking', 'animal_id', 'gps_lat', 'gps_lng', 'last_ping', 'created_at', 'updated_at', 'owner_id'],
    'geofences': ['id', 'name', 'coordinates', 'color', 'alert_type', 'is_active', 'owner_id', 'created_at', 'updated_at'],
    'geofence_alerts': ['id', 'geofence_id', 'animal_id', 'type', 'triggered_at', 'is_acknowledged', 'created_at', 'updated_at', 'notification_sent', 'notification_sent_at', 'device_id', 'latitude', 'longitude'],
    'animal_groups': ['id', 'name', 'description', 'color', 'owner_id', 'created_at', 'updated_at'],
    'animal_group_member': ['id', 'animal_group_id', 'animal_id', 'joined_at'],
    'auctions': ['id', 'animal_id', 'owner_id', 'starting_price', 'current_price', 'reserve_price', 'status', 'description', 'title', 'starts_at', 'ends_at', 'ended_at', 'winner_id', 'created_at', 'updated_at', 'payment_proof_url', 'payment_expires_at', 'payment_verified_at', 'payment_status', 'payment_notes', 'verified_by', 'second_winner_id'],
    'bids': ['id', 'auction_id', 'user_id', 'amount', 'bidder_name', 'bid_at', 'is_winning', 'created_at', 'updated_at'],
    'user_subscriptions': ['id', 'user_id', 'subscription_tier_id', 'start_date', 'end_date', 'status', 'auto_renew', 'created_at', 'updated_at'],
    'subscription_tiers': ['id', 'name', 'price', 'features', 'duration_days', 'is_active', 'created_at', 'updated_at'],
    'location_history': ['id', 'animal_id', 'device_id', 'latitude', 'longitude', 'altitude', 'speed', 'heading', 'battery_level', 'recorded_at', 'created_at'],
    'species': ['id', 'name', 'description', 'is_active', 'created_at', 'updated_at'],
    'breeds': ['id', 'species_id', 'name', 'description', 'created_at', 'updated_at'],
}

conn = sqlite3.connect(sqlite_path)
cursor = conn.cursor()

existing_tables = set()
cursor.execute("SELECT name FROM sqlite_master WHERE type='table'")
for row in cursor.fetchall():
    existing_tables.add(row[0])

with open(output_file, 'w', encoding='utf-8') as f:
    for table, columns in table_mappings.items():
        if table not in existing_tables:
            print(f"Skipping missing table: {table}")
            continue
            
        cursor.execute(f"SELECT * FROM {table}")
        rows = cursor.fetchall()
        sqlite_columns = [description[0] for description in cursor.description]
        
        if not rows:
            print(f"No data in table: {table}")
            continue
            
        print(f"Exporting {table}: {len(rows)} rows")
        
        col_map = {sqlite_columns[i]: i for i in range(len(sqlite_columns))}
        
        for row in rows:
            values = []
            for col in columns:
                if col in col_map:
                    val = row[col_map[col]]
                else:
                    val = None
                    
                if val is None:
                    values.append('NULL')
                elif isinstance(val, (int, float)):
                    values.append(str(val))
                else:
                    values.append("'" + str(val).replace("'", "''") + "'")
            
            sql = f"INSERT INTO {table} ({', '.join(columns)}) VALUES ({', '.join(values)});\n"
            f.write(sql)

conn.close()
print(f"\nExported to {output_file}")
