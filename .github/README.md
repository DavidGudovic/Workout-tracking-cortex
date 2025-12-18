# GitHub Workflows

This directory contains GitHub Actions workflows for CI/CD.

## Workflows

### CI Workflow (`ci.yml`)
Runs on every push and pull request to `main` and `develop` branches.

**What it does:**
- Runs tests on PHP 8.2 and 8.3
- Checks code style with Pint
- Runs security audit
- Uses PostgreSQL and Redis service containers

**Required secrets:** None (uses public runners)

### Deploy Workflow (`deploy.yml`)
Runs on push to `main` branch or manually via workflow_dispatch.

**What it does:**
- Installs production dependencies
- Creates deployment artifact
- Provides hooks for various deployment methods (SSH, Forge, Vapor)

**Setup required:**
1. Uncomment your preferred deployment method
2. Add deployment secrets to GitHub repository:
   - For SSH: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_KEY`
   - For Forge: `FORGE_DEPLOYMENT_URL`
   - For Vapor: Configure vapor CLI

### Dependabot (`dependabot.yml`)
Automatically creates PRs for dependency updates.

**Configuration:**
- Composer dependencies: Weekly on Mondays at 9:00 AM
- GitHub Actions: Weekly on Mondays at 9:00 AM

## Setup Instructions

### 1. Enable GitHub Actions
Actions are enabled by default for new repositories.

### 2. Configure Branch Protection (Optional but Recommended)
Go to Settings → Branches → Add rule for `main`:
- ✅ Require status checks before merging
- ✅ Require branches to be up to date
- Select: `Tests (PHP 8.2)`, `Tests (PHP 8.3)`, `Code Style (Pint)`

### 3. Setup Deployment (if using deploy workflow)
Edit `.github/workflows/deploy.yml` and uncomment your deployment method.

#### SSH Deployment Example:
```yaml
- name: Deploy via SSH
  uses: appleboy/ssh-action@master
  with:
    host: ${{ secrets.DEPLOY_HOST }}
    username: ${{ secrets.DEPLOY_USER }}
    key: ${{ secrets.DEPLOY_KEY }}
    script: |
      cd /var/www/fittrack-api
      git pull origin main
      composer install --no-dev --optimize-autoloader
      php artisan migrate --force
      php artisan config:cache
      php artisan route:cache
```

Then add secrets in Settings → Secrets → Actions:
- `DEPLOY_HOST`: Your server IP/hostname
- `DEPLOY_USER`: SSH username
- `DEPLOY_KEY`: Private SSH key

### 4. Local Testing of Workflows
You can test workflows locally using [act](https://github.com/nektos/act):

```bash
# Install act
brew install act  # macOS
# or
curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash

# Run CI workflow
act -j tests

# Run specific job
act -j code-style
```

## Badges

Add these badges to your README.md:

```markdown
![CI](https://github.com/your-username/workouts-api/workflows/CI/badge.svg)
![Deploy](https://github.com/your-username/workouts-api/workflows/Deploy/badge.svg)
```

## Workflow Status

View workflow runs: **Actions** tab in GitHub repository

## Troubleshooting

### Tests failing in CI but passing locally?
- Check PHP version matches (8.2 or 8.3)
- Verify PostgreSQL version (using 16 in CI)
- Check environment variables in workflow

### Deployment not triggering?
- Ensure you're pushing to `main` branch
- Check workflow file is in `.github/workflows/`
- Verify YAML syntax is valid

### Dependabot PRs not appearing?
- Update `reviewers` in `dependabot.yml` with your GitHub username
- Check Insights → Dependency graph → Dependabot
