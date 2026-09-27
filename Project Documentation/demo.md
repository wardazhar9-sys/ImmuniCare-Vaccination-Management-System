# ImmuniCare Demo Guide

This guide explains the website in simple steps.

## Start the demo

From Windows Command Prompt:

```bat
cd /d "C:\xampp\htdocs\Child Vaccination Management System"
setup\start-xampp.bat
```

Open:

```text
http://127.0.0.1:8080
```

The launcher starts MySQL, creates the database, applies migrations, loads demo data, and starts the website.

### Sample accounts

All demo accounts use:

```text
Password: TestPassword123!
```

- Admin ImmuniCare Admin: `sana.ahmed@immunicare.local`
- Parent Ayesha Khan: `ayesha.khan@immunicare.local`
- Parent Muhammad Ali: `muhammad.ali@immunicare.local`
- Aga Khan University Hospital: `aga.khan@immunicare.local`
- Liaquat National Hospital: `liaquat.national@immunicare.local`

## How the system works

1. Admin creates users, hospitals, vaccines, doses, and inventory.
2. Hospital creates appointment slots.
3. Parent adds children.
4. Parent selects the next eligible vaccine dose and books a hospital slot.
5. Hospital approves or rejects the booking.
6. Hospital schedules approved bookings.
7. Hospital records the vaccination.
8. Inventory decreases only when the vaccination is recorded as **Vaccinated**.
9. Parent sees the schedule and vaccination history.
10. Admin reviews reports and records.

## Common options

### Notifications

Click the bell icon in the top bar.

- **Open**: open the related page.
- **Mark read**: mark one notification as read.
- **Mark all as read**: clear all unread notifications.

### Logout

Click **Logout** at the bottom of the sidebar.

---

# 1. Parent Portal

The Parent Portal manages children and appointments.

## Dashboard

Shows a quick summary:

- number of children;
- upcoming appointments;
- completed vaccinations;
- recent notifications;
- next schedule.

Use the quick links to open children, vaccines, bookings, or schedules.

## My Children

Shows all children owned by the logged-in parent.

Options:

- **Add New Child**: enter name, date of birth, gender, blood group, and address.
- **View**: see the child profile.
- **Edit**: change child information in a form on the same page.
- **Delete**: archive a child. Children with important records are protected.

Use the correct date of birth because it controls dose eligibility.

## Vaccines

Shows available vaccines.

Each vaccine shows:

- name;
- description;
- age group;
- availability;
- next eligible dose for each child.

Options:

- **Search**: find a vaccine.
- **Book Dose**: start an appointment for the selected child and dose.

Parents cannot freely choose Dose 2 or Dose 3. The system checks completed records, child age, and minimum interval. It shows only the next eligible dose.

## Vaccination Schedule

Shows hospital schedules created after approval.

Each schedule shows:

- child;
- vaccine and dose;
- hospital;
- date and time;
- schedule status.

Use it to know when to visit the hospital.

## Book Appointment

Use this page to request a hospital slot.

Steps:

1. Select a child.
2. Select the next eligible vaccine dose.
3. Select an available hospital slot.
4. Click **Book Appointment**.

Only slots created by hospitals are shown. If no slot is shown, the hospital has not created an open slot.

The booking is sent to the hospital as **Pending**.

## My Bookings

Shows all appointment requests.

Possible statuses:

- **Pending**: waiting for hospital approval.
- **Approved**: hospital accepted it.
- **Rejected**: hospital did not accept it.
- **Cancelled**: booking was cancelled or missed.
- **Completed**: vaccination process finished.

Options:

- **Reschedule**: select another open slot.
- **Cancel**: cancel an eligible booking.

## Vaccination History

Shows completed and missed vaccination records.

Each record shows:

- child;
- vaccine and dose;
- hospital;
- date;
- result;
- remarks.

**Vaccinated** records count when the next dose is calculated. **Not Vaccinated** records do not count as completed.

## My Profile

Shows parent account details and children.

Options:

- **Edit Profile**: change name and email.
- **Change Password**: enter current password, new password, and confirmation.
- **Edit Child**: update a child profile.

New passwords must be at least 8 characters and both new-password fields must match.

---

# 2. Hospital Portal

The Hospital Portal manages slots, requests, schedules, and vaccination records.

## Dashboard

Shows:

- pending appointments;
- approved appointments;
- today’s appointments;
- vaccinations recorded;
- low-stock inventory items.

Use quick links to manage appointments, schedules, slots, or vaccinations.

## Appointments

Shows parent booking requests for this hospital.

Options:

- **Approve**: accept a pending booking.
- **Reject**: refuse a pending booking.

Rejecting a booking releases its reserved slot capacity.

The hospital can only manage its own bookings.

## Vaccinations

Shows scheduled appointments ready for recording.

Options:

- enter vaccination date;
- choose **Vaccinated** or **Not Vaccinated**;
- enter optional remarks;
- click **Record Vaccination**.

When **Vaccinated** is saved:

- the booking becomes completed;
- the schedule becomes completed;
- one inventory unit is removed;
- an inventory transaction is saved;
- the parent receives a notification.

When **Not Vaccinated** is saved:

- the schedule becomes missed;
- the booking becomes cancelled;
- inventory is not reduced.

If inventory is zero, a Vaccinated record cannot be saved.

## Vaccination Schedule

Shows approved bookings that have no schedule.

For each booking:

1. choose a future date;
2. choose a future time;
3. click **Create Schedule**.

The parent receives a schedule notification.

## Appointment Slots

Hospitals create the times that parents can book.

Fields:

- **Date**: future appointment date.
- **Time**: appointment time.
- **Capacity**: number of children allowed in that slot.

Example: capacity `2` allows two bookings.

If the slot is full, parents cannot book it. Parents cannot book arbitrary dates or times.

## My Profile

Shows hospital account information and approval details.

Use it to review the hospital profile. Hospital approval is required before managing appointments.

---

# 3. Admin Portal

The Admin Portal manages the complete system.

## Dashboard

Shows system totals and recent activity:

- users;
- children;
- hospitals;
- vaccines;
- bookings;
- schedules;
- vaccination records;
- recent activity.

Use the quick links to open management pages.

## Users

Manages all accounts.

Options:

- search users;
- filter by role;
- view user details;
- edit name and email;
- change role where allowed;
- activate or deactivate an account.

Roles:

- **Parent**: manages children and bookings.
- **Hospital**: manages hospital operations.
- **Admin**: manages the full system.

The last active admin cannot be deactivated.

## Children

Manages all registered children.

Options:

- search children;
- view child details;
- edit child information;
- archive a child.

Child ownership and vaccination history must remain valid.

## Hospitals

Manages registered hospitals.

Options:

- search hospitals;
- view hospital details;
- edit hospital name, phone, address, city, and location;
- approve or deactivate a hospital.

Only active approved hospitals can receive bookings.

## Vaccines

Manages vaccine information.

Vaccine fields:

- name;
- description;
- age group;
- number of doses;
- availability.

Options:

- **Add New Vaccine**;
- **View**;
- **Edit**;
- **Available/Unavailable**.

### Dose schedule management

Dose definitions are available from the **Vaccine Doses** Admin menu and from the dose schedule management section inside the **Vaccines** page.

Dose fields:

- vaccine;
- dose number;
- display label, such as Dose 1 or Booster;
- recommended age in days;
- minimum interval in days;
- clinical source/version;
- Active or Inactive status.

Use dose schedules to control which dose parents can see next.

Example:

- Dose 1: age 0 days.
- Dose 2: age 42 days, minimum interval 30 days.
- Dose 3: age 180 days, minimum interval 90 days.

## Inventory

Manages vaccine stock for each hospital.

Fields:

- hospital;
- vaccine;
- quantity;
- reorder level.

Options:

- add stock;
- replace the current quantity;
- set the reorder warning level.

Status:

- **Sufficient**: quantity is above the reorder level.
- **Reorder required**: quantity is at or below the reorder level.

Every opening balance and adjustment is recorded. Vaccination consumption is also recorded automatically.

## Reports

Creates reports for system review.

Report types:

- **Bookings**: parent, child, vaccine, dose, hospital, time, and status.
- **Vaccination Records**: vaccination result, date, dose, hospital, and remarks.
- **Inventory Balances**: current quantity and reorder status.
- **Inventory Transactions**: stock additions and deductions.

Filters:

- from date;
- to date;
- hospital;
- vaccine;
- dose number;
- status.

Options:

- **Apply filters**: show matching rows.
- **Download CSV**: download the same filtered data.

Empty results are valid and show a clear message.

## Messages

Manages messages sent through the public Contact page.

Options:

- view sender and message;
- change status to **New**;
- change status to **In Progress**;
- change status to **Resolved**;
- mark as **Spam**.

## Bookings

Manages all appointment requests.

Options:

- search bookings;
- filter by status;
- filter by hospital;
- view parent, child, vaccine, dose, and slot;
- approve or reject pending bookings.

Booking status must follow the allowed workflow. Invalid status changes are rejected.

## Schedules

Manages hospital-created vaccination schedules.

Options:

- search schedules;
- filter by status;
- view parent, child, vaccine, dose, hospital, date, and time;
- edit future schedule date and time.

Completed schedules cannot be edited.

## Vaccination Records

Reviews vaccination results entered by hospitals.

Options:

- search by child, parent, or vaccine;
- view dose and hospital;
- view vaccination date;
- view Vaccinated or Not Vaccinated result;
- view remarks.

These records control the parent’s next-dose eligibility.

## Admin Profile

Shows administrator account details.

Options:

- view admin name and email;
- edit account details;
- save changes.

## Admin notifications

The notification bell is available on every admin page.

- Open a related record.
- Mark one notification as read.
- Mark all notifications as read.

---

## Karachi sample cases to show

Use the demo accounts to show:

1. A fully vaccinated child with three completed doses.
2. A child with a previous dose and a next scheduled dose.
3. A new child with a pending appointment.
4. A missed vaccination.
5. A rejected booking.
6. Hospital slots with different capacities.
7. Inventory balances and reorder warnings.
8. Inventory decreasing after a Vaccinated record.
9. Booking, vaccination, and inventory reports.

