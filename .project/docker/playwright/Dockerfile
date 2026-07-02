# Playwright test runner with a Docker CLI and make on board.
#
# The Docker CLI + make lets Playwright tests run docker and make commands inside
# the sibling compose services via the shared command helper (execInContainer /
# execTypo3Command).
FROM mcr.microsoft.com/playwright:v1.59.1-noble

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl gnupg make \
    && install -m 0755 -d /etc/apt/keyrings \
    && curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg \
    && chmod a+r /etc/apt/keyrings/docker.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu noble stable" > /etc/apt/sources.list.d/docker.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends docker-ce-cli docker-compose-plugin \
    && rm -rf /var/lib/apt/lists/*
