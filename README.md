# 🎓 SkillSwap Hub - Campus Peer-to-Peer Skill Exchange Platform

A modern, full-featured web application where students exchange skills reciprocally (e.g., teach Python to learn Figma, teach Calculus to learn React) without exchanging money.

---

## 🚀 Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons
- **Backend**: PHP 8.x (Object-Oriented & PDO Architecture)
- **Database**: Dual Mode Engine:
  - **MySQL** (Default schema in `database.sql` with automatic environment variable configuration)
  - **SQLite** (`config/skillswap.sqlite` with automatic zero-configuration fallback for cloud & lab setups)
- **AI & Chatbot**: SwapBot AI Campus Advisor powered by Google Gemini REST API with high-quality contextual NLP fallback
- **Data Visualization**: Chart.js for Admin Analytics and skill liquidity charts
- **Cloud Ready**: Production Dockerfile included for 1-click deployment on Render, Railway, or Fly.io

---

## ⭐ Key MVP Features

| # | Feature | Description & Implementation |
|---|---|---|
| **1** | **Registration / Login** | Secure student onboarding with `password_hash()` (bcrypt), department, year of study, and session control. Includes 1-click viva demo login. |
| **2** | **Student Profile** | Student details, bio, department, roll ID, reputation badge (star rating), and peer reviews received. |
| **3** | **Add Skills (Teach)** | Form to publish skills the student can teach, with proficiency badges (`Beginner`, `Intermediate`, `Advanced`) and descriptions. |
| **4** | **Learning Skills** | Track what skills the student wants to learn to enable reciprocal matching. |
| **5** | **Search & Filters** | Instant search with multi-parameter filtering by keyword, subject category, and experience level. |
| **6** | **Skill Matching Engine** | Smart reciprocal algorithm that calculates mutual compatibility scores (up to 100% for reciprocal needs) and one-click swap proposals. |
| **7** | **Swap Request Management** | Full state machine (`pending` &rarr; `accepted` &rarr; `completed`), built-in collaboration room for meeting links/notes, and 5-star peer reviews. |
| **8** | **Admin Supervision & Analytics** | Protected admin dashboard with **Chart.js** bar & doughnut graphs (Category Supply vs. Demand, Swap Status Distribution), and moderation tools. |

---

## 🤖 Campus AI Chatbot (`SwapBot`)

- Accessible via the floating widget at the bottom right of all pages.
- Can be powered live by Google Gemini API by setting `GEMINI_API_KEY` in your environment or PHP configuration.
- Features a built-in intelligent Campus Advisor knowledge engine for 100% offline reliability during evaluations.

---

## ☁️ How to Deploy on the Cloud from GitHub (100% Free)

### Option 1: 1-Click Deploy on Render (Recommended)

1. Push this repository to your GitHub account (`https://github.com/nikitamarkand48-droid/skillswap-hub`).
2. Log into [Render.com](https://render.com) using your GitHub account.
3. Click **New +** &rarr; **Web Service**.
4. Select your **skillswap-hub** repository from the list.
5. Render will automatically detect the **Dockerfile**.
6. Select the **Free** instance type and click **Create Web Service**.
7. In ~2 minutes, your project will be live on an HTTPS link like `https://skillswap-hub.onrender.com`!

### Option 2: 1-Click Deploy on Railway

1. Push this repository to GitHub.
2. Go to [Railway.app](https://railway.app) and sign in with GitHub.
3. Click **New Project** &rarr; **Deploy from GitHub repo**.
4. Select your repository. Railway will build using Docker/Nixpacks and generate a live domain automatically.

---

## 🏃 Local Setup Instructions

### Local Option 1: Double-Click Batch File (Fastest)
Simply double-click `start-server.bat` in the project folder to start the server at `http://localhost:8000`.

### Local Option 2: XAMPP (Apache + MySQL)
1. Copy or link this folder into `C:\xampp\htdocs\skillswap`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin` and import `database.sql`.
4. Open `http://localhost/skillswap` in your browser.

---

## 🔑 Demo Accounts for College Viva Presentation

| Role | Name | Email | Password | What They Teach / Want |
|---|---|---|---|---|
| **Student 1** | Aarav Sharma | `aarav@college.edu` | `password123` | Teaches: Python, Django <br> Wants: UI/UX Design |
| **Student 2** | Priya Patel | `priya@college.edu` | `password123` | Teaches: Figma, Design <br> Wants: Python (100% Match!) |
| **Student 3** | Rohan Mehta | `rohan@college.edu` | `password123` | Teaches: Machine Learning <br> Wants: React.js |
| **Student 4** | Ananya Sen | `ananya@college.edu` | `password123` | Teaches: React.js, CSS <br> Wants: Machine Learning |
| **Admin** | Administrator | `admin@skillswap.edu` | `Admin@123` | Access to Admin Analytics & User Moderation |

> **Pro Tip**: Use the **"Quick Demo"** dropdown in the navigation bar to switch between test student profiles with a single click!
