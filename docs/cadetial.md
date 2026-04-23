# Super Admin Credentials

This document contains the default super admin credentials for the NDM project.

> [!WARNING]
> Change the default password immediately after the first login!

## Credentials

- **URL**: `http://localhost:8001/login` (or equivalent backend URL)
- **Email**: `admin@studentmovment-ndm.com`
- **Password**: `password123@ChangeMe`
- **Role**: `superadmin`

## Note
These credentials are created by the `SuperAdminSeeder.php`.

---

# Member Testing Credentials

This account is seeded into the database for local QA.

## Member Test Account (QA)

- **URL**: `http://localhost:5173/login` (web frontend login page)
- **Email**: `member.test@ndm.local`
- **Password**: `Member@12345`
- **Role**: `member`

## Setup Steps

1. Run the seeders: `php artisan db:seed`.
2. Login using the credentials above.
3. Complete profile setup if prompted after login.

## Seeder Reference

This account is created/updated by `TestMemberSeeder.php`.

## Security Note

Use this account for local testing only. Rotate or remove it in shared/staging environments.
