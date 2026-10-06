# 📦 Stock POS — Construction Materials Inventory & POS System

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-PostgreSQL%20%2F%20Neon-0064a5.svg)](https://neon.tech/)
[![Docker](https://img.shields.io/badge/Docker-Supported-2496ED.svg)](https://www.docker.com/)

A modern, web-based Point of Sale (POS) and Inventory Management System designed specifically for construction material depots and retail stores, supporting both Khmer and English, dual currency (USD & KHR ៛), dynamic Role-Based Access Control (RBAC with Tick Boxes), and Neon Serverless PostgreSQL.

---

## 🚀 លក្ខណៈពិសេសសំខាន់ៗ (Features)

1. **Assign Role & Permissions តាម Tick Box**:
   - Admin អាចកំណត់សិទ្ធិបុគ្គលិកដោយគ្រាន់តែ Tick លើប្រអប់ (POS, Sales, Products, Stock In, Reports, etc.) ដោយមិនបាច់កែកូដឡើយ។
2. **កន្លែងលក់ POS គ្រឿងសំណង់**:
   - គាំទ្រឯកតាពិត (បាវ, ដើម, គីឡូ, ធុង, ប្រអប់, ឡាន, ម៉ែត្រ...)។
   - គិតលុយជាដុល្លារ ($) និងប្រាក់រៀល (៛ 4,100) ស្វ័យប្រវត្តិ។
   - កត់ត្រាការទិញជំពាក់ (Credit) និងប៊ូតុងកត់ត្រាសងលុយ (Mark as Paid)។
3. **ការបោះពុម្ព (Print Options)**:
   - ព្រីនវិក្កយបត្រតូច 80mm (Thermal POS Receipt)។
   - ព្រីនប័ណ្ណដឹកជញ្ជូន និងវិក្កយបត្រខ្នាត A4/A5 មាន ៣ កន្លែងចុះហត្ថលេខា (អ្នកចេញទំនិញ, អ្នកដឹក, មេការទទួល)។
4. **របាយការណ៍ហិរញ្ញវត្ថុ & ចំណេញខាត**:
   - បិទបញ្ជីលុយប្រចាំថ្ងៃ (Daily Cash Close)។
   - របាយការណ៍ប្រចាំខែ (Revenue, COGS, Profit, Debt List)។
   - ទាញយកទិន្នន័យទាំងអស់ជា Excel All-in-One Multi-Sheets (SpreadsheetML)។
