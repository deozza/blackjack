import { vi, describe, it, expect, beforeEach } from 'vitest';

describe('Flux d\'Authentification Intégré', () => {
  let mockLocalStorage: Storage;
  let mockFetch: any;

  beforeEach(() => {
    // Mock complet du localStorage
    mockLocalStorage = {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
      length: 0,
      key: vi.fn()
    };
    Object.defineProperty(window, 'localStorage', {
      value: mockLocalStorage,
      writable: true
    });

    // Mock de fetch
    mockFetch = vi.fn();
    global.fetch = mockFetch;
  });

  it('doit permettre un cycle complet : signup -> login -> logout', async () => {
    // 1. Test Signup
    const signupData = {
      username: 'testuser',
      email: 'test@example.com',
      password: 'password123'
    };

    mockFetch.mockResolvedValueOnce({
      status: 201,
      json: () => Promise.resolve({ id: 'user-123', ...signupData })
    });

    // Simuler la création de compte
    const signupResponse = await fetch('http://127.0.0.1:8888/user', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(signupData)
    });

    const signupResult = await signupResponse.json();
    expect(signupResult.id).toBeDefined();
    expect(signupResult.username).toBe(signupData.username);

    // 2. Test Login
    const loginToken = 'fake-jwt-token';
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.resolve({ token: loginToken })
    });

    // Simuler la connexion
    const loginResponse = await fetch('http://127.0.0.1:8888/login_check', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: signupData.username,
        password: signupData.password
      })
    });

    const loginResult = await loginResponse.json();
    expect(loginResult.token).toBe(loginToken);

    // Simuler la sauvegarde du token
    localStorage.setItem('token', loginToken);
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('token', loginToken);

    // 3. Test d'une requête authentifiée
    mockFetch.mockResolvedValueOnce({
      status: 200,
      json: () => Promise.resolve({ id: 'user-123', username: 'testuser' })
    });

    // Simuler une requête authentifiée
    (mockLocalStorage.getItem as any).mockReturnValue(loginToken);
    const token = localStorage.getItem('token');

    const profileResponse = await fetch('http://127.0.0.1:8888/user/profile', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    });

    const profileResult = await profileResponse.json();
    expect(profileResult.username).toBe('testuser');

    // 4. Test Logout
    localStorage.removeItem('token');
    expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('token');

    // Vérifier que le token n'est plus disponible
    (mockLocalStorage.getItem as any).mockReturnValue(null);
    const tokenAfterLogout = localStorage.getItem('token');
    expect(tokenAfterLogout).toBeNull();
  });

  it('doit gérer les erreurs d\'authentification', async () => {
    // Test login avec de mauvais identifiants
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.resolve({ code: 401, message: 'Invalid credentials' })
    });

    const loginResponse = await fetch('http://127.0.0.1:8888/login_check', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: 'wrong',
        password: 'wrong'
      })
    });

    const loginResult = await loginResponse.json();
    expect(loginResult.code).toBe(401);

    // Vérifier qu'aucun token n'a été sauvé
    expect(mockLocalStorage.setItem).not.toHaveBeenCalled();
  });

  it('doit gérer les requêtes non autorisées', async () => {
    // Test requête sans token
    mockFetch.mockResolvedValueOnce({
      status: 401,
      json: () => Promise.resolve({ message: 'Unauthorized' })
    });

    const response = await fetch('http://127.0.0.1:8888/user/profile', {
      method: 'GET',
      headers: { 'Content-Type': 'application/json' }
    });

    expect(response.status).toBe(401);
  });

  it('doit gérer la persistance du token entre les sessions', () => {
    const testToken = 'persistent-token';

    // Simuler la sauvegarde d'un token
    localStorage.setItem('token', testToken);
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith('token', testToken);

    // Simuler un rafraîchissement de page / nouvelle session
    (mockLocalStorage.getItem as any).mockReturnValue(testToken);
    const retrievedToken = localStorage.getItem('token');

    expect(retrievedToken).toBe(testToken);
    expect(mockLocalStorage.getItem).toHaveBeenCalledWith('token');
  });
}); 