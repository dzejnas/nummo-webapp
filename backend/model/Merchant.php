<?php
// ==========================================================
// Model: Merchant
// Represents a registered merchant or business
// ==========================================================
class Merchant {
    public int $id;
    public string $name;
    public ?string $category;
    public ?string $contact_email;
    public string $created_at;

    public function __construct(array $data) {
        $this->id            = $data['id'] ?? 0;
        $this->name          = $data['name'] ?? '';
        $this->category      = $data['category'] ?? null;
        $this->contact_email = $data['contact_email'] ?? null;
        $this->created_at    = $data['created_at'] ?? date('Y-m-d H:i:s');
    }
}
