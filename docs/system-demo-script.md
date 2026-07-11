# System Demonstration Script

This script is based on the actual modules, routes, and sample data already defined in this project.

## Demo Setup

- Seeded office demo accounts in [database/seeders/SampleDataSeeder.php](/c:/xampp/htdocs/new_anitech/database/seeders/SampleDataSeeder.php:212)
  - `admin@anitech.test` / `password123`
  - `staff@anitech.test` / `password123`
- Seeded farmer accounts in [database/seeders/SampleDataSeeder.php](/c:/xampp/htdocs/new_anitech/database/seeders/SampleDataSeeder.php:479)
  - `farmer01@sample.anitech.test` / `password123`
  - `farmer06@sample.anitech.test` / `password123`
  - Any seeded `farmerXX@sample.anitech.test` account uses `password123`
- Demo scenarios already exist in the seeder:
  - approved application in [database/seeders/SampleDataSeeder.php](/c:/xampp/htdocs/new_anitech/database/seeders/SampleDataSeeder.php:314)
  - rejected application in [database/seeders/SampleDataSeeder.php](/c:/xampp/htdocs/new_anitech/database/seeders/SampleDataSeeder.php:353)
  - deceased farmer for mortuary claim in [database/seeders/SampleDataSeeder.php](/c:/xampp/htdocs/new_anitech/database/seeders/SampleDataSeeder.php:418)
  - pending checklist sample in [database/seeders/SampleDataSeeder.php](/c:/xampp/htdocs/new_anitech/database/seeders/SampleDataSeeder.php:431)

If needed before the presentation, run:

```powershell
php artisan db:seed --class=SampleDataSeeder
```

## Suggested Flow

1. Start with `admin`
2. Continue with `staff`
3. End with `farmer`

That sequence works well because it shows the system from management, to operations, to end-user experience.

## Short Opening Script

"Good day. This system is a web-based agriculture management system for San Carlos City, Pangasinan. It supports three main user groups: admin, staff, and farmers. The system handles farmer records, membership applications, renewals, payments, advisories, queries, notifications, and mortuary claim processing. For this demonstration, I will show how each user role interacts with the same system."

## Admin Demo Script

### Intro Line

"I will begin with the admin side, which focuses on monitoring, configuration, and account management."

### Show Login

- Open `/login`
- Log in as `admin@anitech.test`

Say:

"The admin can securely access the back-office side of the platform. After login, the admin can monitor operational data and manage the overall configuration of the system."

### Show Dashboard / Reports

Admin lands on the reports dashboard defined in [routes/admin/reports.php](/c:/xampp/htdocs/new_anitech/routes/admin/reports.php:5).

Say:

"This dashboard gives the admin a high-level view of the system. It can summarize membership activity, farmer records, and other operational indicators. This helps management monitor the performance of the agriculture office."

### Show Location Management

Use the admin-only location routes loaded from [routes/admin.php](/c:/xampp/htdocs/new_anitech/routes/admin.php:22).

Say:

"The admin can maintain master data such as barangays and associations. This is important because farmer records and advisories are tied to location-based groupings."

### Show Fee Schedule

Say:

"The admin can also manage the fee schedule for the current year. This allows the system to apply the correct membership, annual, and mortuary fees during assessment and payment processing."

### Show User Management

Use the admin-only user module from [routes/admin.php](/c:/xampp/htdocs/new_anitech/routes/admin.php:25).

Say:

"Another key responsibility of the admin is user management. The admin can create and maintain office accounts, assign roles, and make sure the right people have access to the right functions."

### Show Registry / Reports Value

Optionally open farmer report from [routes/admin/farmers.php](/c:/xampp/htdocs/new_anitech/routes/admin/farmers.php:8).

Say:

"At the admin level, the system supports reporting and registry visibility, making it easier to review the overall status of registered farmers and office transactions."

### Transition to Staff

"After the admin configures and oversees the system, the daily operational work is mainly handled by staff. Next, I will show the staff workflow."

## Staff Demo Script

### Intro Line

"The staff role is focused on day-to-day processing of farmer transactions and requests."

### Show Login

- Log out
- Log in as `staff@anitech.test`

Say:

"Staff users handle the operational side of the platform. They process applications, review documents, record payments, respond to farmer concerns, and manage claims and advisories."

### Show Farmer Registry

Use the farmer registry routes in [routes/admin/farmers.php](/c:/xampp/htdocs/new_anitech/routes/admin/farmers.php:6).

Say:

"Here in the farmer registry, staff can view and encode farmer records. This serves as the central recordkeeping module of the system."

Suggested sample records to mention:

- `SMP-FRM-0001` as an approved farmer record
- `SMP-FRM-0010` as a senior member with pending document checklist

### Show Membership Applications

Use the application routes in [routes/admin/membership.php](/c:/xampp/htdocs/new_anitech/routes/admin/membership.php:5).

Say:

"For membership applications, staff can review submitted applications, inspect uploaded documents, verify requirements, approve qualified applicants, or reject incomplete or invalid submissions."

Suggested sample cases:

- approved application: Mario Aguila
- under review: Rogelio Cruz
- rejected application: Celia Rivera

### Show Renewal Processing

Use the renewal routes in [routes/admin/renewals.php](/c:/xampp/htdocs/new_anitech/routes/admin/renewals.php:7).

Say:

"For existing members, the system supports renewal processing. Staff can check renewal requests, review uploaded proof, record payments, and update the renewal status."

Suggested sample cases:

- pending payment renewal: Ernesto Garcia
- approved renewal with settled payment: Imelda Villanueva

### Show Queries / Messages

Say:

"The system also includes a query handling module where staff can view farmer concerns and provide official responses. This improves communication and reduces the need for purely manual follow-up."

### Show Advisories / Notifications

Say:

"Staff can publish advisories and system notifications so farmers receive important announcements, reminders, and updates through the portal."

### Show Mortuary Claims

Say:

"A specialized function of the system is mortuary claim processing. Staff can review claim documents, validate requirements, approve or reject claims, and track released benefits."

Suggested case:

- deceased farmer scenario: Pedro Manalo

### Transition to Farmer

"After showing the office workflow, I will now switch to the farmer side to demonstrate how the system looks from the end-user perspective."

## Farmer Demo Script

### Intro Line

"The farmer side is designed as a simpler portal where members can submit requests, track status, pay fees, and receive updates."

### Show Farmer Portal Entry

Use the farmer routes in [routes/farmer.php](/c:/xampp/htdocs/new_anitech/routes/farmer.php:13).

Say:

"The farmer portal supports membership application, account setup, login, renewal, payments, alerts, and queries."

### Option A: Show New Applicant Journey

- Open `/farmer/apply`

Say:

"A new farmer can submit an application through the online form. The applicant provides personal information, selects barangay and association, and submits the requirements digitally."

- Open `/farmer/track`

Say:

"After submission, the applicant can track the application status. This reduces uncertainty because the applicant can see whether the application is submitted, under review, approved, or requires additional action."

### Option B: Show Existing Farmer Account

- Open `/farmer/login`
- Log in as `farmer01@sample.anitech.test`

Say:

"Once the farmer already has an approved and active account, they can log in to the member portal. Here they can view their profile, membership status, payments, and notifications."

### Show Home/Profile

Say:

"This home page gives the farmer a quick summary of their current membership and renewal status."

### Show Payments

Go to `/farmer/payments`.

Say:

"In the payments page, the farmer can view assessments and payment history. This makes the financial side of membership more transparent."

### Show Alerts

Go to `/farmer/alerts`.

Say:

"In alerts, the farmer can see advisories and notifications that are relevant to them. Advisories may be targeted by barangay, association, or member type."

### Show Queries

Go to `/farmer/queries`.

Say:

"If the farmer has a concern, they can submit a query directly through the portal. The staff can respond inside the system, and the conversation becomes easier to track."

### Show Renewal

Go to `/farmer/renewal`.

Say:

"For annual renewal, the farmer can submit the request online, upload the required documents, and proceed to payment. This reduces in-person processing time and improves tracking."

Suggested farmer account for renewal-focused demo:

- `farmer06@sample.anitech.test` for an active member account

## Closing Script

"In summary, this system connects the admin, staff, and farmer workflows in one platform. The admin manages configuration and oversight, the staff handles operational processing, and the farmers can apply, track, renew, pay, and communicate online. Because the records, payments, advisories, and requests are all centralized, the system improves efficiency, transparency, and service delivery."

## Shorter 3-Minute Version

"This project is a web-based agriculture management system with three user roles: admin, staff, and farmer. On the admin side, the system supports dashboard monitoring, location management, fee setup, and user management. On the staff side, it supports farmer registry management, membership application review, renewal processing, payment recording, query response handling, advisories, and mortuary claims. On the farmer side, users can apply for membership, track status, log in to their account, view alerts and payments, send queries, and submit renewals online. Overall, the system centralizes records and improves the speed and visibility of service delivery."

## Presenter Notes

- If you want the smoothest demo, use:
  - `admin@anitech.test` for admin
  - `staff@anitech.test` for staff
  - `farmer01@sample.anitech.test` for an active farmer
- If a page looks empty, mention that the module still exists and switch to another seeded sample account.
- If time is limited, prioritize:
  - admin dashboard
  - staff membership applications
  - staff renewals
  - farmer alerts
  - farmer renewal
