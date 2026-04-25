# 🧠 AI-Based Blog Platform

A professional, high-performance blog publishing platform featuring hybrid human-AI content generation, a responsive Tailwind CSS administrative backend, and modern aesthetics.

## 🚀 Key Features

-   **🤖 Hybrid AI Content Creator**: Generate full blog articles, snappy excerpts, and SEO-friendly metadata using Google's Gemini AI.
-   **🎨 Modern Tailwind Admin**: A fully responsive, mobile-first administrative command center.
-   **🏷️ Structured Taxonomy**: Dedicated category management system for clean site organization.
-   **📈 Social Engagement**: Built-in tracking for Likes, Shares, and Comments.
-   **🔐 Role-Based Access (RBAC)**: Fine-grained permissions for Super Admins, Admins, and Authors.
-   **🖼️ Media Library**: Physical image upload support for featured blog images, site logos, and favicons.

## 🛠️ Tech Stack

-   **Backend**: PHP 8.0+ / MySQL
-   **Frontend**: Vanilla HTML/JS + Tailwind CSS (Admin Panel)
-   **AI Engine**: Google Gemini API (Flash 1.5)
-   **Styling**: Custom Modern Glassmorphism & Formal Professional Layouts

## 📦 Installation & Setup

1.  **Clone the Repository**:
    ```bash
    git clone https://github.com/mehedi-11/AI-Based-Blogsite.git
    ```

2.  **Database Configuration**:
    -   Import the `database.sql` file into your local MySQL server (XAMPP/WAMP).
    -   Alternatively, run `php setup_db.php` from your terminal to automate the schema creation and directory setup.

3.  **App Configuration**:
    -   Copy `includes/config.example.php` to `includes/config.php`.
    -   Add your **Gemini API Key** and your local **Database Credentials** in `includes/config.php`.
    -   Adjust the `BASE_URL` to match your local installation folder.
    -   **Note**: `includes/config.php` is ignored by Git for security.

4.  **Local Development**:
    -   Ensure your server's rewrite rules allow access to the folder.
    -   **Default Login**: 
        -   Username: `Mehedi19`
        -   Password: `Mehedi@129221` (or as configured in the `users` table).

## 🔐 Security Note

Always keep your `GEMINI_API_KEY` private. Never upload your `includes/config.php` to public repositories. This project uses a `.gitignore` file to ensure sensitive information stays local.

## 📄 License

Developed by **MD Mehedi Hasan**.
