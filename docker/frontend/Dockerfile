# Node runtime for the Next.js frontend.
#
# Debian (glibc) instead of Alpine (musl) so Playwright browsers and other
# prebuilt native dependencies are supported out of the box.
FROM node:22-bookworm-slim

ARG APP_UID=1000
ARG APP_GID=1000

WORKDIR /app

# Shared libraries required by the Playwright browsers used in the E2E suite.
# Browser binaries themselves are downloaded at runtime with:
#   docker compose exec frontend npx playwright install chromium
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        fonts-liberation \
        libasound2 \
        libatk-bridge2.0-0 \
        libatk1.0-0 \
        libatspi2.0-0 \
        libcairo2 \
        libcups2 \
        libdbus-1-3 \
        libdrm2 \
        libgbm1 \
        libglib2.0-0 \
        libnspr4 \
        libnss3 \
        libpango-1.0-0 \
        libwayland-client0 \
        libxcb1 \
        libxcomposite1 \
        libxdamage1 \
        libxfixes3 \
        libxkbcommon0 \
        libxrandr2 \
    && rm -rf /var/lib/apt/lists/*

# Align the built-in `node` user with the host user so bind-mounted files stay
# writable without permission juggling.
RUN groupmod -g "${APP_GID}" node \
    && usermod -u "${APP_UID}" -g "${APP_GID}" node \
    && chown -R node:node /app

USER node

EXPOSE 3000

CMD ["npm", "run", "dev"]
