import requests
r = requests.post('http://localhost:8050/api/login', data='{"email":"admin@oasis.com","password":"password"}', headers={'Accept': 'application/json'})
print(r.status_code, r.text[:300])