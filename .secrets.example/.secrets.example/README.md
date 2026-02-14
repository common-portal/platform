# Secrets Configuration

This directory contains **template files** for secret configuration. 

## Setup Instructions

1. **Copy the example directory:**
   ```bash
   cp -r .secrets.example .secrets
   ```

2. **Edit each file** in `.secrets/` with your actual credentials

3. **The `.secrets/` directory is gitignored** — your credentials will never be committed

---

## Files

| File | Purpose | Required |
|------|---------|----------|
| `openai.env` | OpenAI API key for translation service | Yes (for translation) |
| `smtp.env` | SMTP credentials for email delivery | Yes (for email) |
| `database.env` | Database connection credentials | Optional (uses Docker default) |

---

## How It Works

The application loads secrets from environment variables. You can either:

1. **Use `.secrets/` files** — Source them in your shell or Docker entrypoint
2. **Set in `.env`** — Add variables directly to your root `.env` file
3. **Use system environment** — Set via your hosting platform (Heroku, AWS, etc.)

### Loading .secrets files (optional helper)

Add to your shell profile or Docker entrypoint:
```bash
for f in .secrets/*.env; do
  [ -f "$f" ] && export $(grep -v '^#' "$f" | xargs)
done
```

---

## Security Notes

- **Never commit** the `.secrets/` directory
- **Never share** these files publicly
- **Rotate keys** periodically
- **Use separate keys** for development vs production

---

## For Contributors

When adding new secret requirements:

1. Add a template file to `.secrets.example/`
2. Document the new file in this README
3. Update application code to read from the new environment variable
