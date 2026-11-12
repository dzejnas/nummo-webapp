// The only place that talks to the backend. Every service file uses Api.get/post/put/delete.
// - sends the JWT token with every request
// - 401: the session is over -> log out, show one message and go to the login page
// - 403 and other errors: throws an Error with the message from the API and error.status
//   (status 0 = network error: the request may or may not have reached the server)
const Api = {
  async request(method, path, body = null, extraHeaders = {}) {
    const headers = { 'Content-Type': 'application/json', ...extraHeaders };
    if (Auth.isLoggedIn()) {
      headers.Authorization = 'Bearer ' + Auth.getToken();
    }

    let response;
    try {
      response = await fetch(API_BASE_URL + path, {
        method,
        headers,
        body: body === null ? null : JSON.stringify(body),
      });
    } catch (networkError) {
      const error = new Error('The server is not reachable. Please try again later.');
      error.status = 0;
      throw error;
    }

    const data = await response.json().catch(() => ({}));

    if (response.status === 401 && !path.startsWith('/auth/')) {
      // Several requests can fail at once; only the first one shows the message
      if (Auth.isLoggedIn()) {
        Auth.logout();
        // Keep the server's reason when the account was closed; otherwise the session ended
        const closed = (data.error || '').includes('closed');
        UI.toast(closed ? data.error : 'Your session has expired, please log in again', 'warning');
      }
      location.hash = '#login';
      const error = new Error(data.error || 'Unauthorized');
      error.handled = true; // UI.error() will not show it again
      throw error;
    }
    if (!response.ok) {
      const error = new Error(data.error || 'Request failed (' + response.status + ')');
      error.status = response.status;
      throw error;
    }
    return data;
  },

  get(path)          { return this.request('GET', path); },
  post(path, body)   { return this.request('POST', path, body); },
  put(path, body)    { return this.request('PUT', path, body); },
  delete(path)       { return this.request('DELETE', path); },
};
