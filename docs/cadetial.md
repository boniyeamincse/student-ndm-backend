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

This project does not seed a default member login by default.
Use the following test credential pattern for QA after creating a member account.

## Member Test Account (QA)

- **URL**: `http://localhost:5173/login` (web frontend login page)
- **Email**: `member.test@ndm.local`
- **Password**: `Member@12345`
- **Role**: `member`

## Setup Steps

1. Register or create a user using the email above.
2. Ensure the user has the `member` role.
3. Complete profile setup if prompted after login.

## Security Note

Use this account for local testing only. Rotate or remove it in shared/staging environments.
