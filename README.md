# 🐆 PANTHERVERSE — Simple PHP Edition

**JRMSU Academic Community Platform**  
Pure PHP · MySQL · No framework required · Works on Laragon + Vercel

---

## 🚀 Quick Start — Laragon

### Step 1 — Place the folder
```
Copy the `pantherverse-simple` folder to:
C:\laragon\www\pantherverse-simple\
```

### Step 2 — Import the database
1. Open **HeidiSQL** from the Laragon tray
2. Connect (root / no password by default)
3. Click **File → Run SQL File**
4. Select `pantherverse_db.sql` from this folder
5. Done — all tables + demo data are ready!

### Step 3 — Visit the site
Open your browser and go to:
```
http://pantherverse-simple.test
```
Or if the virtual host isn't set up:
```
http://localhost/pantherverse-simple/
```

---

## 🔑 Demo Accounts

| Role       | Email                                        | Password         |
|------------|----------------------------------------------|------------------|
| Admin      | admin@pantherverse.jrmsu.edu.ph              | Admin@12345      |
| Instructor | msantos@pantherverse.jrmsu.edu.ph            | Instructor@12345 |
| Instructor | rbautista@pantherverse.jrmsu.edu.ph          | Instructor@12345 |
| Student    | juan.delacruz@pantherverse.jrmsu.edu.ph      | Student@12345    |
| Student    | ana.reyes@pantherverse.jrmsu.edu.ph          | Student@12345    |
| Student    | mark.villanueva@pantherverse.jrmsu.edu.ph    | Student@12345    |

---

## 📁 File Structure

```
pantherverse-simple/
├── index.php             — Home page
├── login.php             — Login
├── register.php          — Register
├── logout.php            — Logout
├── questions.php         — Browse Q&A
├── question.php          — View a question + answers
├── ask.php               — Post new question
├── edit-question.php     — Edit a question
├── delete-question.php   — Delete a question
├── delete-answer.php     — Delete an answer
├── verify-answer.php     — Instructor: verify answer
├── vote.php              — Ajax voting endpoint
├── profile.php           — User profile
├── settings.php          — Edit profile + password
├── my-questions.php      — My questions list
├── notifications.php     — Notifications
├── forums.php            — Forum categories
├── forum.php             — Forum thread list
├── forum-post.php        — Forum post + replies
├── resources.php         — Browse resources
├── upload-resource.php   — Upload a file
├── download-resource.php — Download (counter)
├── showcase.php          — Project showcase
├── submit-project.php    — Submit a project
├── admin/
│   └── index.php         — Admin dashboard
├── includes/
│   ├── db.php            — DB connection (PDO)
│   ├── auth.php          — Auth + helpers
│   ├── functions.php     — Utility functions
│   ├── header.php        — Navbar + global CSS
│   └── footer.php        — Footer + JS
├── assets/
│   └── logo.png          — PANTHERVERSE logo
├── uploads/              — Uploaded files (auto-created)
├── pantherverse_db.sql   — ✅ Complete database dump
└── vercel.json           — Vercel deployment config
```

---

## ☁️ Deploy to Vercel

### Step 1 — Install Vercel CLI
```bash
npm install -g vercel
```

### Step 2 — Get a MySQL database
Use a free cloud MySQL provider:
- [PlanetScale](https://planetscale.com) (free tier)
- [Railway](https://railway.app) (free tier)
- [Aiven](https://aiven.io) (free tier)

Import `pantherverse_db.sql` to your cloud database.

### Step 3 — Set environment variables
In Vercel dashboard → Settings → Environment Variables, add:
```
DB_HOST     = your-cloud-db-host
DB_NAME     = pantherverse_db
DB_USER     = your-db-username
DB_PASS     = your-db-password
```

### Step 4 — Deploy
```bash
cd pantherverse-simple
vercel
```

---

## 🎨 Theme

- **Colors:** Deep Purple `#0e0720` bg · `#7c3aed` purple · `#f4a623` gold
- **Fonts:** Rajdhani (headings) · Nunito (body) — Google Fonts
- **Icons:** Bootstrap Icons CDN
- **Code highlight:** Highlight.js (Night Owl theme)

---

## ⚠️ Notes

- Sessions use PHP file-based sessions (works on both Laragon and Vercel)
- File uploads go to `uploads/resources/` folder (local only — use S3/Cloudinary for Vercel)
- Passwords are bcrypt hashed (`password_hash` with cost 12)
- All user input is sanitized with `htmlspecialchars()` and PDO prepared statements
- CSRF tokens protect all forms
