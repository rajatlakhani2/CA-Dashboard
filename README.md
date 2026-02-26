# CA Dashboard (Laravel)

A comprehensive practice management dashboard for Chartered Accountants, built with Laravel 12.

## 🌟 Features

*   **Client Management**: 
    *   Full CRUD for client details.
    *   Excel Import/Export for bulk operations.
    *   Service mapping (assign recurring services to clients).
*   **Task Management (Kanban)**:
    *   Visual Kanban board for task tracking.
    *   Drag-and-drop status updates.
    *   Due date tracking and priority assignment.
*   **Service Dues Engine**:
    *   Automated tracking of recurring compliances (GST, IT Returns, etc.).
    *   Generates "Service Due" records based on frequency (Monthly, Quarterly, Annually).
*   **Invoicing**:
    *   Raise invoices linked to Tasks or Service Dues.
    *   PDF Generation & Download.
    *   Track payment status (Raised, Paid, Overdue).
*   **Personal Renewals**:
    *   Track personal payments (LIC, Loan, Medical).
    *   **Auto-Recurring**: Automatically generates next due date for recurring payments.
    *   **Reminders**: Send WhatsApp reminders instantly.
    *   **Calendar**: Interactive calendar with resize controls.
*   **Reports**:
    *   Financial, Compliance, Service, and Client reports.

## 🛠 Installation

1.  **Clone & Install Dependencies**
    ```bash
    git clone <repo-url>
    cd ca-dashboard
    composer install
    npm install
    ```

2.  **Environment Setup**
    ```bash
    copy .env.example .env
    php artisan key:generate
    touch database/db.sqlite
    ```
    *Update `.env` to set `DB_DATABASE` to your absolute path if needed.*

3.  **Database Migration & Seeding**
    ```bash
    php artisan migrate
    php artisan db:seed
    ```

4.  **Run Application**
    ```bash
    npm run dev
    # In a separate terminal:
    php artisan serve
    ```

## 📂 Key Directories

*   `app/Models`: Core business objects (Client, Task, Invoice, ServiceDue).
*   `app/Http/Controllers`: Logic for Web routes.
*   `resources/views`: Blade templates (Tailwind CSS styled).
*   `routes/web.php`: Application routes.

## 🔧 Commands

*   `php artisan serve`: Start the dev server.
*   `php artisan queue:work`: Run background jobs (if configured).

## 📄 License
MIT License.
