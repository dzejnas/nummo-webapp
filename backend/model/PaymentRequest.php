<?php
// ==========================================================
// Model: PaymentRequest
// Represents a pending payment request between two users
// ==========================================================
class PaymentRequest {
    public int $id;
    public int $requester_id;   // user who sent the request
    public int $receiver_id;    // user who receives and can approve it
    public float $amount;
    public ?string $note;
    public string $status;      // pending | accepted | rejected | cancelled
    public string $created_at;
    public ?string $updated_at;

    public function __construct(array $data) {
        $this->id           = $data['id'] ?? 0;
        $this->requester_id = $data['requester_id'] ?? 0;
        $this->receiver_id  = $data['receiver_id'] ?? 0;
        $this->amount       = isset($data['amount']) ? (float)$data['amount'] : 0.0;
        $this->note         = $data['note'] ?? null;
        $this->status       = $data['status'] ?? 'pending';
        $this->created_at   = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updated_at   = $data['updated_at'] ?? null;
    }

    // ✅ Optional helper: check if this request is still actionable
    public function isPending(): bool {
        return $this->status === 'pending';
    }

    // ✅ Optional helper: mark accepted/rejected (you’ll use this in DAO later)
    public function setStatus(string $newStatus): void {
        $allowed = ['pending', 'accepted', 'rejected', 'cancelled'];
        if (in_array($newStatus, $allowed, true)) {
            $this->status = $newStatus;
            $this->updated_at = date('Y-m-d H:i:s');
        }
    }
}
