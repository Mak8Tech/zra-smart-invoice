# Deployment Guide for Packagist.org

This guide provides step-by-step instructions for deploying the ZRA Smart Invoice package to Packagist.org.

## Prerequisites

1. A GitHub account with your package repository set up
2. A Packagist.org account
3. Proper package configuration in composer.json (already done)

## Steps to Deploy to Packagist

### 1. Push Your Code to GitHub

Make sure your package code is pushed to GitHub and properly structured:

```bash
git add .
git commit -m "Prepare for initial release"
git push origin main
```

### 2. Tag a Release on GitHub

Create a release tag to specify the version:

```bash
git tag v0.1.0
git push --tags
```

Or create a release through the GitHub web interface:
1. Go to your repository
2. Click on "Releases"
3. Click "Create a new release"
4. Enter the tag version (e.g., "v0.1.0")
5. Add a title and description
6. Click "Publish release"

### 3. Submit Your Package to Packagist

1. Go to [Packagist.org](https://packagist.org/)
2. Log in to your account
3. Click "Submit Package"
4. Enter your GitHub repository URL (e.g., `https://github.com/mak8tech/zra-smart-invoice`)
5. Click "Check" and then "Submit"

### 4. Set Up GitHub Webhook for Packagist

For automatic updates when you push changes:

1. On Packagist, go to your profile
2. Find the API Token in your profile
3. Copy the Webhook URL with your username and token
4. Go to your GitHub repository
5. Go to Settings > Webhooks > Add webhook
6. Paste the Packagist webhook URL
7. Set Content type to "application/json"
8. Click "Add webhook"

### 5. Updating Your Package

When making changes to your package:

1. Update the code
2. Update CHANGELOG.md
3. Commit and push changes
4. Create a new release/tag with the updated version number
5. Packagist will automatically update through the webhook

## Semantic Versioning

Follow Semantic Versioning for your releases:

- **MAJOR** version when you make incompatible API changes
- **MINOR** version when you add functionality in a backward-compatible manner
- **PATCH** version when you make backward-compatible bug fixes

## GitHub Actions Workflows

The package includes two GitHub Actions workflows:

1. **PHP Tests** (`php-test.yml`) - Runs tests on various PHP and Laravel versions
2. **Update README** (`update-readme.yml`) - Automatically updates badge information in the README

These workflows will run automatically when code is pushed or PRs are created against the main branch.
