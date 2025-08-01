# Presentation Builder MVP

## Setup

1. Run `scripts/create_env.sh` to set your API key, LLM URL/model, and project name.
2. Set up Nginx with `scripts/setup_nginx.sh` and edit PHP limits with `scripts/set_php_upload_size.sh`.
3. Place or upload .pptx templates in `/templates/<name>/`.
4. Use `index.php` for project and template management.

## Python

- Drop your audit/analyze scripts in `/python_web/`. Flask or FastAPI recommended.

## Logs

- See `/logs/` for all API and error logs.
