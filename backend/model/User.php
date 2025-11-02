<?php
// ==========================================================
// Model: User
// Represents a Nummo user entity
// ==========================================================
class User {
    public int $id;
    public string $name;
    public string $email;
    public string $role;
    public string $created_at;

    public function __construct(array $data) {
        $this->id         = $data['id'] ?? 0;
        $this->name       = $data['name'] ?? '';
        $this->email      = $data['email'] ?? '';
        $this->role       = $data['role'] ?? 'user';
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
    }
}
