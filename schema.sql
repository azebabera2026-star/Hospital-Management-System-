CREATE DATABASE IF NOT EXISTS hospital_management;
USE hospital_management;

-- ──────────────────────────────────────────────────────────────────────────────
-- Core tables
-- ──────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  description TEXT NULL
);

CREATE TABLE IF NOT EXISTS patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  gender ENUM('male','female','other') NULL,
  birthdate DATE NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(190) NULL,
  address VARCHAR(255) NULL,
  deleted_at DATETIME NULL DEFAULT NULL   -- soft-delete; NULL = active
);

CREATE TABLE IF NOT EXISTS doctors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  specialization VARCHAR(150) NULL,
  department_id INT NULL,
  deleted_at DATETIME NULL DEFAULT NULL,  -- soft-delete; NULL = active
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  appointment_date DATETIME NOT NULL,
  status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  notes TEXT NULL,
  deleted_at DATETIME NULL DEFAULT NULL,  -- soft-delete; NULL = active
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS treatments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NULL,
  appointment_id INT NULL,
  diagnosis TEXT NULL,
  treatment_date DATE NOT NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS medications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  description TEXT NULL
);

CREATE TABLE IF NOT EXISTS prescriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  treatment_id INT NOT NULL,
  medication_id INT NOT NULL,
  dosage VARCHAR(100) NOT NULL,
  frequency VARCHAR(100) NOT NULL,
  duration_days INT NOT NULL,
  FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE CASCADE,
  FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_number VARCHAR(20) NOT NULL UNIQUE,
  type ENUM('General','Private','ICU','Emergency','Maternity') NULL,
  status ENUM('available','occupied','maintenance') NOT NULL DEFAULT 'available'
);

CREATE TABLE IF NOT EXISTS admissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  room_id INT NOT NULL,
  admitted_on DATETIME NOT NULL,
  discharged_on DATETIME NULL,
  notes TEXT NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(120) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','staff','doctor') NOT NULL DEFAULT 'staff',
  linked_doctor_id INT NULL UNIQUE,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = user must change password on next login
  FOREIGN KEY (linked_doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

-- ── Billing tables ────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  appointment_id INT NULL,
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  due_date DATE NULL,
  status ENUM('unpaid','paid','partially_paid','cancelled') NOT NULL DEFAULT 'unpaid',
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  notes TEXT NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS invoice_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL,
  description VARCHAR(255) NOT NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- ── Audit log ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(60) NULL,
  entity_id INT NULL,
  detail JSON NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_user (user_id),
  INDEX idx_audit_entity (entity_type, entity_id),
  INDEX idx_audit_created (created_at)
);

-- ── Notifications log ─────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS notification_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NULL,
  appointment_id INT NULL,
  channel ENUM('sms','whatsapp') NOT NULL,
  status ENUM('sent','failed') NOT NULL,
  message_sid VARCHAR(64) NULL,
  error TEXT NULL,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notif_patient (patient_id),
  INDEX idx_notif_appointment (appointment_id),
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- ── Performance indexes ───────────────────────────────────────────────────────

ALTER TABLE patients    ADD INDEX IF NOT EXISTS idx_patients_name        (last_name, first_name);
ALTER TABLE patients    ADD INDEX IF NOT EXISTS idx_patients_active       (deleted_at);
ALTER TABLE doctors     ADD INDEX IF NOT EXISTS idx_doctors_name          (last_name, first_name);
ALTER TABLE doctors     ADD INDEX IF NOT EXISTS idx_doctors_dept_active   (department_id, deleted_at);
ALTER TABLE appointments ADD INDEX IF NOT EXISTS idx_appt_patient_date   (patient_id, appointment_date);
ALTER TABLE appointments ADD INDEX IF NOT EXISTS idx_appt_doctor_date    (doctor_id,  appointment_date);
ALTER TABLE appointments ADD INDEX IF NOT EXISTS idx_appt_status_date    (status,     appointment_date);
ALTER TABLE appointments ADD INDEX IF NOT EXISTS idx_appt_active         (deleted_at);
ALTER TABLE invoices    ADD INDEX IF NOT EXISTS idx_inv_patient           (patient_id);
ALTER TABLE invoices    ADD INDEX IF NOT EXISTS idx_inv_status            (status);

-- ── Seed data ─────────────────────────────────────────────────────────────────

INSERT IGNORE INTO departments (id,name,description) VALUES
(1,'Cardiology','Heart and vascular care'),
(2,'Neurology','Nervous system and brain care'),
(3,'Pediatrics','Child healthcare'),
(4,'Emergency','Emergency and trauma services'),
(5,'Orthopedics','Bone and joint care');

INSERT IGNORE INTO patients (id,first_name,last_name,gender,birthdate,phone,email,address) VALUES
(1,'Md. Rahim','Khan','male','1980-05-14','+880-171-1001','md.rahim@example.com','House 12, Dhanmondi, Dhaka'),
(2,'Fatema','Begum','female','1992-11-02','+880-171-1002','fatema.begum@example.com','Road 7, Uttara, Dhaka'),
(3,'Arif','Hossain','male','2015-07-20','+880-171-1003','arif.hossain@example.com','Sector 5, Mirpur, Dhaka'),
(4,'Sadia','Akter','female','1975-03-30','+880-171-1004','sadia.akter@example.com','House 8, Gulshan, Dhaka'),
(5,'Tanvir','Ahmed','male','1968-09-12','+880-171-1005','tanvir.ahmed@example.com','Road 3, Chittagong'),
(6,'Ayesha','Siddika','female','1988-01-22','+880-171-1006','ayesha.siddika@example.com','House 45, Sylhet');

INSERT IGNORE INTO doctors (id,first_name,last_name,email,phone,specialization,department_id) VALUES
(1,'Mohammad','Karim','m.karim@hospital.example','+880-171-2001','Interventional Cardiology',1),
(2,'Rahim','Uddin','r.uddin@hospital.example','+880-171-2002','Stroke Specialist',2),
(3,'Nusrat','Jahan','n.jahan@hospital.example','+880-171-2003','Pediatrician',3),
(4,'Tanbir','Chowdhury','t.chowdhury@hospital.example','+880-171-2004','Orthopedic Surgeon',5);

INSERT IGNORE INTO medications (id,name,description) VALUES
(1,'Aspirin','Pain reliever and antiplatelet (low-dose for cardioprotection)'),
(2,'Amoxicillin','Broad-spectrum antibiotic for bacterial infections'),
(3,'Lisinopril','ACE inhibitor for hypertension'),
(4,'Ibuprofen','NSAID for pain and inflammation'),
(5,'Paracetamol','Analgesic and antipyretic (acetaminophen)'),
(6,'Atorvastatin','Statin for cholesterol lowering'),
(7,'Metformin','First-line oral medication for type 2 diabetes'),
(8,'Omeprazole','Proton-pump inhibitor for acid-related disorders'),
(9,'Salbutamol','Short-acting bronchodilator for asthma/COPD'),
(10,'Cetirizine','Second-generation antihistamine for allergy');

INSERT IGNORE INTO rooms (id,room_number,type,status) VALUES
(1,'101','General','available'),
(2,'102','Private','occupied'),
(3,'ICU-1','ICU','available'),
(4,'ER-1','Emergency','available'),
(5,'201','Maternity','occupied'),
(6,'Ward-1','General','available'),
(7,'Cabin-1','Private','available'),
(8,'VIP-1','Private','available'),
(9,'CCU-1','ICU','available'),
(10,'Labour-1','Maternity','available'),
(11,'Ward-2','General','available');

INSERT IGNORE INTO admissions (id,patient_id,room_id,admitted_on,discharged_on,notes) VALUES
(1,2,2,'2025-02-10 08:30:00',NULL,'Observation after minor surgery'),
(2,5,3,'2025-01-25 14:00:00','2025-02-02 10:00:00','Post-op recovery complete'),
(3,3,5,'2025-03-05 09:15:00',NULL,'Routine newborn observation');

INSERT IGNORE INTO appointments (id,patient_id,doctor_id,appointment_date,status,notes) VALUES
(1,1,1,'2025-02-15 10:00:00','completed','Follow-up for hypertension'),
(2,2,4,'2025-02-18 11:30:00','scheduled','Knee pain evaluation'),
(3,3,3,'2025-03-05 09:00:00','completed','Well child visit'),
(4,4,2,'2025-04-01 15:00:00','cancelled','Patient requested reschedule'),
(5,6,1,'2025-05-12 13:45:00','scheduled','Chest pain assessment');

INSERT IGNORE INTO treatments (id,patient_id,doctor_id,appointment_id,diagnosis,treatment_date) VALUES
(1,1,1,1,'Essential hypertension','2025-02-15'),
(2,2,4,2,'Meniscal tear, right knee','2025-02-18'),
(3,3,3,3,'Routine vaccination and check-up','2025-03-05'),
(4,5,1,NULL,'Hyperlipidemia management','2025-01-25'),
(5,6,1,5,'Chest pain - atypical, further tests ordered','2025-05-12');

INSERT IGNORE INTO prescriptions (id,treatment_id,medication_id,dosage,frequency,duration_days) VALUES
(1,1,3,'10 mg','once daily',30),
(2,2,4,'400 mg','every 8 hours',7),
(3,3,2,'250 mg','three times daily',5),
(4,4,6,'20 mg','once daily',90),
(5,5,1,'81 mg','once daily',30);

INSERT IGNORE INTO invoices (id,patient_id,appointment_id,status,paid_amount,notes) VALUES
(1,1,1,'paid',1500.00,'Hypertension follow-up consultation'),
(2,2,2,'unpaid',0.00,'Knee evaluation — awaiting payment'),
(3,3,3,'paid',500.00,'Well child visit — fully settled');

INSERT IGNORE INTO invoice_items (id,invoice_id,description,quantity,unit_price) VALUES
(1,1,'Consultation fee',1,1000.00),
(2,1,'ECG',1,500.00),
(3,2,'Consultation fee',1,1200.00),
(4,2,'MRI – Right Knee',1,5000.00),
(5,3,'Consultation fee',1,500.00);

-- Demo accounts — default password for all: "password123" (must_change_password = 1 forces password change on first login)
INSERT IGNORE INTO users (id,username,email,password_hash,role,linked_doctor_id,must_change_password) VALUES
(1,'admin','admin@hospital.example','$2y$10$6g1jCGd6xxfkxl2bmBNRM.XruYyo7wOoMkQQprfzVNo6oRqplY7gK','admin',NULL,1),
(2,'m.karim','m.karim@hospital.example','$2y$10$6g1jCGd6xxfkxl2bmBNRM.XruYyo7wOoMkQQprfzVNo6oRqplY7gK','doctor',1,1),
(3,'r.uddin','r.uddin@hospital.example','$2y$10$6g1jCGd6xxfkxl2bmBNRM.XruYyo7wOoMkQQprfzVNo6oRqplY7gK','doctor',2,1),
(4,'frontdesk','frontdesk@hospital.example','$2y$10$6g1jCGd6xxfkxl2bmBNRM.XruYyo7wOoMkQQprfzVNo6oRqplY7gK','staff',NULL,1);