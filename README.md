# Hospital Management System (HMS)
## Sankhla Hospital — Online Deployment

[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app)

---

## 🌐 Live System
- **Live URL**: *(Railway deploy ke baad milega)*
- **Login**: Use admin credentials

---

## 🚀 Features
- **Dashboard**: Real-time hospital stats
- **Patient Registration**: EMR, Photo upload
- **OPD & Doctor Consultation**: Digital prescriptions
- **Billing**: GST-ready invoices with print support
- **IPD**: Admissions, Discharges, IPD Dashboard
- **Pharmacy & Lab**: Stock and Test result management
- **Accounting**: Balance sheet, Profit/Loss, Ledger
- **Role-Based Access**: Admin, Doctor, Receptionist, Nurse, Lab, Pharmacist, Accountant
- **Audit Log**: Complete activity tracking

---

## ⚙️ Tech Stack
- **Backend**: PHP 8.2
- **Database**: MySQL 8
- **Frontend**: Bootstrap 5 + jQuery
- **Hosting**: Railway.app
- **Repo**: GitHub

---

## 🔐 Login Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | *(set during deployment)* |
| Doctor | `doctor` | *(set during deployment)* |
| Reception | `reception` | *(set during deployment)* |

---

## 🛠️ Local Development Setup

### 1. Clone Repository
```bash
git clone https://github.com/YOUR_USERNAME/hms.git
cd hms
```

### 2. Setup Database
1. Install XAMPP and start Apache + MySQL
2. Open phpMyAdmin → Create database `hms_db`
3. Import `hms_schema.sql`

### 3. Configure Environment
```bash
cp .env.example .env
```
Edit `.env` with your local database credentials:
```
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=hms_db
```

### 4. Run Locally
Open browser: `http://localhost/hms`

---

## ☁️ Railway Deployment

### 1. Fork/Clone this repo to GitHub

### 2. Create Railway Account
- Go to [railway.app](https://railway.app)
- Login with GitHub

### 3. New Project → Deploy from GitHub
- Select this repository
- Railway auto-detects PHP via `nixpacks.toml`

### 4. Add MySQL Database
- In Railway dashboard: `+ New` → `Database` → `MySQL`
- It auto-sets environment variables

### 5. Set Environment Variables
In Railway project settings → Variables:
```
APP_ENV=production
APP_DEBUG=false
```
*(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT are auto-set by Railway MySQL)*

### 6. Import Database Schema
- Connect to Railway MySQL using TablePlus/DBeaver
- Import `hms_schema.sql`

---

## 📂 Project Structure
```
hms/
├── config/          # Database configuration
├── includes/        # Header, Footer, Sidebar, Auth
├── assets/          # CSS, JS, Images
├── uploads/         # Patient photos (git-ignored)
├── sql/             # Additional SQL migrations
├── *.php            # Application pages
├── hms_schema.sql   # Main database schema
├── nixpacks.toml    # Railway build config
├── railway.json     # Railway deployment config
├── .env.example     # Environment variables template
└── .gitignore       # Git ignore rules
```

---

## 🔒 Security
- All pages protected by session-based authentication
- Database credentials stored in environment variables
- Error display disabled in production
- Role-based access control on all modules

---

**Developed for Sankhla Hospital | PHP + MySQL**
