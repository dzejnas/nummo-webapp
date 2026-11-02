// Keeps the JWT token and the logged-in user in localStorage.
const Auth = {
  TOKEN_KEY: 'nummo_token',
  USER_KEY: 'nummo_user',

  save(token, user) {
    localStorage.setItem(this.TOKEN_KEY, token);
    localStorage.setItem(this.USER_KEY, JSON.stringify(user));
  },

  getToken() {
    return localStorage.getItem(this.TOKEN_KEY);
  },

  getUser() {
    const user = localStorage.getItem(this.USER_KEY);
    return user ? JSON.parse(user) : null;
  },

  updateUser(user) {
    localStorage.setItem(this.USER_KEY, JSON.stringify(user));
  },

  isLoggedIn() {
    return this.getToken() !== null;
  },

  isAdmin() {
    const user = this.getUser();
    return user !== null && user.role === 'admin';
  },

  logout() {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
  },
};
