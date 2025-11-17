# **Nummo WebApp – Backend (Milestone 3)**

### *FlightPHP • MySQL • REST API • DAO/Service Architecture • Swagger Documentation*

This repository contains the backend implementation for **Nummo**, a peer-to-peer payment platform.
The project follows a clean **DAO → Service → Route** architecture, includes full CRUD operations for all core entities, and provides a fully documented **OpenAPI 3.0** specification accessible through Swagger UI.

---

## 🚀 **Features Implemented**

### **✔️ CRUD Endpoints**

* **Users**
* **Contacts**
* **Categories**
* **Merchants**
* **Transactions**

### **✔️ Architecture**

* DAO layer (database queries)
* Service layer (validation + business logic)
* Routes layer (REST endpoints using FlightPHP)
* Centralized error handling

### **✔️ OpenAPI Documentation**

* `GET /api/docs` → OpenAPI YAML
* `GET /docs` → Interactive Swagger UI

### **✔️ Development Enhancements**

* CORS enabled
* JSON response standardization
* Validation layer
* Clean routing structure
* Composer autoloading

---

## 📁 **Project Structure**

```
backend/
│
├── config/
│   └── Database.php
│
├── dao/
│   ├── BaseDao.php
│   ├── UserDao.php
│   ├── CategoryDao.php
│   ├── MerchantDao.php
│   ├── ContactDao.php
│   └── TransactionDao.php
│
├── services/
│   ├── UserService.php
│   ├── CategoryService.php
│   ├── MerchantService.php
│   ├── ContactService.php
│   ├── TransactionService.php
│   └── Validation.php
│
├── routes/
│   ├── users.php
│   ├── categories.php
│   ├── merchants.php
│   ├── transactions.php
│   ├── contacts.php
│   └── docs.php
│
├── public/
│   ├── index.php           # FlightPHP entrypoint
│   └── docs/
│       └── index.html      # Swagger UI (CDN version)
│
├── docs/
│   └── openapi.yaml        # OpenAPI 3.0 spec
│
└── vendor/                 # Composer dependencies
```

---

## 🛠️ **How to Run the Backend**

### **1️⃣ Install dependencies**

```bash
cd backend
composer install
```

### **2️⃣ Start the development server**

```bash
php -S localhost:8000 -t public
```

### **3️⃣ API base URL**

```
http://localhost:8000
```

---

## 📚 **API Documentation**

### **OpenAPI YAML**

```
http://localhost:8000/api/docs
```

### **Swagger UI (Interactive Docs)**

```
http://localhost:8000/docs
```

Includes full documentation for:

* Users
* Contacts
* Categories
* Merchants
* Transactions

---

## 🧪 **Testing the API**

### All Users

```bash
curl http://localhost:8000/api/users
```

### All Categories

```bash
curl http://localhost:8000/api/categories
```

### All Merchants

```bash
curl http://localhost:8000/api/merchants
```

### YAML Output

```bash
curl http://localhost:8000/api/docs
```

---

## 🗄️ **Database**

The backend uses **MySQL** and includes tables for:

* `users`
* `contacts`
* `categories`
* `merchants`
* `transactions`

Configure connection in:

```
backend/config/Database.php
```

---

## 🧰 **Technologies Used**

* PHP 8+
* FlightPHP Framework
* MySQL
* Composer (autoloading)
* OpenAPI 3.0
* Swagger UI via CDN

---

## 👩‍💻 **Author**

**Džejna Sejfić**
International Burch University

Web Programming — *Milestone 3 Submission*

