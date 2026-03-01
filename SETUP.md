# Wedding Site - Setup Guide

## Environment Variables & Security

This project uses environment variables to securely store sensitive configuration like email credentials. This ensures your passwords are **never committed to version control**.

### Local Development Setup

1. **Copy the example environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Edit `.env` with your actual credentials:**
   ```bash
   nano .env  # or open in your editor
   ```

3. **Configure your email credentials:**
   - `RECIPIENT_EMAIL`: Where RSVP emails should be sent
   - `SENDER_EMAIL`: Your Gmail address
   - `SMTP_USERNAME`: Your Gmail address
   - `SMTP_PASSWORD`: Your Gmail App Password (see below)

### Getting a Gmail App Password

For security, Gmail doesn't allow direct use of your main password. Instead:

1. Go to: https://myaccount.google.com/apppasswords
2. Select "Mail" and "macOS" (or your OS)
3. Google will generate a unique 16-character password
4. Copy it to the `SMTP_PASSWORD` field in `.env`

⚠️ **Important**: This password should NEVER be shared or committed to version control.

### Production Deployment

**GitHub Pages** (Static hosting - only works with client-side or external services):
- You cannot run PHP on GitHub Pages
- Requires external email service or backend

**Dedicated Domain/Server**:
Set environment variables through your hosting provider:

- **nginx/Apache**: Create a `.env` file in the web root with proper permissions
- **Docker**: Use environment variables or secrets
- **cPanel/Hosting Panel**: Use the control panel to set environment variables
- **Heroku/Railway/Render**: Use the platform's environment variable settings

**Example for shared hosting (cpanel):**
1. Create `.env` file in your public_html
2. Paste your configuration
3. Set file permissions to `600` (not readable by others)

### Security Checklist

- ✅ Never commit `.env` to git (it's in `.gitignore`)
- ✅ Use strong, unique App Passwords from Google
- ✅ Use HTTPS on production (required for security)
- ✅ Set proper file permissions on `.env` (readable only by web server)
- ✅ Keep `.env.example` in git (without actual credentials)
- ✅ Rotate credentials if accidentally exposed

### Testing

```bash
# Test locally
php -S localhost:8000

# Then submit a test RSVP on http://localhost:8000
```

Check `logs/rsvp_YYYY-MM-DD.log` to verify submissions and any errors.

### Troubleshooting

**"Missing required environment variables" error:**
- Make sure `.env` exists in the root directory
- Verify all required variables are filled in
- Check file permissions

**Emails not sending:**
- Verify `SMTP_PASSWORD` is a valid App Password (not your main Gmail password)
- Check logs for detailed error messages
- Ensure your server can reach smtp.gmail.com:587

