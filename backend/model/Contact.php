<?php
// ==========================================================
// Model: Contact
// Represents a user's friend/contact in Nummo
// ==========================================================
class Contact {
    public int $id;
    public int $user_id;
    public int $friend_user_id;
    public string $status;
    public string $created_at;

    public function __construct(array $data) {
        $this->id             = $data['id'] ?? 0;
        $this->user_id        = $data['user_id'] ?? 0;
        $this->friend_user_id = $data['friend_user_id'] ?? 0;
        $this->status         = $data['status'] ?? 'pending';
        $this->created_at     = $data['created_at'] ?? date('Y-m-d H:i:s');
    }
}
