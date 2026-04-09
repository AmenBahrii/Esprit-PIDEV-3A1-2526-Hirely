# Symfony Interview Evaluation Web Application

A modern web-based interview evaluation and recruitment management system built with **Symfony 7** and **Twig templates**.

---

## 📋 Project Overview

This application manages the full interview and evaluation workflow:
- ✅ User authentication (Recruiter, Candidate/Interviewee, Admin)
- ✅ Interview scheduling and management
- ✅ Dynamic evaluation forms with multiple criteria
- ✅ Dashboard with statistics and upcoming interviews
- ✅ Notification system
- ✅ Evaluation history and reports

---

## 🛠️ Technology Stack

| Component | Technology |
|---|---|
| **Backend** | PHP 8.2+ / Symfony 7.0 |
| **Frontend** | Twig Templates |
| **Database** | MySQL 8.0+ |
| **ORM** | Doctrine 3.0 |
| **Package Manager** | Composer |
| **Build Tool** | Symfony CLI |

---

## 📦 Installation & Setup

### Prerequisites
- PHP 8.2+ with necessary extensions
- MySQL 8.0+
- Composer
- Git (optional)

### 1. Set Up Environment

```bash
cd c:\Users\21625\Desktop\pi symf
cp .env.local.example .env.local
```

Edit `.env.local` and configure your database:
```
DATABASE_URL="mysql://root:password@127.0.0.1:3306/hirely_interview_app?serverVersion=8.0&charset=utf8mb4"
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Create Database

```bash
php bin/console doctrine:database:create
```

### 4. Run Migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 5. Load Sample Data (Fixtures)

```bash
php bin/console doctrine:fixtures:load
```

This will create:
- **Recruiter account:** recruiter@example.com / password
- **Candidate account:** candidate@example.com / password
- Default interview types and evaluation criteria

### 6. Start Development Server

```bash
php -S 127.0.0.1:8000 -t public
```

Visit: **http://localhost:8000**

---

## 🗂️ Project Structure

```
symfony-interview-app/
├── src/
│   ├── Controller/       # Request handlers
│   ├── Entity/          # Doctrine entities (database models)
│   ├── Repository/      # Data access layer
│   ├── Service/         # Business logic
│   ├── Security/        # Authentication
│   └── DataFixtures/    # Sample data
├── templates/           # Twig templates
│   ├── base.html.twig   # Base layout
│   ├── security/        # Login templates
│   ├── recruiter/       # Recruiter views
│   ├── interview/       # Interview management views
│   └── evaluation/      # Evaluation views
├── config/              # Configuration files
├── migrations/          # Database migrations
├── public/              # Web root
├── bin/
│   └── console          # Symfony CLI entry point
└── composer.json        # Project dependencies
```

---

## 💾 Database Schema

### Core Tables

**users** - User accounts and authentication
```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE,
  password VARCHAR(255),
  role VARCHAR(50),
  created_at DATETIME
);
```

**recruiter_profiles** - Recruiter-specific details
```sql
CREATE TABLE recruiter_profiles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNIQUE,
  name VARCHAR(255),
  department VARCHAR(255),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**applications** - Job applications
```sql
CREATE TABLE applications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  job_id VARCHAR(255),
  candidate_name VARCHAR(255),
  status VARCHAR(50),
  created_at DATETIME
);
```

**interview_types** - Interview format definitions
```sql
CREATE TABLE interview_types (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255),
  description TEXT
);
```

**interviews** - Scheduled interviews
```sql
CREATE TABLE interviews (
  id INT PRIMARY KEY AUTO_INCREMENT,
  application_id INT,
  recruiter_id INT,
  interview_type_id INT,
  schedule_date DATETIME,
  duration INT,
  format VARCHAR(50),
  location TEXT,
  meeting_link VARCHAR(500),
  notes TEXT,
  status VARCHAR(50),
  round INT,
  created_at DATETIME
);
```

**evaluation_criteria** - Evaluation scoring criteria
```sql
CREATE TABLE evaluation_criteria (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255),
  description TEXT,
  weight FLOAT
);
```

**interview_evaluations** - Completed evaluations
```sql
CREATE TABLE interview_evaluations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  interview_id INT,
  recruiter_id INT,
  overall_rating FLOAT,
  recommendation VARCHAR(50),
  hire_decision VARCHAR(50),
  strengths TEXT,
  weaknesses TEXT,
  comments TEXT,
  next_steps TEXT,
  created_at DATETIME,
  updated_at DATETIME
);
```

**evaluation_scores** - Individual criterion scores
```sql
CREATE TABLE evaluation_scores (
  id INT PRIMARY KEY AUTO_INCREMENT,
  evaluation_id INT,
  criteria_id INT,
  score FLOAT,
  comment TEXT
);
```

**notifications** - User notifications
```sql
CREATE TABLE notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  message TEXT,
  type VARCHAR(50),
  is_read BOOLEAN,
  created_at DATETIME
);
```

---

## 🔐 Authentication & Authorization

### Roles
- **ROLE_RECRUITER** - Full access to interviews, evaluations, dashboard
- **ROLE_INTERVIEWEE** - View scheduled interviews and personal information
- **ROLE_ADMIN** - Administrative access (future implementation)

### Security Features
- Password hashing with Symfony's PasswordHasher
- Session-based authentication
- CSRF protection on forms
- Role-based access control (security.yaml)

---

## 📍 API Routes & Pages

### Authentication
- `GET /login` - Login page
- `POST /login` - Process login
- `GET /logout` - Logout

### Recruiter Dashboard
- `GET /recruiter-dashboard` - Main dashboard with statistics
- `GET /recruiter-dashboard` - View statistics

### Interview Management
- `GET /interviews` - List all interviews
- `GET /interviews/{id}` - View interview details
- `POST /interviews/{id}/complete` - Mark interview as completed

### Evaluations
- `GET /evaluations` - List all evaluations
- `GET /evaluations/{id}` - View evaluation details
- `GET /evaluations/interview/{interviewId}/form` - Evaluation form
- `POST /evaluations/submit` - Submit evaluation

### Candidate Dashboard (Future)
- `GET /candidate-dashboard` - Candidate view of interviews

---

## 🗄️ Key Entities & Services

### Services
- **UserService** - User creation and management
- **InterviewService** - Interview scheduling and status management
- **EvaluationService** - Evaluation creation and retrieval
- **NotificationService** - Notification management

### Repositories
- UserRepository
- InterviewRepository
- InterviewEvaluationRepository
- ApplicationRepository
- EvaluationCriteriaRepository

---

## 🎨 Frontend / Twig Templates

All templates use **Twig** templating with embedded CSS. Key templates:

- **base.html.twig** - Master layout with navigation and styling
- **security/login.html.twig** - Login form
- **recruiter/dashboard.html.twig** - Dashboard view
- **interview/list.html.twig** - Interview list
- **interview/show.html.twig** - Interview details
- **evaluation/list.html.twig** - Evaluation list
- **evaluation/show.html.twig** - Evaluation details
- **evaluation/form.html.twig** - Dynamic evaluation form with sliders

---

## 🚀 Next Steps & Future Enhancements

### Phase 2: Extended Features
- [ ] Schedule Interview Form & Workflow
- [ ] Job Offer generation
- [ ] Map picker for locations
- [ ] Email notifications integration
- [ ] Interview round management
- [ ] Advanced filters and search

### Phase 3: Candidate Features
- [ ] Candidate dashboard
- [ ] Interview confirmation
- [ ] Status timeline view

### Phase 4: Advanced Features
- [ ] Analytics and reporting
- [ ] Bulk operations
- [ ] API endpoints (REST)
- [ ] Email integration
- [ ] Document uploads
- [ ] Video interview support

---

## 📝 Common Commands

```bash
# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Load sample data
php bin/console doctrine:fixtures:load

# Generate migrations from entities
php bin/console doctrine:migrations:generate

# Clear cache
php bin/console cache:clear

# List all routes
php bin/console debug:router

# Start dev server
php -S 127.0.0.1:8000 -t public
```

---

## 🐛 Troubleshooting

### Database connection error
- Verify MySQL is running
- Check `.env.local` DATABASE_URL
- Confirm database credentials

### Fixtures won't load
```bash
composer require --dev symfony/doctrine-fixtures-bundle
php bin/console doctrine:fixtures:load
```

### Port 8000 already in use
```bash
php -S 127.0.0.1:8001 -t public
```

---

## 📚 Resources

- [Symfony Documentation](https://symfony.com/doc/)
- [Doctrine ORM](https://www.doctrine-project.org/)
- [Twig Documentation](https://twig.symfony.com/)

---

## 📧 Support

For issues or questions, refer to the project documentation or Symfony community resources.

---

**Project Status:** ✅ Core infrastructure complete | 🔄 Features in development
