#!/bin/bash
ENV_FILE=".env"
read -p "Enter your OpenAI API key: " API_KEY
read -p "Enter LLM endpoint URL [https://api.openai.com/v1/chat/completions]: " LLM_URL
LLM_URL=${LLM_URL:-https://api.openai.com/v1/chat/completions}
read -p "Enter model name [gpt-4o]: " LLM_MODEL
LLM_MODEL=${LLM_MODEL:-gpt-4o}
read -p "Enter OpenAI project name [dr-deck-gen]: " OPENAI_PROJECT
OPENAI_PROJECT=${OPENAI_PROJECT:-dr-deck-gen}
if [[ -f "$ENV_FILE" ]]; then
    grep -q "OPENAI_API_KEY" "$ENV_FILE" && \
        sed -i "s|^OPENAI_API_KEY=.*|OPENAI_API_KEY=$API_KEY|" "$ENV_FILE" || \
        echo "OPENAI_API_KEY=$API_KEY" >> "$ENV_FILE"
    grep -q "LLM_ENDPOINT" "$ENV_FILE" && \
        sed -i "s|^LLM_ENDPOINT=.*|LLM_ENDPOINT=$LLM_URL|" "$ENV_FILE" || \
        echo "LLM_ENDPOINT=$LLM_URL" >> "$ENV_FILE"
    grep -q "LLM_MODEL" "$ENV_FILE" && \
        sed -i "s|^LLM_MODEL=.*|LLM_MODEL=$LLM_MODEL|" "$ENV_FILE" || \
        echo "LLM_MODEL=$LLM_MODEL" >> "$ENV_FILE"
    grep -q "OPENAI_PROJECT" "$ENV_FILE" && \
        sed -i "s|^OPENAI_PROJECT=.*|OPENAI_PROJECT=$OPENAI_PROJECT|" "$ENV_FILE" || \
        echo "OPENAI_PROJECT=$OPENAI_PROJECT" >> "$ENV_FILE"
else
    cat > "$ENV_FILE" <<EOF
OPENAI_API_KEY=$API_KEY
LLM_ENDPOINT=$LLM_URL
LLM_MODEL=$LLM_MODEL
OPENAI_PROJECT=$OPENAI_PROJECT
EOF
fi
echo ".env file updated:"
cat "$ENV_FILE"
