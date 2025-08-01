#!/bin/bash
ENV_FILE=".env"
read -p "Enter your OpenAI API key: " API_KEY
read -p "Enter LLM endpoint URL [https://api.openai.com/v1/chat/completions]: " LLM_URL
LLM_URL=${LLM_URL:-https://api.openai.com/v1/chat/completions}
read -p "Enter model name [gpt-4o]: " LLM_MODEL
LLM_MODEL=${LLM_MODEL:-gpt-4o}
read -p "Enter OpenAI project name [dr-deck-gen]: " OPENAI_PROJECT
OPENAI_PROJECT=${OPENAI_PROJECT:-dr-deck-gen}

# Helper for add/update .env keys
update_env() {
    local key="$1"
    local value="$2"
    if grep -q "^$key=" "$ENV_FILE" 2>/dev/null; then
        sed -i "s|^$key=.*|$key=$value|" "$ENV_FILE"
    else
        echo "$key=$value" >> "$ENV_FILE"
    fi
}

if [[ -f "$ENV_FILE" ]]; then
    update_env "OPENAI_API_KEY" "$API_KEY"
    update_env "LLM_ENDPOINT" "$LLM_URL"
    update_env "LLM_MODEL" "$LLM_MODEL"
    update_env "OPENAI_PROJECT" "$OPENAI_PROJECT"
    update_env "LOGGING_ENABLED" "true"
    update_env "LOGGING_LEVEL" "DEBUG"
else
    cat > "$ENV_FILE" <<EOF
OPENAI_API_KEY=$API_KEY
LLM_ENDPOINT=$LLM_URL
LLM_MODEL=$LLM_MODEL
OPENAI_PROJECT=$OPENAI_PROJECT
LOGGING_ENABLED=true
LOGGING_LEVEL=DEBUG
EOF
fi

echo ".env file updated:"
cat "$ENV_FILE"
