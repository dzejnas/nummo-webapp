# Nummo — Entity Relationship Diagram

The same model is in [`erd.dbml`](erd.dbml) (open it on dbdiagram.io).

```mermaid
erDiagram
    USERS ||--o{ CONTACTS : "has"
    USERS ||--o{ TRANSACTIONS : "sends"
    USERS |o--o{ TRANSACTIONS : "receives"
    MERCHANTS |o--o{ TRANSACTIONS : "is paid in"
    CATEGORIES |o--o{ TRANSACTIONS : "classifies"
    CATEGORIES |o--o{ MERCHANTS : "groups"
    USERS ||--o{ PAYMENT_REQUESTS : "requests / pays"

    USERS {
        int id PK
        varchar name
        varchar email UK
        varchar password
        enum role "user or admin"
        decimal balance
        char currency
        timestamp created_at
    }
    CATEGORIES {
        int id PK
        varchar name UK
    }
    MERCHANTS {
        int id PK
        varchar name UK
        int category_id FK
        varchar contact_email
        timestamp created_at
    }
    CONTACTS {
        int id PK
        int user_id FK
        int friend_user_id FK
        enum status "pending, accepted, blocked"
        timestamp created_at
    }
    TRANSACTIONS {
        int id PK
        int sender_id FK
        int receiver_id FK "null when paying a merchant"
        int merchant_id FK "null when paying a user"
        int category_id FK
        decimal amount
        char currency
        varchar note
        enum status "pending, completed, failed"
        timestamp created_at
    }
    PAYMENT_REQUESTS {
        int id PK
        int requester_id FK
        int payer_id FK
        decimal amount
        varchar note
        enum status "pending, paid, declined"
        timestamp created_at
    }
```

## Relationships

| Relationship | Type | Meaning |
|---|---|---|
| users → contacts | 1 : N (twice) | A contact row links a user (`user_id`) to another user (`friend_user_id`). |
| users → transactions | 1 : N | Every transaction has a sender. The receiver is a user **or** a merchant. |
| merchants → transactions | 1 : N | A payment to a shop sets `merchant_id` instead of `receiver_id`. |
| categories → transactions | 1 : N | Optional spending category of a payment. |
| categories → merchants | 1 : N | Default category of a merchant. |
| users → payment_requests | 1 : N (twice) | `requester_id` asks `payer_id` to pay an amount. |
