# Node runtime for the Next.js frontend.
#
# Debian (glibc) instead of Alpine (musl) so Playwright browsers and other
# prebuilt native dependencies are supported out of the box.
FROM node:22-bookworm-slim

WORKDIR /app

# Run as the built-in `node` user (uid/gid 1000) so bind-mounted files stay
# writable by the host user without permission juggling.
RUN chown node:node /app

USER node

EXPOSE 3000

CMD ["npm", "run", "dev"]
