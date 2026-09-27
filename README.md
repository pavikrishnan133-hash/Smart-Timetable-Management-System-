# Smart Academic Timetable Management System
### Department of B.Voc (Software Development & System Administration)

A complete, enterprise-grade, responsive full-stack academic timetable creation and management web application engineered for the **B.Voc SD & SA Department**.

Built with **HTML5, CSS3, JavaScript, PHP 8.2**, and **MySQL**, designed to run smoothly on **XAMPP** or standard Apache/PHP environments.

---

## 🌟 Core System Features

1. **Admin Authentication & Security**:
   - Secure session-based authentication with bcrypt password encryption.
   - Default administrator account: `admin` / `admin123`.

2. **Executive Academic Dashboard**:
   - Live KPI metric cards for Staff, Active Subjects, Academic Years, Classrooms & Labs, Scheduled Hours.
   - Timetable Quality Score radial gauge with metric breakdown bars.
   - Real-time Conflict Health status alert.
   - Quick action shortcuts.

3. **Dynamic Staff & Faculty Management**:
   - Full CRUD for department faculty and HOD.
   - Configurable maximum weekly workload limits (Default: HOD = 14 hours, Faculty = 18 hours, fully editable).
   - **Interactive Staff Availability Matrix**: Clickable day-by-period grid to set faculty availability status.
   - Custom timetable color tagging for quick visual identification.

4. **Dynamic Subject Management**:
   - Zero hardcoding: Add, edit, or delete any course in the curriculum.
   - Categories: `Major Theory`, `Major Lab`, `Skill Theory`, `Skill Lab`, `Common Theory`, `Project`.
   - Dynamic weekly hours, primary faculty assignment, preferred room type, and consecutive period flags.

5. **Academic Year & Batch Management**:
   - 1st Year, 2nd Year, 3rd Year B.Voc (SD & SA) batch and semester tracking.

6. **Classrooms & Specialized Labs**:
   - Theory Lecture Halls (`LH-201`, `LH-202`, `LH-203`).
   - Computer Labs (`SD Lab 1`, `SA Lab 2`).
   - Hardware & IoT Labs (`Hardware Lab`).
   - Seating/PC capacities and location tracking.

7. **Configurable Schedule Settings**:
   - Customizable working days (e.g. Monday to Friday/Saturday).
   - Period timings builder (start time, end time, period names).
   - Recess / Tea break rules (break name, duration, start & end time).
   - Live interactive daily timeline preview.

8. **Smart Timetable Generator (`TimetableEngine.php`)**:
   - Constraint satisfaction and heuristic placement algorithm.
   - Guarantees zero faculty double-booking and zero classroom clashes.
   - Places 4-hour practical labs into 2-period contiguous blocks inside specialized computer labs without crossing breaks.
   - Balances subject dispersion across days.

9. **Real-Time Conflict Detector (`ConflictDetector.php`)**:
   - Instant visual clash alerts for double-booking, workload overruns, unmet subject quotas, and unavailability violations.

10. **Interactive Timetable Viewer & Manual Slot Editor (`timetable.php`)**:
    - 3 Views: **Class View**, **Faculty View**, **Classroom View**.
    - Click any cell to open the Slot Editor.
    - **Live Clash Validator**: Warns in real-time if a newly selected teacher or room is busy during that slot.
    - Slot Lock/Pin feature to protect manually edited slots from automated regeneration.
    - 🖨 Print / PDF export formatted for A4 landscape with institutional header and signature blocks.

11. **Timetable Quality Score (0 - 100)**:
    - Calculates overall schedule health across syllabus completeness, lab rule compliance, workload balance, and subject diversity.

12. **Reports & Exports**:
    - Printable Timetable Sheets.
    - Faculty Workload Summary Report.
    - Room Utilization Report.
    - Conflict & Quality Audit Logs.
    - 1-Click CSV / Excel export.

---

## 🚀 Quick Setup & Installation on XAMPP

1. **Place Project in XAMPP `htdocs`**:
   Copy the `smart-timetable-management-system` folder to `C:\xampp\htdocs\smart-timetable-management-system`.

2. **Start MySQL & Apache in XAMPP**:
   Open XAMPP Control Panel and start **Apache** and **MySQL**.

3. **Initialize Database**:
   Open your browser and navigate to:
   ```
   http://localhost/smart-timetable-management-system/database/init_db.php
   ```
   *This automatically creates `timetable_management_db`, loads all relational schema tables, and populates realistic demo data for the B.Voc SD & SA department.*

4. **Access the Application**:
   Navigate to:
   ```
   http://localhost/smart-timetable-management-system/
   ```
   **Default Login Credentials**:
   - **Username**: `admin`
   - **Password**: `admin123`

---

## 📁 Directory Structure

```
smart-timetable-management-system/
├── assets/
│   ├── css/
│   │   ├── style.css           # Modern Academic Design System
│   │   └── print.css           # High-fidelity A4 printable styles
│   ├── js/
│   │   ├── app.js              # Global app framework & utilities
│   │   ├── dashboard.js        # Dashboard KPI counters & charts
│   │   ├── staff.js            # Staff CRUD & availability matrix
│   │   ├── subjects.js         # Dynamic subject CRUD & filters
│   │   ├── classrooms.js       # Classroom & lab controller
│   │   ├── schedule.js         # Schedule & period timeline builder
│   │   ├── generator.js        # Generator wizard & quality breakdown
│   │   ├── timetable-view.js   # Interactive timetable grid & slot editor
│   │   └── reports.js          # Reports, workload & CSV exporter
│   └── images/
│       └── logo.svg            # Department SVG emblem
├── config/
│   ├── database.php            # PDO MySQL singleton & auto-creator
│   └── session.php             # Session auth guards & CSRF helpers
├── auth/
│   ├── login.php               # Glassmorphism login page
│   ├── logout.php              # Logout handler
│   └── check_auth.php          # Auth middleware
├── admin/
│   ├── includes/
│   │   ├── header.php          # HTML head & stylesheets
│   │   ├── sidebar.php         # Navigation sidebar
│   │   ├── topbar.php          # Session switcher & action bar
│   │   └── footer.php          # Modals & script tags
│   ├── dashboard.php           # Executive Dashboard
│   ├── staff.php               # Staff Management & Availability
│   ├── subjects.php            # Dynamic Subject Management
│   ├── academic_years.php      # Academic Years & Batches
│   ├── classrooms.php          # Classrooms & Labs Management
│   ├── schedule_settings.php   # Schedule Settings & Timings
│   ├── generate_timetable.php  # Smart Timetable Generator
│   ├── timetable.php           # Master Interactive Timetable Grid
│   └── reports.php             # Reports & Quality Audit
├── api/
│   ├── auth.php                # Auth endpoints
│   ├── staff.php               # Staff endpoints
│   ├── subjects.php            # Subject endpoints
│   ├── academic_years.php      # Academic Year endpoints
│   ├── classrooms.php          # Classroom endpoints
│   ├── schedule_settings.php   # Schedule Settings endpoints
│   ├── generator.php           # Generation & Quality Score endpoints
│   ├── timetable.php           # Grid & Slot Editor endpoints
│   └── reports.php             # Reports & Export endpoints
├── classes/
│   ├── TimetableEngine.php     # Constraint Satisfaction Generator
│   ├── ConflictDetector.php    # Conflict & Clash Audit Engine
│   └── QualityEvaluator.php    # Timetable Quality Score (0-100)
├── database/
│   ├── schema.sql              # MySQL Relational Schema
│   ├── seed_data.sql           # B.Voc SD & SA Demo Dataset
│   └── init_db.php             # 1-Click DB Installer
├── index.php                   # Root Entry Router
└── README.md                   # Documentation
```
#   S m a r t - T i m e t a b l e - M a n a g e m e n t - S y s t e m -  
 