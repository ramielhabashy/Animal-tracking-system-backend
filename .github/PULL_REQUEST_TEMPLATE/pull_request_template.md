---
name: Pull Request
about: Submit a pull request to the Animal Tracking System
---

## Description
A clear and concise description of what this PR does.

## Related Issue
Closes #[issue_number]
Fixes #[issue_number]
Relates to #[issue_number]

## Component
Which part of the system does this PR affect?
- [ ] Backend API (Laravel)
- [ ] Frontend (React)
- [ ] Mobile App (Flutter)
- [ ] Database
- [ ] CI/CD Pipeline
- [ ] Documentation

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Refactoring (no functional changes)
- [ ] Performance improvement
- [ ] Documentation update
- [ ] CI/CD changes

## Changes Made
Describe the changes made in detail:
- Change 1
- Change 2
- Change 3

## API Changes (if applicable)
### New Endpoints
```
METHOD /api/new-endpoint
Description: ...
```

### Modified Endpoints
```
METHOD /api/existing-endpoint (modified)
Changes: ...
```

### Deprecated Endpoints
```
METHOD /api/deprecated-endpoint (will be removed in vX.X)
```

## Database Changes (if applicable)
- [ ] New migration(s) added
- [ ] Seeder(s) modified
- [ ] No database changes

### Migration Files
- `yyyy_mm_dd_xxxxxx_migration_name.php`

## Frontend Changes (if applicable)
- [ ] New component(s) added
- [ ] Existing component(s) modified
- [ ] Styling changes (Tailwind)
- [ ] State management changes
- [ ] Routing changes

## Mobile Changes (if applicable)
- [ ] New screen(s) added
- [ ] Existing screen(s) modified
- [ ] New widget(s) added
- [ ] Package dependencies updated

## Screenshots (if applicable)
Add screenshots or screen recordings to demonstrate the changes.

### Before
[Screenshot of before]

### After
[Screenshot of after]

## Testing
Describe the tests you've added or modified:

### Unit Tests
- [ ] Backend unit tests added/updated
- [ ] Frontend unit tests added/updated
- [ ] Mobile tests added/updated

### Integration Tests
- [ ] API integration tests added
- [ ] End-to-end tests added

### Test Coverage
Current coverage: XX%
New code coverage: XX%

## Manual Testing Steps
Steps to manually test this PR:
1. Step 1
2. Step 2
3. Step 3

## Checklist
- [ ] Code follows the project's style guidelines
- [ ] Self-review of code completed
- [ ] Code is properly commented, particularly in hard-to-understand areas
- [ ] Documentation updated (if needed)
- [ ] Tests added/updated and all tests pass
- [ ] No new warnings introduced
- [ ] Changes have been tested locally
- [ ] Database migrations tested (if applicable)
- [ ] API endpoints tested with Postman/curl
- [ ] Mobile app builds successfully (if applicable)
- [ ] Frontend builds successfully (if applicable)

## Breaking Changes
If this is a breaking change, describe the impact and migration path:
- **Impact**: ...
- **Migration Guide**: ...

## Environment Tested
- **OS**: [e.g., Windows 11, macOS Sonoma]
- **PHP Version**: [e.g., 8.2]
- **Node Version**: [e.g., 18.x]
- **Flutter Version**: [e.g., 3.16.x]
- **MySQL Version**: [e.g., 8.0]

## Additional Notes
Any additional information for the reviewers.

## Reviewers
Tag specific reviewers if needed:
@username1
@username2
