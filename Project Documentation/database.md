# ImmuniCare Database Guide

Database: `vaccination_management_system`  
Engine: MariaDB/MySQL with InnoDB  
Purpose: stores users, children, vaccines, appointments, vaccination records, inventory, notifications and administration data.

## Main user and organisation tables

| Table | Purpose |
|---|---|
| `users` | Stores login accounts, names, email addresses, roles and account status for Admins, Parents and Hospitals. |
| `children` | Stores children registered by Parents, including date of birth and basic health-profile information. |
| `hospitals` | Stores hospital profiles linked to Hospital user accounts, including contact details and approval status. |

## Vaccine and dose tables

| Table | Purpose |
|---|---|
| `vaccines` | Stores the general vaccine catalogue, descriptions, age groups, dose count and availability. |
| `vaccine_doses` | Stores each dose in a vaccine series, including recommended age, minimum interval and clinical source. |

## Appointment and vaccination workflow tables

| Table | Purpose |
|---|---|
| `hospital_slots` | Stores appointment dates, times, capacity, booked count and open/closed status for hospitals. |
| `bookings` | Stores Parent appointment requests linking a child, vaccine dose, hospital and appointment slot. |
| `vaccination_schedules` | Stores the vaccination date and time created by a Hospital for an approved booking. |
| `vaccination_records` | Stores the final vaccination result, date, remarks, dose and recording Hospital or staff member. |

## Inventory tables

| Table | Purpose |
|---|---|
| `hospital_inventory` | Stores the current quantity and reorder level of each vaccine at each Hospital. |
| `inventory_transactions` | Stores every stock opening balance, adjustment and vaccine deduction with its resulting quantity. |

## Notification and communication tables

| Table | Purpose |
|---|---|
| `notifications` | Stores in-application messages sent to Parents, Hospitals and Admins. |
| `notification_outbox` | Queues email, SMS, WhatsApp or in-app notification events for later processing. |
| `contact_messages` | Stores messages submitted through the public Contact page for Admin review. |

## Security and account-recovery tables

| Table | Purpose |
|---|---|
| `email_verification_tokens` | Stores hashed, expiring, single-use tokens for verifying email addresses. |
| `password_reset_tokens` | Stores hashed, expiring, single-use tokens for resetting forgotten passwords. |
| `api_tokens` | Stores hashed bearer tokens used to authenticate API requests. |
| `audit_logs` | Stores actions performed by users, including the actor, affected entity and event details. |

## Preferences and reporting tables

| Table | Purpose |
|---|---|
| `report_exports` | Stores Admin report-export requests, selected filters, format and export status. |

## Migration table

| Table | Purpose |
|---|---|
| `schema_migrations` | Records which database migrations have already been applied so they are not run twice. |

## Main relationship flow

```text
users
  └── children
        └── bookings
              ├── hospital_slots
              ├── vaccine_doses
              └── vaccination_schedules
                    └── vaccination_records
                          └── inventory_transactions
```

`hospitals` owns the appointment slots, bookings, schedules, vaccination records and hospital-specific inventory. `vaccines` defines the catalogue, while `vaccine_doses` controls the next eligible dose for each child.

## Migration note

Migrations `001`–`004` create and strengthen the application schema. Migrations `005`–`007` mainly create or rename local sample data; they do not create the main database tables. Migration `008_remove_unused_support_tables.sql` removes the unused hospital-hours, hospital-holidays, user-preferences and authentication-events tables from existing databases.
