# Use Node 20 LTS slim for stability
FROM node:20-slim

WORKDIR /app

# Install dependencies
COPY package*.json ./
RUN npm install --legacy-peer-deps

# Copy source code
COPY . .

# Ensure local node_modules binaries (like vite) are available
ENV PATH="/app/node_modules/.bin:$PATH"

# Expose dev server port
EXPOSE 5173

# Run Vite dev server
CMD ["npm", "run", "dev", "--", "--host"]
