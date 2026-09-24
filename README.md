# 📦 Stock POS — Construction Materials Inventory & POS System

[![Live Demo](https://img.shields.io/badge/demo-online-brightgreen.svg)](https://inventory-system-j47r.onrender.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://www.php.net/)
[![Docker](https://img.shields.io/badge/Docker-Supported-2496ED.svg)](https://www.docker.com/)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](#license)

A modern, web-based Point of Sale (POS) and Inventory Management System designed specifically for construction material depots and retail stores, supporting both Khmer and English, dual currency (USD & KHR), and modern Cambodian payment methods (KHQR & Cash).

---

## 🚀 Live Demo

- **URL:** [https://inventory-system-j47r.onrender.com](https://inventory-system-j47r.onrender.com)
- **Demo Account:**
  - **Username:** `Super Admin`
  - **Password:** *(configured during setup)*

---

## ✨ Key Features

- **📊 Comprehensive Dashboard:**
  - Real-time sales statistics (Daily & Monthly).
  - Total inventory stock count and valuation in dual currency (USD and KHR ៛).
  - Low stock warning alerts (ជិតអស់ពីស្តុក).
  - Monthly top-selling products overview.

- **🛒 Point of Sale (POS):**
  - Fast billing and invoice generation.
  - Multi-method payments: **CASH** and **KHQR**.
  - Dual-currency exchange rate calculation ($ / ៛).

- **📦 Inventory & Stock Control:**
  - Stock In (នាំចូលស្តុក) from suppliers.
  - Stock Adjustment (កែតម្រូវស្ដុក) and Movement Tracking (ចលនាស្តុក).
  - Categories and Supplier management.

- **📑 Invoicing & Reporting:**
  - Real-time recent sales ledger with quick-view invoice modals.
  - Daily register closing reports (បិទបញ្ជីប្រចាំថ្ងៃ).
  - Monthly sales & profit/loss reports.

- **👥 Multi-User & Access Control:**
  - Role-based permissions (e.g., Super Admin, Cashier).
  - Individual cashier shift and transaction logs.

- **🐳 Deployment Ready:**
  - Includes `Dockerfile` for containerized environments.
  - Automated database migration via `install.php` and `install.sql`.

---

## 🛠️ Tech Stack

- **Backend:** PHP (8.0+)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap
- **Containerization:** Docker
- **Deployment Platform:** Render

---

## 📁 Project Structure

```text
inventory-system/
├── config/             # Database and environment configurations
├── includes/           # Header, footer, sidebar, and helper functions
├── modules/            # Functional modules (products, sales, reports, stock)
├── Dockerfile          # Docker container configuration
├── index.php           # Main application dashboard
├── install.php         # Initial web-based database installer
├── install.sql         # Database schema and initial seed data
├── login.php           # User authentication
└── logout.php          # Session termination
