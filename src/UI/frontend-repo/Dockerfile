FROM node:18-alpine

WORKDIR /usr/src/app

# Only install the bare minimum for development
RUN npm install -g nodemon

# We'll mount everything else as volumes
EXPOSE 3000

CMD ["sh", "-c", "npm install && npm run dev"]