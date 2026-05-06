# Secure Role-Based E-Commerce System 🛒🔒

A full-stack PHP web application featuring a role-based shopping system, secure authentication, OAuth integrations, and AI-powered responses. 


## ✨ Features
* **Role-Based Access Control (RBAC):** Three user tiers (Owner, Admin, User) with distinct dashboards and permissions.
* **Secure Authentication:** * Traditional Email/Password login (with hashed passwords).
  * GitHub & Google OAuth integration.
* **E-Commerce Functionality:** Product management (CRUD for admins) and shopping cart system for users.
* **AI Integration:** Smart responses powered by the Gemini API.
* **Security First:** Environment variables (`.env`) for all sensitive data, prepared statements to prevent SQL injection, and AJAX for dynamic secure updates.

## 🛠️ Technologies Used
* **Backend:** PHP, MySQL (PDO / Prepared Statements)
* **Frontend:** HTML5, CSS3 (Flexbox/Grid), JavaScript (AJAX)
* **Integrations:** GitHub API, Google API, Gemini AI API

## 🚀 How to Run Locally
1. Clone the repository: `git clone https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git`
2. Create a `.env` file in the root directory based on the provided `.env.example`.
3. Add your database credentials and API keys to the `.env` file.
4. Import the database structure from `database/schema.sql` into your MySQL server.
5. Start your local server (e.g., XAMPP, MAMP) and navigate to the project folder.
