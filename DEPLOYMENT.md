# Deployment Guide

This document outlines the steps to deploy the ZRA Smart Invoice package to Packagist.org.

## Prerequisites

Before deploying, make sure you have:

1. A GitHub account with ownership or contributor access to the repository
2. A Packagist.org account linked to your GitHub account
3. Composer installed locally
4. Git installed locally

## Deployment Checklist

Before releasing a new version, ensure you've completed the following:

- [ ] All tests pass (`composer test` and `npm run test`)
- [ ] Documentation is up-to-date
- [ ] CHANGELOG.md is updated with all changes
- [ ] Version numbers are updated in:
  - [ ] package.json
  - [ ] composer.json (if needed)
- [ ] All changes are committed and pushed to GitHub

## Deployment Steps

### 1. Tag a New Release on GitHub

```bash
# Ensure you're on the main branch
git checkout main

# Pull the latest changes
git pull origin main

# Tag the new version (replace X.Y.Z with your version number)
git tag vX.Y.Z

# Push the tag to GitHub
git push origin vX.Y.Z
```

### 2. Create a GitHub Release (Optional but Recommended)

1. Go to your repository on GitHub
2. Click on "Releases"
3. Click "Create a new release"
4. Select the tag you just pushed
5. Add a title (typically the version number)
6. Add release notes (you can copy from the CHANGELOG.md)
7. Click "Publish release"

### 3. Update on Packagist

If this is the first time publishing the package:

1. Go to [Packagist.org](https://packagist.org)
2. Click "Submit" in the top menu
3. Enter your GitHub repository URL (e.g., `https://github.com/yourusername/zra-smart-invoice`)
4. Click "Check" and follow the prompts

For subsequent updates:

- Packagist will automatically update when you push a new tag if you have configured the GitHub webhook
- If the automatic update doesn't work, you can manually update by going to your package on Packagist and clicking "Update"

### 4. Verify the Release

After deploying, verify that:

1. The new version appears on Packagist
2. The package can be installed with Composer:
   ```bash
   composer require mak8tech/zra-smart-invoice:^X.Y.Z
   ```
3. The GitHub release page shows the new version

## Setting Up GitHub Webhook for Automatic Updates

To have Packagist automatically update when you push changes:

1. Go to your package page on Packagist
2. Click "Manage" tab
3. Copy the webhook URL
4. Go to your GitHub repository settings
5. Click "Webhooks" on the left menu
6. Click "Add webhook"
7. Paste the Packagist webhook URL as the Payload URL
8. Keep Content type as `application/json`
9. Click "Add webhook"

## Troubleshooting

If you encounter issues during deployment:

- Ensure all composer.json requirements are valid
- Verify that your GitHub repository is public
- Check that your package name in composer.json matches the name on Packagist
- Confirm that your tag names follow Semantic Versioning (vX.Y.Z)

## Post-Deployment

After successful deployment:

1. Update your project board with completed tasks
2. Notify users/contributors about the new release
3. Start planning the next version
