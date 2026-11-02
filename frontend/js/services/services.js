// Frontend services: one object per backend resource. Only these call the API;
// page code (js/pages) calls services and updates the HTML.

const AuthService = {
  async login(email, password) {
    const result = await Api.post('/auth/login', { email, password });
    Auth.save(result.token, result.user);
    return result.user;
  },
  async register(data) {
    const result = await Api.post('/auth/register', data);
    Auth.save(result.token, result.user);
    return result.user;
  },
  logout() {
    Auth.logout();
  },
};

const ProfileService = {
  get()          { return Api.get('/me'); },
  update(data)   { return Api.put('/me', data); },
  spending()     { return Api.get('/me/spending'); },
};

const UserService = {
  getAll()           { return Api.get('/users'); },
  create(data)       { return Api.post('/users', data); },
  update(id, data)   { return Api.put('/users/' + id, data); },
  remove(id)         { return Api.delete('/users/' + id); },
};

const CategoryService = {
  getAll()           { return Api.get('/categories'); },
  create(data)       { return Api.post('/categories', data); },
  update(id, data)   { return Api.put('/categories/' + id, data); },
  remove(id)         { return Api.delete('/categories/' + id); },
};

const MerchantService = {
  getAll()           { return Api.get('/merchants'); },
  create(data)       { return Api.post('/merchants', data); },
  update(id, data)   { return Api.put('/merchants/' + id, data); },
  remove(id)         { return Api.delete('/merchants/' + id); },
};

const ContactService = {
  getAll()           { return Api.get('/contacts'); },
  addByEmail(email)  { return Api.post('/contacts', { email }); },
  setStatus(id, status) { return Api.put('/contacts/' + id, { status }); },
  remove(id)         { return Api.delete('/contacts/' + id); },
};

const TransactionService = {
  getAll(filters = {}) {
    const query = new URLSearchParams(Object.entries(filters).filter(([, value]) => value !== ''));
    return Api.get('/transactions' + (query.toString() ? '?' + query : ''));
  },
  create(data)       { return Api.post('/transactions', data); },
  update(id, data)   { return Api.put('/transactions/' + id, data); },
  remove(id)         { return Api.delete('/transactions/' + id); },
};

const PaymentRequestService = {
  getAll()           { return Api.get('/payment-requests'); },
  create(data)       { return Api.post('/payment-requests', data); },
  update(id, data)   { return Api.put('/payment-requests/' + id, data); },
  remove(id)         { return Api.delete('/payment-requests/' + id); },
};

const RateService = {
  convert(amount, from, to) {
    return Api.get('/rates/convert?' + new URLSearchParams({ amount, from, to }));
  },
  currencies()       { return Api.get('/rates/currencies'); },
};
