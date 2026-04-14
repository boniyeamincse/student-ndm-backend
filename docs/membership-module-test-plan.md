# Membership Backend Test Coverage Plan

## Implemented Feature Test Files

- tests/Feature/AdminMembershipApplicationsApiTest.php
- tests/Feature/AdminMembersApiTest.php

## Membership Applications Coverage

- Admin can list applications.
- Admin can filter pending/approved.
- Admin can search by name/email/mobile/application_no.
- Admin can view application detail.
- Admin can mark under review.
- Admin can approve.
- Approval creates member and linked user.
- Admin can reject with reason.
- Admin can hold with remarks.
- Invalid transition (approved -> reject) is blocked.
- Unauthorized member role is forbidden from admin routes.

## Members Coverage

- Admin can list members.
- Admin can filter active/inactive/suspended.
- Admin can view member detail.
- Admin can update safe profile fields.
- Admin can update member status.
- Revoke access marks linked user inactive.
- Unauthorized member role is forbidden from admin routes.

## Additional Recommended Cases

- Pagination assertions for list endpoints.
- Validation structure assertions for invalid payloads.
- Resource field consistency checks for list/detail responses.
- Summary endpoint count assertions by status and gender.
