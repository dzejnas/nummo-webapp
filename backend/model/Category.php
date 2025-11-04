<?php
// ==========================================================
// Model: Category
// Represents a transaction category (Food, Transport, etc.)
// ==========================================================
class Category {
    public int $id;
    public string $name;

    public function __construct(array $data) {
        $this->id   = $data['id'] ?? 0;
        $this->name = $data['name'] ?? '';
    }
}
