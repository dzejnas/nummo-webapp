<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Settings.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../utils/ApiException.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/UserService.php';

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Registration, login and JWT tokens.
class AuthService
{
    private const TOKEN_HOURS = 24;

    public function register(array $data): array
    {
        // New accounts are always normal users with a 0 balance, whatever the request says
        unset($data['role'], $data['balance']);
        $user = (new UserService())->create($data);

        Logger::info("Registration: user={$user['id']} email={$user['email']}");
        return ['token' => $this->createToken($user), 'user' => $user];
    }

    public function login(array $data): array
    {
        $email    = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        $user = (new UserDao())->getByEmail($email);
        if ($user === null || !password_verify($password, $user['password'])) {
            Logger::error("Failed login: email=$email");
            // Same message for both cases, so nobody can find out which emails exist
            throw new ApiException('Wrong email or password', 401);
        }

        if ($user['closed_at'] !== null) {
            Logger::error("Failed login (closed account): user={$user['id']}");
            throw new ApiException('This account has been closed', 401);
        }
        unset($user['password']);
        Logger::info("Login: user={$user['id']} role={$user['role']}");
        return ['token' => $this->createToken($user), 'user' => $user];
    }

    // Checks the token and returns the current user. 401 if missing, invalid, expired, or the
    // account was closed after the token was issued. Role and name come from the database, so an
    // admin's changes apply immediately instead of when the token expires.
    public function userFromToken(string $token): array
    {
        try {
            $payload = JWT::decode($token, new Key(Settings::get('jwt_secret'), 'HS256'));
        } catch (ExpiredException $e) {
            throw new ApiException('Your session has expired, please log in again', 401);
        } catch (Throwable $e) {
            throw new ApiException('Invalid token, please log in again', 401);
        }

        $user = (new UserDao())->getById((int) $payload->user->id);
        if ($user === null || $user['closed_at'] !== null) {
            throw new ApiException('This account has been closed', 401);
        }
        return ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
    }

    private function createToken(array $user): string
    {
        $payload = [
            'iat'  => time(),
            'exp'  => time() + self::TOKEN_HOURS * 3600,
            'user' => [
                'id'    => (int) $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ];
        return JWT::encode($payload, Settings::get('jwt_secret'), 'HS256');
    }
}
