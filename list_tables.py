import sqlite3

sqlite_path = r'C:\animal-tracking-system-head\backend\database\database.sqlite'
conn = sqlite3.connect(sqlite_path)
cursor = conn.cursor()
cursor.execute("SELECT name FROM sqlite_master WHERE type='table'")
tables = [row[0] for row in cursor.fetchall() if row[0] not in ['sqlite_sequence', 'migrations', 'cache', 'cache_locks']]
print(tables)
conn.close()