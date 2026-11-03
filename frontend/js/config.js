// Address of the Nummo REST API.
// Local development: frontend on :8000, backend on :8080. Deployed: same server, under /api.
const API_BASE_URL = location.port === '8000' ? 'http://localhost:8080/api' : '/api';
