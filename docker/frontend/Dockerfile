# Node runtime for the Next.js frontend.
#
# Debian (glibc) instead of Alpine (musl) so Playwright browsers and other
# prebuilt native dependencies are supported out of the box.
FROM node:22-bookworm-slim

ARG APP_UID=1000
ARG APP_GID=1000

WORKDIR /app

# Align the built-in `node` user with the host user so bind-mounted files stay
# writable without permission juggling.
RUN groupmod -g "${APP_GID}" node \
    && usermod -u "${APP_UID}" -g "${APP_GID}" node \
    && chown -R node:node /app

USER node

EXPOSE 3000

CMD ["npm", "run", "dev"]
