<?php
// ==========================================================
// Model: Transaction
// Represents a single money transfer in Nummo
// ==========================================================
class Transaction {
    public int $id;
    public int $sender_id;
    public int $receiver_id;
    public ?int $merchant_id;
    public ?int $category_id;
    public float $amount;
    public string $note;
    public string $status;
    public string $created_at;

    public function __construct(array $data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) $this->$key = $value;
        }
    }
}
