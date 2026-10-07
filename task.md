Anda adalah Senior Software Architect, Senior Laravel Developer, Database Architect, UI/UX Engineer, dan QA Engineer.

Saya ingin Anda membangun aplikasi internal hotel untuk:

**Urbanview Daniela Jambi by RedDoorz**

Aplikasi ini bukan website landing page publik, melainkan **Hotel Internal Management System** yang digunakan oleh Owner, Manager, Receptionist, Housekeeping, Maintenance, Finance, dan Administrator.

Teknologi utama:

- Laravel versi stabil terbaru yang kompatibel dengan environment project
- PHP 8.3+
- Tailwind CSS
- Livewire
- Alpine.js bila diperlukan
- MySQL
- Redis bila diperlukan
- Laravel Queue
- Laravel Scheduler
- Laravel Policies/Gates
- Spatie Laravel Permission bila sesuai
- Activity/Audit Logging
- Responsive Web Design

Jangan menggunakan React atau Vue kecuali benar-benar diperlukan.

Target utama adalah aplikasi yang sederhana digunakan oleh staf hotel tetapi memiliki arsitektur backend yang rapi, scalable, aman, dan mudah dikembangkan.

---

# 1. PRINSIP UTAMA PENGEMBANGAN

Jangan langsung membuat semua fitur tanpa perencanaan.

Sebelum coding:

1. Analisis struktur project existing.
2. Analisis dependency Laravel yang sudah digunakan.
3. Jangan merusak fitur existing.
4. Tentukan arsitektur yang paling sesuai.
5. Gunakan modular monolith.
6. Hindari controller gemuk.
7. Hindari business logic langsung di Blade/Livewire Component.
8. Gunakan Service/Action class untuk proses bisnis penting.
9. Gunakan Enum untuk status.
10. Gunakan Policy/Permission untuk authorization.
11. Gunakan transaction untuk operasi penting.
12. Gunakan locking bila ada risiko double booking.
13. Gunakan audit trail untuk perubahan sensitif.
14. Gunakan Soft Deletes untuk data yang relevan.
15. Jangan hardcode konfigurasi bisnis.

Struktur domain yang direkomendasikan:

app/
Domains/
    Dashboard/
    Reservation/
    Guest/
    Room/
    Housekeeping/
    Maintenance/
    Inventory/
    Cashier/
    Staff/
    Reporting/
    System/

Jika struktur project existing tidak cocok menggunakan Domains, adaptasikan tanpa membuat perubahan arsitektur ekstrem.

---

# 2. ROLE DAN PERMISSION

Minimal role:

### Owner
Akses seluruh dashboard dan laporan.

### Manager
Akses seluruh operasional hotel.

### Receptionist
Akses:
- Reservation
- Guest
- Room Board
- Check-In
- Check-Out
- Payment
- Shift Handover

### Housekeeping
Akses:
- Housekeeping Board
- Room Cleaning
- Room Checklist
- Linen
- Amenities

### Maintenance
Akses:
- Maintenance Ticket
- Work Order
- Room Issue

### Finance
Akses:
- Payment
- Transaction
- Cashier Closing
- Financial Reports

### Administrator
Akses:
- User
- Role
- Permission
- Master Data
- Application Settings
- Audit Logs

Gunakan permission granular, contoh:

reservation.view
reservation.create
reservation.update
reservation.cancel

guest.view
guest.create
guest.update

room.view
room.update_status

checkin.execute
checkout.execute

payment.view
payment.create
payment.refund

housekeeping.view
housekeeping.update

maintenance.view
maintenance.create
maintenance.update

inventory.view
inventory.adjust

report.view

user.manage
role.manage

Jangan hanya melakukan pengecekan berdasarkan nama role.

---

# 3. DASHBOARD

Buat dashboard utama yang profesional dan mudah dipahami.

Card utama:

- Total Room
- Occupied Room
- Available Room
- Dirty Room
- Cleaning Room
- Maintenance Room
- Occupancy %
- Check-In Today
- Check-Out Today
- Expected Arrivals
- Expected Departures
- Outstanding Payment
- Open Maintenance
- Open Guest Request
- Revenue Today

Tambahkan:

### Occupancy chart
7 / 30 hari terakhir.

### Revenue chart
7 / 30 hari terakhir.

### Booking Source
Contoh:

- RedDoorz
- Traveloka
- Agoda
- Booking.com
- Walk-In
- WhatsApp
- Direct

### Recent Activity

Contoh:

Room 205 checked-in
Room 101 cleaning completed
Room 304 maintenance opened
Payment received RES-XXXX

---

# 4. ROOM MASTER

Buat master:

rooms

Minimal field:

id
room_number
floor
room_type_id
status
housekeeping_status
maintenance_status
base_rate
capacity_adult
capacity_child
description
notes
is_active
created_at
updated_at
deleted_at

Buat:

room_types

Field:

id
name
code
description
base_rate
capacity_adult
capacity_child
is_active

Contoh tipe:

Standard
Deluxe
Superior

Gunakan master sehingga tipe kamar tidak hardcoded.

---

# 5. ROOM STATUS

Pisahkan status operasional.

Occupancy status:

VACANT
OCCUPIED
RESERVED

Housekeeping status:

DIRTY
CLEANING
CLEAN
READY

Operational status:

AVAILABLE
MAINTENANCE
OUT_OF_ORDER

Jangan membuat satu field status yang menangani semuanya karena akan menyulitkan pengembangan.

Buat Enum untuk semua status.

---

# 6. ROOM BOARD

Room Board adalah salah satu halaman terpenting.

Tampilkan kamar menggunakan card/grid.

Contoh:

101
READY
AVAILABLE

102
OCCUPIED
Muhammad Rihaz

103
DIRTY

104
MAINTENANCE

Gunakan warna/status badge yang konsisten.

Tambahkan filter:

- All
- Available
- Occupied
- Reserved
- Dirty
- Cleaning
- Ready
- Maintenance
- Floor
- Room Type

Ketika card room diklik tampilkan detail drawer/modal:

Room Number
Room Type
Current Guest
Check-In
Check-Out
Booking Source
Payment Status
Housekeeping Status
Active Maintenance
Notes

---

# 7. GUEST MANAGEMENT

Buat:

guests

Minimal field:

id
guest_code
full_name
gender
phone
email
identity_type
identity_number
nationality
address
date_of_birth
notes
is_blacklisted
created_at
updated_at

Tambahkan:

guest stay history

Saat membuka guest profile tampilkan:

Guest Information
Stay History
Reservation History
Payment History
Complaint History
Notes

Guest tidak boleh dibuat duplikat tanpa warning apabila:

phone sama
email sama
identity_number sama

Berikan duplicate detection.

---

# 8. RESERVATION

Buat reservation management.

Table:

reservations

Minimal field:

id
reservation_number
guest_id
room_type_id
room_id nullable
booking_source
booking_reference
check_in_date
check_out_date
adult_count
child_count
room_rate
total_room_amount
additional_charge
discount
deposit_amount
total_amount
payment_status
reservation_status
special_request
internal_note
created_by
created_at
updated_at

Reservation number otomatis:

RES-YYYYMMDD-XXXX

Status:

PENDING
CONFIRMED
CHECKED_IN
CHECKED_OUT
CANCELLED
NO_SHOW

Booking source:

REDDOORZ
TRAVELOKA
AGODA
BOOKING_COM
WALK_IN
WHATSAPP
DIRECT
OTHER

Gunakan Enum/master bila lebih baik.

---

# 9. PREVENT DOUBLE BOOKING

Ini WAJIB.

Sistem tidak boleh membiarkan satu kamar mempunyai reservation overlapping.

Gunakan rule:

Existing check_in < new check_out

AND

Existing check_out > new check_in

Exclude:

CANCELLED
NO_SHOW

Lakukan validasi di:

Frontend

dan

Backend.

Untuk proses critical gunakan transaction/database locking bila diperlukan.

Error harus jelas:

"Room 205 tidak tersedia pada tanggal tersebut karena sudah memiliki reservasi RES-XXXX."

---

# 10. CHECK-IN

Buat workflow Check-In.

Validasi:

Reservation CONFIRMED
Guest tersedia
Room tersedia
Room housekeeping status READY
Room tidak maintenance
Tanggal valid

Saat check-in:

reservation_status = CHECKED_IN

room occupancy_status = OCCUPIED

Buat stay record jika diperlukan.

Simpan:

checked_in_at
checked_in_by

Buat audit log.

---

# 11. CHECK-OUT

Saat checkout:

Pastikan payment/folio sudah diperiksa.

Update:

reservation_status = CHECKED_OUT

checked_out_at
checked_out_by

Room:

occupancy_status = VACANT
housekeeping_status = DIRTY

Otomatis buat housekeeping task.

Jadi workflow:

CHECKOUT
→ ROOM DIRTY
→ HOUSEKEEPING TASK CREATED

---

# 12. HOUSEKEEPING

Buat Housekeeping Board.

Table:

housekeeping_tasks

Field:

id
room_id
reservation_id nullable
assigned_to nullable
priority
status
started_at
completed_at
verified_at
verified_by
notes
created_at

Status:

PENDING
ASSIGNED
CLEANING
COMPLETED
VERIFIED

Cleaning checklist:

Bedsheet
Pillow Case
Bath Towel
Water
Soap
Shampoo
Tissue
Bathroom
Floor
TV
AC
Shower
Trash Bin

Buat table:

housekeeping_checklists

housekeeping_checklist_items

atau struktur fleksibel yang baik.

Room tidak boleh menjadi READY sebelum checklist mandatory selesai.

Workflow:

DIRTY
→ ASSIGNED
→ CLEANING
→ CLEAN
→ VERIFIED
→ READY

Supervisor/manager dapat melakukan verification.

---

# 13. MAINTENANCE

Buat Maintenance / Work Order.

Table:

maintenance_tickets

Field:

id
ticket_number
room_id nullable
location
category
priority
description
reported_by
assigned_to
status
started_at
resolved_at
verified_at
resolution
before_photo
after_photo
created_at
updated_at

Ticket:

MT-YYYYMMDD-XXXX

Category:

AIR_CONDITIONER
ELECTRICAL
PLUMBING
SHOWER
TV
WIFI
DOOR_LOCK
FURNITURE
BATHROOM
OTHER

Priority:

LOW
NORMAL
HIGH
CRITICAL

Status:

OPEN
ASSIGNED
IN_PROGRESS
WAITING
RESOLVED
VERIFIED
CLOSED

Jika maintenance critical pada kamar:

Room harus bisa berubah menjadi:

MAINTENANCE / OUT_OF_ORDER

dan tidak dapat digunakan untuk reservation/check-in.

---

# 14. GUEST REQUEST / COMPLAINT

Buat module sederhana.

guest_requests

Field:

request_number
guest_id
reservation_id
room_id
category
description
priority
assigned_department
assigned_to
status
requested_at
completed_at
notes

Contoh:

Extra towel
Extra pillow
Room cleaning
AC complaint
Shower complaint
WiFi complaint
Late checkout

Status:

OPEN
ASSIGNED
IN_PROGRESS
COMPLETED
CANCELLED

Hitung response time.

---

# 15. CASHIER DAN PAYMENT

Buat sistem cashier sederhana.

transactions

payment_transactions

atau desain database yang lebih tepat.

Support:

Cash
Bank Transfer
QRIS
Other

Payment status:

UNPAID
PARTIAL
PAID
REFUNDED

Reservation harus bisa mempunyai beberapa payment.

Contoh:

Total:
Rp500.000

Deposit:
Rp200.000

Payment:
Rp300.000

Balance:
Rp0

Jangan hanya menyimpan satu payment amount di reservation.

Gunakan transaksi terpisah.

---

# 16. CHARGE / FOLIO SEDERHANA

Buat guest folio sederhana.

Charges:

ROOM
EXTRA_BED
LATE_CHECKOUT
LAUNDRY
MINIBAR
DAMAGE
OTHER

Setiap charge menyimpan:

reservation_id
category
description
quantity
unit_price
amount
created_by
created_at

Total reservation:

room charge
+
additional charge
-
discount
=
grand total

Payment dikurangi dari grand total menjadi balance.

---

# 17. REFUND DAN VOID

Refund/void harus menyimpan:

amount
reason
user
timestamp

Tidak boleh delete transaction payment langsung.

Gunakan reversal/void mechanism.

Semua tercatat di audit log.

---

# 18. CASHIER SHIFT

Buat cashier shift sederhana.

cashier_shifts

Field:

user_id
opened_at
opening_balance
closed_at
closing_balance
expected_balance
difference
notes

Dashboard shift:

Cash Received
Transfer Received
QRIS
Refund
Expected Cash
Actual Cash
Difference

---

# 19. INVENTORY

Buat Inventory sederhana untuk:

Amenity
Housekeeping Supply
Linen

Table:

items

Field:

code
name
category
unit
current_stock
minimum_stock
is_active

Kategori:

AMENITY
LINEN
HOUSEKEEPING
MAINTENANCE
OTHER

Contoh item:

Bath Towel
Bedsheet
Pillow Case
Mineral Water
Soap
Shampoo
Tissue

Gunakan inventory_transactions.

Type:

STOCK_IN
STOCK_OUT
ADJUSTMENT
USAGE
LOST
DAMAGED

Jangan update stock tanpa transaction history.

Current stock dapat dihitung atau diperbarui secara aman melalui transaction service.

---

# 20. LOW STOCK ALERT

Jika:

current_stock <= minimum_stock

Tampilkan warning:

LOW STOCK

di dashboard.

---

# 21. LINEN

Jika memungkinkan pisahkan linen tracking:

CLEAN
IN_ROOM
DIRTY
LAUNDRY
DAMAGED
LOST

Minimal:

Bath Towel
Bedsheet
Pillowcase

Tidak perlu membuat sistem laundry enterprise.

---

# 22. SHIFT HANDOVER

Buat module:

shift_handovers

Field:

shift_date
shift_type
created_by
handover_to nullable
notes
status

Shift:

MORNING
AFTERNOON
NIGHT

Handover item:

room
reservation
category
description
priority
status

Contoh:

Room 203 late checkout
Room 105 unpaid Rp150.000
Room 307 AC maintenance
Guest RES-001 arrival 01:00

Dashboard shift berikutnya harus bisa melihat unfinished handover.

---

# 23. LOST AND FOUND

Module sederhana.

lost_found_items

Field:

code
item_name
description
found_location
found_date
found_by
guest_id nullable
reservation_id nullable
photo
storage_location
status
notes

Status:

FOUND
STORED
CLAIMED
RETURNED
DISPOSED

---

# 24. REPORTS

Buat reports:

## Occupancy Report

Date
Available Rooms
Occupied Rooms
Occupancy %

## Revenue Report

Room Revenue
Additional Revenue
Payment
Refund
Net Revenue

## Reservation Report

Booking Source
Reservation Count
Cancelled
No Show
Check-In

## Housekeeping Report

Rooms Cleaned
Average Cleaning Time
Staff Productivity

## Maintenance Report

Tickets
Category
Average Resolution Time
Open Issues
Repeated Room Issues

## Inventory Report

Current Stock
Stock Usage
Low Stock
Adjustment

## Payment Report

Cash
Bank Transfer
QRIS
Refund

Semua report harus mempunyai:

Date Filter
Export Excel jika dependency/project memungkinkan
Print

---

# 25. AUDIT LOG

Semua aktivitas critical harus dicatat.

Contoh:

Reservation created
Reservation cancelled
Room changed
Room rate changed
Check-in
Check-out
Payment
Refund
Inventory adjustment
Room status change
Maintenance close
User permission change

Simpan minimal:

user_id
action
module
record_id
old_value
new_value
ip_address
user_agent
created_at

Jika menggunakan package activity log, konfigurasi dengan benar.

Audit log hanya boleh dilihat Administrator/Manager/Owner.

---

# 26. NOTIFICATION

Buat notification internal.

Contoh:

New Reservation
Expected Arrival
Room Ready
Maintenance Critical
Low Stock
Outstanding Payment
Guest Complaint

Gunakan Laravel Notification.

Jika belum diperlukan:

Database Notification cukup.

Architecture harus memungkinkan penambahan:

Email
WhatsApp
Telegram

nanti.

---

# 27. UI/UX

Saya ingin UI modern, bersih, profesional, dan cocok untuk operasional hotel.

Gunakan:

Tailwind CSS

Design direction:

Clean
Minimal
Professional
Modern Hotel Dashboard
Tidak terlalu banyak gradient
Tidak terlalu banyak animasi
Fokus usability

Gunakan:

Card
Badge
Table
Modal
Drawer
Tabs
Dropdown
Datepicker
Confirmation dialog
Toast notification
Empty state
Loading state
Skeleton bila diperlukan

Responsive untuk:

Desktop
Tablet
Mobile

Housekeeping dan Maintenance harus nyaman digunakan melalui HP.

---

# 28. SIDEBAR

Gunakan struktur:

Dashboard

FRONT OFFICE
- Reservations
- Room Board
- Check-In
- In-House
- Check-Out
- Guests

OPERATIONS
- Housekeeping
- Guest Requests
- Maintenance
- Lost & Found

INVENTORY
- Items
- Stock Transactions
- Linen
- Stock Opname

FINANCE
- Cashier
- Payments
- Transactions
- Cashier Closing

STAFF
- Employees
- Shift Handover

REPORTS
- Occupancy
- Revenue
- Reservations
- Housekeeping
- Maintenance
- Inventory

SYSTEM
- Users
- Roles
- Permissions
- Room Master
- Room Types
- Audit Logs
- Settings

Menu harus berdasarkan permission.

---

# 29. NUMBER GENERATOR

Gunakan nomor otomatis.

Reservation:

RES-YYYYMMDD-XXXX

Maintenance:

MT-YYYYMMDD-XXXX

Guest Request:

GR-YYYYMMDD-XXXX

Payment:

PAY-YYYYMMDD-XXXX

Inventory Transaction:

INV-YYYYMMDD-XXXX

Jangan menggunakan:

count() + 1

karena rawan duplicate.

Gunakan safe sequential/random generator.

---

# 30. VALIDATION

Semua input wajib memiliki:

Backend Validation

dan frontend feedback.

Gunakan Form Request atau Livewire validation.

Contoh:

Check-out > Check-in

Room tidak double booking

Room READY untuk check-in

Amount tidak negatif

Refund <= amount paid

Inventory tidak minus kecuali diizinkan configuration

Tidak bisa checkout reservation yang belum check-in

Tidak bisa check-in CANCELLED reservation

Tidak bisa menghapus payment history

---

# 31. DATABASE

Gunakan:

Foreign Key

Index

Unique constraint

Soft Delete

sesuai kebutuhan.

Index minimal untuk field yang sering dicari:

reservation_number
room_id
guest_id
check_in_date
check_out_date
reservation_status
booking_source
payment_status
created_at

Jangan membuat semua column nullable tanpa alasan.

Gunakan decimal untuk uang.

Jangan gunakan float/double untuk currency.

Contoh:

decimal(15,2)

---

# 32. TRANSACTION

Gunakan DB::transaction pada proses:

Create reservation
Check-in
Check-out
Payment
Refund
Inventory adjustment
Room transfer

Jika satu langkah gagal, seluruh transaction harus rollback.

---

# 33. SECURITY

Implementasikan:

Authentication
Authorization
CSRF Protection
Validation
XSS protection
SQL injection prevention
Secure file upload
Rate limiting untuk endpoint sensitif
Session security
Password hashing
Permission validation backend

Jangan mengandalkan hidden menu sebagai security.

Setiap action harus diperiksa permission pada backend.

---

# 34. FILE UPLOAD

Untuk:

Guest document
Maintenance photo
Lost and found photo

Gunakan Storage Laravel.

Validasi:

MIME
Extension
File size

Jangan percaya nama file dari user.

Generate safe filename.

Jangan simpan file upload langsung ke public tanpa kontrol bila sensitif.

---

# 35. PERFORMANCE

Hindari N+1 query.

Gunakan:

with()
withCount()
pagination()
indexes
caching bila relevan

Dashboard tidak boleh menjalankan puluhan query berat secara tidak efisien.

---

# 36. SEEDER

Buat seeder development:

Admin User
Roles
Permissions
Room Types
Rooms
Demo Guests
Demo Reservations
Inventory Items

Jangan memasukkan credential production.

---

# 37. TESTING

Minimal buat feature tests untuk:

Login
Permission
Reservation creation
Double booking
Check-in
Check-out
Payment
Refund
Housekeeping completion
Maintenance ticket
Inventory transaction

Test penting:

Room yang sudah memiliki reservation overlapping tidak boleh bisa dibooking.

---

# 38. DEVELOPMENT PHASE

Kerjakan bertahap.

PHASE 1

Foundation

- Authentication
- User
- Role
- Permission
- Layout
- Sidebar
- Audit Log
- Master Room
- Room Type

PHASE 2

Front Office

- Guest
- Reservation
- Room Board
- Check-In
- In-House
- Check-Out

PHASE 3

Operations

- Housekeeping
- Maintenance
- Guest Request
- Lost & Found
- Shift Handover

PHASE 4

Finance

- Folio
- Charges
- Payment
- Refund
- Cashier
- Closing

PHASE 5

Inventory

- Items
- Stock Transaction
- Linen
- Low Stock

PHASE 6

Reporting

- Dashboard
- Occupancy
- Revenue
- Reservation
- Housekeeping
- Maintenance
- Inventory

Jangan langsung coding seluruh phase sekaligus.

---

# 39. CARA KERJA YANG SAYA INGINKAN

Pertama lakukan ANALISIS.

Berikan:

1. Existing project analysis.
2. Architecture proposal.
3. Database schema.
4. ERD sederhana.
5. List migration.
6. List model.
7. List module.
8. List route.
9. List permission.
10. Workflow reservation.
11. Workflow check-in/check-out.
12. Workflow housekeeping.
13. Workflow maintenance.
14. Implementation plan.

Setelah analisis selesai:

Mulai implementasi Phase 1.

Jangan mengubah file tidak relevan.

Setiap selesai satu module:

- Review code
- Cari bug
- Review security
- Review performance
- Review UI
- Jalankan test

Kemudian lanjut module berikutnya.

---

# 40. IMPLEMENTATION STANDARD

Semua code harus:

Clean
Readable
Maintainable
Production-oriented

Jangan membuat:

Massive Controller
Massive Livewire Component
Massive Model
Duplicate Query
Duplicate Business Logic

Gunakan:

Service / Action
DTO bila dibutuhkan
Enum
Policy
Event
Listener
Job

secara proporsional.

Jangan over-engineering.

---

# 41. BUSINESS GOAL

Tujuan akhir aplikasi adalah membuat staf Urbanview Daniela Jambi dapat mengetahui dalam satu sistem:

- Siapa yang akan check-in hari ini?
- Siapa yang akan check-out?
- Kamar mana tersedia?
- Kamar mana occupied?
- Kamar mana belum dibersihkan?
- Kamar mana sudah ready?
- Kamar mana bermasalah?
- Siapa guest yang belum bayar?
- Berapa revenue hari ini?
- Ada complaint apa?
- Ada maintenance apa?
- Apakah stok towel cukup?
- Apa pekerjaan shift sebelumnya?
- Berapa occupancy hotel hari ini?

Prioritaskan kebutuhan operasional tersebut dibanding fitur enterprise yang tidak diperlukan.

---

# 42. FITUR YANG BELUM PERLU

Untuk saat ini JANGAN implementasikan:

Multi Property
Complex ERP
General Ledger
Payroll
Spa
Banquet
Restaurant POS Enterprise
Kitchen Display System
Complex Revenue Management
Loyalty Program
AI Pricing
OTA API Integration
Channel Manager
Door Lock Integration

Tetapi desain database dan architecture jangan membuat future integration menjadi sulit.

---

# 43. OUTPUT AWAL YANG SAYA INGINKAN

JANGAN LANGSUNG CODING.

Pada response pertama:

Analisis project yang tersedia.

Kemudian berikan:

## A. Architecture

Struktur module dan alasan.

## B. Database

Daftar table lengkap dan relasinya.

## C. ERD

Berikan ERD menggunakan Mermaid.

## D. Permissions

Daftar role dan permissions.

## E. Workflow

Reservation
Check-In
Check-Out
Housekeeping
Maintenance
Payment

Gunakan Mermaid flowchart bila sesuai.

## F. Sidebar

Final menu structure.

## G. Development Roadmap

Urutan implementasi yang paling aman.

## H. Risk Analysis

Identifikasi risiko:

Double booking
Race condition
Permission leak
Financial transaction inconsistency
Stock inconsistency
Room status inconsistency
N+1 query
Audit log gap

Jelaskan mitigasinya.

Setelah semua analisis selesai, mulai implementasi secara bertahap dari:

**Phase 1 — Foundation.**

Selalu utamakan stabilitas, keamanan data, integritas transaksi, dan kemudahan penggunaan staf hotel daripada membuat fitur terlalu kompleks.