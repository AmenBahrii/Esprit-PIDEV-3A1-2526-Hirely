# SETUP GUIDE - Symfony Interview Evaluation Web Application

## Quick Start (5 Minutes)

### Step 1: Navigate to Project
```bash
cd c:\Users\21625\Desktop\pi symf
```

### Step 2: Configure Environment
```bash
# Copy example environment file
cp .env .env.local

# Edit .env.local and set your database connection
# Change this line:
# DATABASE_URL="mysql://root:password@127.0.0.1:3306/hirely_interview_app?serverVersion=8.0&charset=utf8mb4"
```

### Step 3: Install & Setup
```bash
# Install dependencies
composer install

# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Load sample data
php bin/console doctrine:fixtures:load
```

### Step 4: Run Application
```bash
# Start development server
php -S 127.0.0.1:8000 -t public
```

Visit: **http://localhost:8000**

---

## 🔑 Default Credentials

| Role | Email | Password |
|---|---|---|
| Recruiter | recruiter@example.com | password |
| Candidate | candidate@example.com | password |

---

## 📋 What's Included

### ✅ Completed Components

**Core Infrastructure:**
- ✅ Symfony 7 setup with Doctrine ORM
- ✅ MySQL database schema (9 tables)
- ✅ User authentication with roles
- ✅ Session-based security

**Entities (Database Models):**
- ✅ User (users)
- ✅ RecruiterProfile
- ✅ Application
- ✅ InterviewType
- ✅ Interview
- ✅ EvaluationCriteria
- ✅ InterviewEvaluation
- ✅ EvaluationScore
- ✅ Notification

**Services (Business Logic):**
- ✅ UserService - User creation/authentication
- ✅ InterviewService - Schedule, retrieve, update interviews
- ✅ EvaluationService - Create and manage evaluations
- ✅ NotificationService - Notification management

**Controllers (Request Handlers):**
- ✅ LoginController - Authentication
- ✅ RecruiterDashboardController - Dashboard & statistics
- ✅ InterviewController - Interview listing and details
- ✅ EvaluationController - Evaluation management

**Frontend (Twig Templates):**
- ✅ Base layout (base.html.twig) with responsive design
- ✅ Login page (security/login.html.twig)
- ✅ Recruiter dashboard (recruiter/dashboard.html.twig)
- ✅ Interview list (interview/list.html.twig)
- ✅ Interview details (interview/show.html.twig)
- ✅ Evaluation list (evaluation/list.html.twig)
- ✅ Evaluation details (evaluation/show.html.twig)
- ✅ Evaluation form (evaluation/form.html.twig) with dynamic criteria

**Configuration:**
- ✅ Routes setup
- ✅ Security configuration (authentication, roles, access control)
- ✅ Doctrine configuration
- ✅ Twig configuration
- ✅ Service configuration

---

## 🗺️ User Workflows

### Recruiter Workflow
1. Login with recruiter credentials
2. View dashboard with statistics
3. See upcoming interviews for today
4. View pending evaluations
5. Click "View" on interview to see details
6. Complete evaluation form with criteria scores
7. Submit evaluation

### Navigation Paths
```
Login ↓
Dashboard (Main hub with stats)
  ├── Interviews (Interview list page)
  │   └── Interview Details (mark as complete, create evaluation)
  │       └── Evaluation Form (fill criteria, submit)
  └── Evaluations (View all evaluations)
      └── Evaluation Details (view submitted evaluation)
```

---

## 🔧 Working with the Application

### Adding New Interview Types
```bash
# Use Symfony console
php bin/console make:entity InterviewType

# Then add via database or through admin interface (future)
```

### Creating New Users (Recruiters)
Currently done via AppFixtures. To add manually:
```bash
# Use a database client or Symfony command
php bin/console make:user
```

### Viewing Database
```bash
# MySQL command line
mysql -u root -p hirely_interview_app

# Show all tables
SHOW TABLES;

# View interviews
SELECT * FROM interviews;
```

---

## 📊 Key Database Queries

```sql
-- All interviews for a recruiter
SELECT i.*, a.candidate_name FROM interviews i
JOIN applications a ON i.application_id = a.id
WHERE i.recruiter_id = 1
ORDER BY i.schedule_date DESC;

-- Pending evaluations
SELECT e.*, i.schedule_date, a.candidate_name 
FROM interview_evaluations e
JOIN interviews i ON e.interview_id = i.id
JOIN applications a ON i.application_id = a.id
WHERE e.overall_rating IS NULL
ORDER BY i.schedule_date DESC;

-- Evaluation scores for an evaluation
SELECT es.score, ec.name FROM evaluation_scores es
JOIN evaluation_criteria ec ON es.criteria_id = ec.id
WHERE es.evaluation_id = 1;
```

---

## 🚀 Next Features to Build

### Phase 2: Interview Scheduling (Next)
- [ ] Schedule Interview Page (from dashboard)
- [ ] Select application
- [ ] Select date/time
- [ ] Choose interview type
- [ ] Set virtual or in-person
- [ ] Add meeting link or location
- [ ] Submit and create Interview record

### Phase 3: Candidate Dashboard
- [ ] Candidate view of scheduled interviews
- [ ] Status updates
- [ ] Notifications about interviews

### Phase 4: Enhanced Features
- [ ] Email notifications
- [ ] Map picker for locations
- [ ] Interview reminders
- [ ] Document uploads
- [ ] Advanced reporting
- [ ] Bulk operations

---

## 🐛 Useful Symfony Commands

```bash
# List all available commands
php bin/console list

# View routes (all URLs in system)
php bin/console debug:router

# View container services
php bin/console debug:container

# Generate entity migration
php bin/console doctrine:make:migration

# Execute migrations
php bin/console doctrine:migrations:migrate

# Rollback last migration
php bin/console doctrine:migrations:migrate prev

# Clear application cache
php bin/console cache:clear

# Create new controller (interactive)
php bin/console make:controller

# Create new entity (interactive)
php bin/console make:entity

# Generate database from entities
php bin/console doctrine:schema:update --force
```

---

## 📁 File Locations Reference

- **Templates:** `templates/`
- **Controllers:** `src/Controller/`
- **Entities:** `src/Entity/`
- **Services:** `src/Service/`
- **Repositories:** `src/Repository/`
- **Routes:** `config/routes.yaml`
- **Security:** `config/packages/security.yaml`
- **Database Config:** `config/packages/doctrine.yaml`
- **Environment:** `.env.local`

---

## ❓ FAQ

**Q: How do I add a new evaluation criterion?**
A: Add it to the database or AppFixtures, then update the evaluation form template.

**Q: Can I change the database?**
A: Yes, update DATABASE_URL in `.env.local` and run `php bin/console doctrine:database:create`

**Q: How do I reset the database?**
A: 
```bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load --append
```

**Q: Where is the login validation?**
A: `src/Security/AppAuthenticator.php` and `config/packages/security.yaml`

**Q: How do I add more sample data?**
A: Edit `src/DataFixtures/AppFixtures.php` and run `php bin/console doctrine:fixtures:load`

---

## 💾 Database Backup

```bash
# Backup
mysqldump -u root -p hirely_interview_app > backup.sql

# Restore
mysql -u root -p hirely_interview_app < backup.sql
```

---

## 🔒 Security Notes

- ✅ Passwords are hashed using Symfony's PasswordHasher
- ✅ CSRF tokens protect forms
- ✅ Role-based access control enforced
- ✅ SQL injection protected via Doctrine ORM

**TODO for Production:**
- Add HTTPS
- Set strong APP_SECRET
- Configure proper CORS
- Add rate limiting
- Enable security headers
- Setup logging/monitoring

---

## 📞 Getting Help

1. Check Symfony logs: `var/log/`
2. Run: `php bin/console debug:router` to verify routes
3. Check `.env.local` database connection
4. Ensure MySQL is running and accessible
5. Review `config/packages/` for configuration

---

## ✨ You're Ready!

Your Symfony Interview Evaluation application is ready to use. Start with:
```bash
php -S 127.0.0.1:8000 -t public
```

Then open: **http://localhost:8000**

Login with: **recruiter@example.com / password**

Happy coding! 🎉
