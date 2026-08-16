# Hospital Management System (HMS)

A comprehensive Hospital Management System designed to streamline key hospital operations through a unified, role-aware platform. It features a normalized relational database, a secure PHP backend, and a responsive user interface styled with Tailwind CSS.

This project was created and developed by **Azeb Abera**.

## ✨ Key Features

The system supports multiple domains of hospital management:
* **Patient and Doctor Registries**: Maintain core records for patients, doctors, and departments.
* **Appointment Scheduling**: Create, update, and track appointments between patients and doctors.
* **Clinical Management**: Record patient treatments and issue prescriptions with specific medications and dosages.
* **Facility Management**: Manage room inventory and handle patient admissions and discharges.
* **Role-Based Access Control**: Three distinct user roles (Admin, Staff, Doctor) with tailored dashboards and permissions to ensure data security and integrity.
* **Secure Authentication**: Features session management and CSRF token protection on all POST forms.

## 🛠️ Technology Stack

* **Backend**: PHP with PDO (using positional placeholders for prepared statements).
* **Database**: MySQL / InnoDB.
* **Frontend**: Tailwind CSS and Alpine.js for lightweight interactivity.
* **Development Environment**: XAMPP / Apache.
* **Security**: Session management, CSRF tokens, role checks, and doctor ownership validation.

## 📊 Entity-Relationship Diagram (ERD)

The database design models the relationships between all major entities in the system.

![Hospital Management System ERD](Hospital%20Management%20System%20ERD.png)

## 🧑‍💻 User Roles & Permissions

The system implements three user roles with specific permissions:

* **Admin**: Has full access to the system. Can manage all master data including doctors, departments, medications, users, and hospital-wide records.
* **Staff**: Can manage patient records, appointments, and room admissions/discharges.
* **Doctor**: Has a restricted view. Doctors can only see and manage clinical data (treatments, prescriptions) for their own patients. User accounts with the 'doctor' role can be linked to a specific doctor profile.

## 🚀 Getting Started

To get a local copy up and running, follow these simple steps.

### Prerequisites

* **XAMPP / WAMP**: Make sure you have Apache and MySQL services running.
* **PHP**: PHP 7.4 or higher.
* **MySQL Database**.

### Installation & Setup

1. **Clone the repository:**
   ```sh
   git clone [https://github.com/azebabera2026-star/Hospital-Management-System-.git](https://github.com/azebabera2026-star/Hospital-Management-System-.git)